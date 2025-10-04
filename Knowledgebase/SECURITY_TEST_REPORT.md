# SecureWP Pro v2.0.0 – Security Test Report and Verification Plan

This document provides a structured test plan to verify that the security features implemented by the `SecureWP Pro` version 2.0.0 plugin are functioning correctly on your WordPress site.

- Plugin bootstrap: `wp-content/plugins/securewp-pro/securewp-pro.php`
- Key classes:
  - `includes/class-cron-security.php`
  - `includes/class-xmlrpc-security.php`
  - `includes/class-login-rate-limiting.php`
  - `includes/class-rest-api-security.php`
  - `includes/class-password-reset-rate-limiting.php`
  - `includes/class-general-rate-limiting.php`
  - `includes/class-lockout-manager.php`
  - `includes/class-logger.php`
  - `includes/class-notifier.php`
- Admin UI: `admin/partials/tab-settings.php` (Settings, Lockouts, Logs)
- Evidence URLs: see `EVIDENCE_URLS.md` for quick, client-facing endpoints and curl examples.

## Quick Links
- [Evidence URLs (Client-Facing)](./EVIDENCE_URLS.md)
- [Deployment-Agnostic Test Guide](./TEST_GUIDE_SKELETON.md)
- [Folder Structure & Changelog](./folder-structure.md)

## Prerequisites

1. Ensure the plugin is active and tables exist:
   - Logs table: `{wp_prefix}securewp_pro_logs` (created by `SecureWP_Pro_Logger::create_log_table()`)
   - Lockouts table: `{wp_prefix}securewp_pro_lockouts` (created by `SecureWP_Pro_Lockout_Manager::create_lockout_table()`)
2. WP admin access to verify screens: SecureWP Pro → `Overview`, `Lockouts`, `Logs`, `Settings`.
3. Mail delivery configured if you want to test email notifications.
4. Optional per feature:
   - General rate limiting – Contact Form 7 and/or Fluent Forms installed with at least one active form.
   - Registration tests – Enable “Anyone can register” in Settings → General.
   - For alternate cron tests – define `ALTERNATE_WP_CRON` and set a `Cron Secret Key` in plugin settings.

## How to Reset Between Tests

- From SecureWP Pro → `Lockouts`: unlock/delete specific lockouts.
- From SecureWP Pro → `Logs`: clear logs (see Overview’s Quick Action or plugin’s export/clear helpers in `securewp-pro.php`).
- Successful login clears login lockout state for your IP (see `SecureWP_Pro_Login_Rate_Limiting::track_successful_login()`).

---

## 1) WP-Cron Security

Code: `includes/class-cron-security.php`

What it does:
- Adds authentication header `X-SecureWP-Cron` to internal cron requests (`secure_cron_requests`).
- Blocks unauthorized access to `/wp-cron.php` and rate-limits requests.
- Allows server-local requests or whitelisted IPs (now read from the Settings field `Authorized Cron IPs`).
- Accepts alternate cron authentication via `?secret=YOUR_KEY` when `ALTERNATE_WP_CRON` is enabled.

Settings to check (Admin → SecureWP Pro → Settings → Core Security Features):
- “WP-Cron Security” enabled.
- Optional: add your testing IP to “Authorized Cron IPs” (Advanced tab).
- Note the value in “Cron Secret Key” (option key `securewp_pro_cron_secret_key`). This is used for both the header and the `?secret=` URL parameter.
- Regenerating the key is done via the “Generate New Key” button (AJAX, no page reload). The UI also provides Show/Hide and Copy controls next to the key.

Test steps:
1. Without auth header, request `/wp-cron.php`:
   - Expected: 403 Forbidden and log entry `cron_security: blocked`.
   - Example (curl):
     ```bash
     curl -i https://your-site.example/wp-cron.php
     ```
2. With wrong header value:
   - Expected: 403 Forbidden and `blocked` log.
   - Example:
     ```bash
     curl -i -H "X-SecureWP-Cron: WRONG" https://your-site.example/wp-cron.php
     ```
3. With correct header value:
   - Find or set value in settings: “Cron Secret Key” (option key `securewp_pro_cron_secret_key`).
   - Expected: 200 OK cron behavior, log entry `cron_security: access`.
   - Example:
     ```bash
     curl -i -H "X-SecureWP-Cron: <your-correct-key>" https://your-site.example/wp-cron.php
     ```
5. Alternate cron with secret parameter (when `ALTERNATE_WP_CRON` is enabled):
   - Example:
     ```bash
     curl -i "https://your-site.example/wp-cron.php?secret=<your-correct-key>"
     ```
4. Rate limiting:
   - Make >10 requests/min to `/wp-cron.php` with or without header.
   - Expected: 403 Forbidden via `block_cron_access()` after threshold; logs include `cron_security: blocked` and prior `access` events.

Verification:
- SecureWP Pro → `Logs` should show `cron_security` events (access/blocked) with your IP.
- Authorized IPs from Settings should be allowed without the header.
- Network response codes: 403 on block.

Notes:
- The “Generate New Key” action updates the field in-place via AJAX (no admin notice/redirect anymore). Use the Copy button to quickly copy the value for curl tests.

---

## 2) XML-RPC Protection

Code: `includes/class-xmlrpc-security.php`

What it does:
- Optionally disables XML-RPC entirely (`xmlrpc_enabled` filter) if “Block XML-RPC Completely” is enabled.
- Removes pingback methods and blocks suspicious methods.
- Rate limits XML-RPC requests; may return 429 Too Many Requests.
- Logs attempts and optionally notifies the admin.

Settings to check:
- “XML-RPC Protection” enabled.
- “Block XML-RPC Completely” as needed.
- “Block Suspicious XML-RPC Methods” enabled.

Test steps:
1. Access `/xmlrpc.php` when “Block Completely” is enabled:
   - Expected: 403 Forbidden body “Forbidden”; log `xmlrpc_security: blocked`.
   - Example:
     ```bash
     curl -i https://your-site.example/xmlrpc.php
     ```
2. Call a removed pingback method (if not completely blocked):
   - Expected: method not available or blocked; log shows `pingback_attempt` or `suspicious_method` and possibly immediate block.
   - Example:
     ```bash
     curl -i -X POST https://your-site.example/xmlrpc.php \
       -H 'Content-Type: text/xml' \
       --data '<methodCall><methodName>pingback.ping</methodName><params></params></methodCall>'
     ```
3. Call a suspicious method `system.multicall` repeatedly (>10/min):
   - Expected: eventually receive 429 Too Many Requests; logs contain `xmlrpc_security: rate_limited`.

Verification:
- SecureWP Pro → `Logs` should show `xmlrpc_security` entries: `request`, `suspicious_method`, `pingback_attempt`, `blocked`, `rate_limited`.
- Response codes: 403 (blocked completely) or 429 (rate limited).

---

## 3) Login Rate Limiting

Code: `includes/class-login-rate-limiting.php`, `includes/class-lockout-manager.php`

What it does:
- Tracks failed and successful logins.
- Uses progressive lockout times stored in option `securewp_pro_lockout_times`.
- If locked out, returns `WP_Error('temporary_lockout', ...)` to the authenticator and blocks attempts.

Settings to check:
- “Login Rate Limiting” enabled.
- Lockout thresholds under `Lockout Settings` are as desired.

Test steps:
1. In a private/incognito browser, attempt login to `/wp-login.php` with a valid username and wrong password several times.
   - Expected:
     - Logs contain `login_rate_limiting: failed_attempt` entries with your IP and user agent.
     - After threshold, attempts are blocked: login page shows error “Too many failed login attempts. Please try again later.”
     - Lockout recorded in `{wp_prefix}securewp_pro_lockouts` with `event_type = 'login_failure'`.
2. Successful login from the same IP:
   - Expected: `login_rate_limiting: successful_login` logged, and lockout cleared for that IP.

Verification:
- SecureWP Pro → `Logs` for `login_rate_limiting` events.
- SecureWP Pro → `Lockouts` should show and allow unlocking the IP.
- Database: `{wp_prefix}securewp_pro_lockouts` rows for your IP with `event_type = login_failure`.

Notes:
- Automated curl-based login is brittle due to nonces; use a browser for realism.

---

## 4) Password Reset Rate Limiting

Code: `includes/class-password-reset-rate-limiting.php`

What it does:
- Tracks password reset requests and blocks the flow if the IP is locked.

Settings to check:
- “Password Reset Rate Limiting” enabled.

Test steps:
1. Visit `/wp-login.php?action=lostpassword` and submit multiple reset requests for the same or different usernames/emails.
   - Expected:
     - Logs contain `password_reset_rate_limiting: request` and eventually `blocked` for your IP.
     - When blocked, the password reset should be denied (`allow_password_reset` filter returns false).

Verification:
- SecureWP Pro → `Logs` shows `password_reset_rate_limiting` events.
- Lockouts table may include entries with `event_type = 'password_reset'`.

---

## 5) REST API Security (Users Endpoint)

Code: `includes/class-rest-api-security.php`

What it does:
- Tightens permissions for `/wp/v2/users` endpoints.
- Redacts sensitive user data for non-admins.

Settings to check:
- “REST API Security” enabled.

Test steps:
1. Unauthenticated request to list users:
   - Expected: 401 Unauthorized or 403 Forbidden (depending on environment); should not list users.
   - Example:
     ```bash
     curl -i https://your-site.example/wp-json/wp/v2/users
     ```
2. Authenticated as admin (use cookie/nonce or Application Passwords):
   - Expected: Users list works.
   - Example with Application Passwords (WordPress core feature):
     ```bash
     curl -i -u "admin:APP_PASSWORD_HERE" https://your-site.example/wp-json/wp/v2/users
     ```
3. Authenticated as a non-admin:
   - Expected: Either blocked from listing or redacted fields (`email`, `url`, `capabilities`, `extra_capabilities` removed) as per `filter_user_data()`.

Verification:
- Inspect JSON responses and confirm permission enforcement and redaction behavior.
- SecureWP Pro → `Logs` is not directly updated by this class; verify by endpoint behavior.

---

## 6) General Rate Limiting (Contact/Registration) + NEW: Admin Interface Security (v2.0.0)

Code: `includes/class-general-rate-limiting.php`, `admin/js/admin.js`, `admin/css/admin.css`

What it does:
- Hooks into Contact Form 7 submissions (`wpcf7_before_send_mail`).
- Hooks into Fluent Forms validation (`fluentform/validation_errors`).
- **v2.0.0**: Hooks into Elementor Pro Forms validation (`elementor_pro/forms/validation`).
- **v2.0.0**: Provides honeypot spam protection for Elementor Pro Forms.
- Hooks into user registration attempts (`register_post`).
- **NEW v2.0.0**: Implements admin interface security with unsaved changes protection.
- **NEW v2.0.0**: Visual feedback system for modified form fields.
- **NEW v2.0.0**: Professional dialog system for navigation with unsaved changes.
- Uses `SecureWP_Pro_Lockout_Manager` to track and enforce lockouts per IP with separate buckets per plugin:
  - CF7 bucket: `contact_form_cf7`
  - Fluent Forms bucket: `contact_form_fluentforms`
  - **Elementor Pro bucket (v2.0.0)**: `contact_form_elementor`
  - Registration bucket: `registration`

Settings to check:
- "General Rate Limiting" enabled (Security Features tab).
- Under "Contact Form Plugins" (Security Features tab):
  - Enable "Contact Form 7" to enforce CF7 rate limits.
  - Enable "Fluent Forms" to enforce Fluent Forms rate limits.
  - **Enable "Elementor Pro Forms" to enforce Elementor Pro rate limits (v2.0.0).**
  - **Enable "Elementor Pro Forms Honeypot Protection" for spam detection (v2.0.0).**
- **NEW v2.0.0**: Admin interface automatically enables unsaved changes protection.
- Contact form plugin(s) have at least one form embedded on a page.
- Registration allowed (Settings → General → "Anyone can register").

Test steps:
1. Contact Form 7 – submit rapidly (e.g., >5–10 submissions from same IP):
   - Expected: Eventually blocked by `wp_die('Too many form submissions...')` with HTTP 403.
   - Logs: `general_rate_limiting: blocked`; Lockouts table entry with `event_type = 'contact_form_cf7'`.
2. Fluent Forms – submit rapidly (e.g., >5–10 submissions from same IP):
   - Expected: A global validation error appears on the form: "Too many form submissions. Please try again later." Submission is prevented.
   - Logs: `general_rate_limiting: blocked`; Lockouts table entry with `event_type = 'contact_form_fluentforms'`.
3. **Elementor Pro Forms – submit rapidly (e.g., >5–10 submissions from same IP) (v2.0.0):**
   - Expected: A validation error appears on the form: "Too many form submissions. Please try again later." Submission is prevented.
   - Logs: `general_rate_limiting: blocked`; Lockouts table entry with `event_type = 'contact_form_elementor'`.
4. **Elementor Pro Honeypot Protection – fill hidden honeypot field (v2.0.0):**
   - Add a hidden field with ID "comments", "phone_number", "address", "email_confirm", or "human_check" to your Elementor Pro form
   - Fill the hidden field and submit
   - Expected: Submission blocked with spam detection message
   - Logs: `general_rate_limiting: honeypot_detected`
5. **NEW: Admin Interface Security Testing (v2.0.0):**
   - Navigate to SecureWP Pro → Settings and modify any form field
   - Try to click on another tab (Overview, Lockouts, etc.)
   - Expected: Professional dialog appears with three options: "Save & Continue", "Discard Changes", "Stay Here"
   - Verify modified fields show visual highlighting (red border and background tint)
   - Test "Save & Continue" automatically saves and navigates
   - Test "Discard Changes" discards changes and navigates
   - Test "Stay Here" cancels navigation
6. Registration – attempt repeated registrations (even with errors):
   - Expected: Errors include a `rate_limit_exceeded` message.
   - Logs: `general_rate_limiting: blocked`; Lockouts table entry with `event_type = 'registration'`.

Verification:
- SecureWP Pro → `Logs` shows `general_rate_limiting` events with your IP.
- Lockouts table entries for `contact_form_cf7`, `contact_form_fluentforms`, `contact_form_elementor`, and/or `registration`.

---

## 7) Notifications

Code: `includes/class-notifier.php`, used by `class-lockout-manager.php` and XML-RPC/Cron classes.

What it does:
- Sends emails to admin on significant events (lockouts, permanent lockout, blocked XML-RPC/cron when enabled).

Settings to check (Notifications tab):
- “Enable Admin Notifications” enabled.
- Notification email set correctly.
- Specific toggles like “XML-RPC Attacks” and “Cron Security” if applicable.

Test steps:
- Trigger any of the above blocks/lockouts.
- Expected: Email received with details; logs include `notification: lockout_triggered` or `permanent_lockout_triggered`.

Verification:
- Check inbox for admin email.
- SecureWP Pro → `Logs` for corresponding `notification` entries.

---

## 8) Database-Level Verification

Tables:
- `{wp_prefix}securewp_pro_logs` (fields: `event_type`, `event_action`, `details`, `ip_address`, `created`).
- `{wp_prefix}securewp_pro_lockouts` (fields include `ip_address`, `event_type`, `failure_count`, `lockout_expiry`, `permanent`).

Suggested queries (replace `{wp_prefix}` with your actual prefix):
```sql
SELECT * FROM {wp_prefix}securewp_pro_logs ORDER BY created DESC LIMIT 50;
SELECT * FROM {wp_prefix}securewp_pro_lockouts ORDER BY modified DESC LIMIT 50;
```

---

## 9) Expected Results Summary (per Feature)

- Cron security: Unauthorized or excessive access → 403 with logs; authorized header → allowed and logged.
- XML-RPC protection: `xmlrpc.php` 403 when fully blocked; suspicious methods rate-limited (429) or blocked with logs.
- Login: Multiple failures → lockout with progressive durations; UI shows lockout error; DB records created; success clears lockout.
- Password reset: Multiple requests → lockout; flow blocked; logs show request/blocked.
- REST API: Users endpoint restricted; non-admins cannot list or see redacted user data.
- General rate limiting: CF7, Fluent Forms, Elementor Pro, and Registration throttled; blocked with logs and lockout records (separate buckets for CF7, Fluent Forms, and Elementor Pro with honeypot detection).
- Notifications: Emails sent on lockouts/blocks when enabled.

---

## 10) Troubleshooting & Edge Cases

- Not seeing any logs? Verify tables exist and plugin activated. Check `WP_DEBUG_LOG` for DB errors.
- Behind reverse proxies/CDNs: IP detection uses server headers; ensure correct header forwarding and that your CDN IPs aren’t being logged as clients.
- Alternative cron (`ALTERNATE_WP_CRON`): Use the `Cron Secret Key` URL parameter test as described in the settings (Advanced tab).
- Mobile apps/Jetpack require XML-RPC; do not enable “Block XML-RPC Completely” if you rely on them.
- High false positives on CF7/registration: Adjust lockout timings in Settings → Lockout.

---

## 11) Appendix – Quick Curl Commands

Replace `your-site.example` and headers/keys with your actual values.

- XML-RPC basic access test:
```bash
curl -i https://your-site.example/xmlrpc.php
```

- XML-RPC pingback attempt:
```bash
curl -i -X POST https://your-site.example/xmlrpc.php \
  -H 'Content-Type: text/xml' \
  --data '<methodCall><methodName>pingback.ping</methodName><params></params></methodCall>'
```

- XML-RPC burst to trigger 429:
```bash
for i in $(seq 1 15); do
  curl -s -o /dev/null -w "%{http_code}\n" -X POST https://your-site.example/xmlrpc.php \
    -H 'Content-Type: text/xml' \
    --data '<methodCall><methodName>system.multicall</methodName><params></params></methodCall>'
  sleep 2
done
```

- Cron without/with header:
```bash
curl -i https://your-site.example/wp-cron.php
curl -i -H "X-SecureWP-Cron: <your-correct-key>" https://your-site.example/wp-cron.php
```

- REST API users endpoint:
```bash
curl -i https://your-site.example/wp-json/wp/v2/users
```
 
- REST API users endpoint (admin via Application Passwords):
```bash
curl -i -u "admin:APP_PASSWORD_HERE" https://your-site.example/wp-json/wp/v2/users
```

- View recent logs (admin UI): SecureWP Pro → Logs (submenu or tab; both open the tabbed UI and land on the Logs tab).
- Manage lockouts: SecureWP Pro → Lockouts (submenu or tab; both open the tabbed UI and land on the Lockouts tab).

---

This test plan maps directly to the plugin code paths and options referenced above, ensuring coverage of each security feature and clear pass/fail signals via HTTP responses, admin UI, and database records.
