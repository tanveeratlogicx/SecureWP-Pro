# SecureWP Pro v2.0.0 – Evidence URLs (Client-Facing)

Replace placeholders before sharing with the client:
- BASE_URL: https://your-site.example
- CRON_SECRET_KEY: <paste from Admin → SecureWP Pro → Settings → Advanced>

## Quick Links
- [Security Test Report](./SECURITY_TEST_REPORT.md)
- [Deployment-Agnostic Test Guide](./TEST_GUIDE_SKELETON.md)
- [Folder Structure & Changelog](./folder-structure.md)

## WP‑Cron Security
- Unauthorized (should be blocked → 403):
  - BASE_URL/wp-cron.php
- Authorized via secret (ALTERNATE_WP_CRON must be enabled; should run cron → 200):
  - BASE_URL/wp-cron.php?secret=CRON_SECRET_KEY
- Invalid secret when ALTERNATE_WP_CRON is enabled (should be blocked → 403):
  - BASE_URL/wp-cron.php?secret=WRONG_KEY
- Header-based authorization (no ALTERNATE_WP_CRON; should be allowed → 200 when header matches):
  - curl -i -H "X-SecureWP-Cron: CRON_SECRET_KEY" "BASE_URL/wp-cron.php"
- Rate limiting (more than 10 requests per minute → 429 Too Many Requests):
  - Repeatedly request BASE_URL/wp-cron.php more than 10x/min from same IP
- Authorized IPs allowlist:
  - Requests from IPs configured in `SecureWP Pro → Settings → Advanced → Authorized Cron IPs` are allowed. Others are blocked unless header/secret is valid.

## XML‑RPC Protection
- Complete block enabled (Settings → XML‑RPC → "Block XML‑RPC Completely"): direct access should be blocked → 403
  - GET: BASE_URL/xmlrpc.php
  - POST: BASE_URL/xmlrpc.php

- Suspicious methods blocked when "Block Suspicious Methods" is enabled (default true) → 429
  - Example (system.multicall):
    - curl -i -X POST -H "Content-Type: text/xml" \
      --data '<methodCall><methodName>system.multicall</methodName><params></params></methodCall>' \
      "BASE_URL/xmlrpc.php"
  - Example (pingback.ping):
    - curl -i -X POST -H "Content-Type: text/xml" \
      --data '<methodCall><methodName>pingback.ping</methodName><params></params></methodCall>' \
      "BASE_URL/xmlrpc.php"

- XML‑RPC rate limiting (more than 10 requests per minute from same IP → 429):
  - Rapidly POST to BASE_URL/xmlrpc.php >10x/min

- XML‑RPC flood detection (more than 20 requests in 5 minutes → subsequent access blocked → 403):
  - After 20+ requests/5min, direct access to BASE_URL/xmlrpc.php will return 403 until the window expires.

- Notes:
  - When XML‑RPC is not completely blocked, WordPress may still require valid credentials for certain methods; authentication failures may be returned as XML‑RPC faults (not plugin blocks).
  - The plugin removes pingback methods from `xmlrpc_methods` to reduce abuse.

## REST API Hardening (Users)
- Users list (unauthenticated; should be blocked → 401/403):
  - BASE_URL/wp-json/wp/v2/users
- Users list (logged-in Administrator; should be allowed → 200):
  - BASE_URL/wp-json/wp/v2/users
- User object filtering for non-admins:
  - When a non-admin accesses individual user objects (where permitted), sensitive fields like `email`, `url`, and capabilities are removed.

## Login Rate Limiting
- Login page (repeated failed attempts lead to incremental lockouts):
  - BASE_URL/wp-login.php
- Evidence/expected behavior:
  - Multiple failed logins from same IP will be logged and eventually blocked temporarily. During lockout, authentication returns an error such as: "Too many failed login attempts. Please try again later."
  - Successful login clears existing login lockouts for the IP.

## Password Reset Rate Limiting
- Lost password page (repeated requests from same IP may be blocked):
  - BASE_URL/wp-login.php?action=lostpassword
- Evidence/expected behavior:
  - Excessive reset attempts result in the reset being disallowed for that IP during the lockout window.

## General Rate Limiting
- Registration (if enabled on the site; repeated attempts may be blocked):
  - BASE_URL/wp-login.php?action=register
  - Evidence: Error message like "Too many registration attempts. Please try again later."
- Contact Form 7 submissions (repeated, rapid submissions from same IP → 403):
  - Submit any page containing a CF7 form multiple times quickly; when blocked, response is 403 with message: "Too many form submissions. Please try again later."
- Fluent Forms submissions (repeated, rapid submissions from same IP → blocked via validation error):
  - Submit any page containing a Fluent Form multiple times quickly; when blocked, a global validation error appears on the form: "Too many form submissions. Please try again later."
- **Elementor Pro Forms submissions (v1.1 - NEW)**:
  - Submit any page containing an Elementor Pro Form multiple times quickly; when blocked, a validation error appears: "Too many form submissions. Please try again later."
  - **Honeypot protection**: Forms with hidden fields ("comments", "phone_number", "address", "email_confirm", "human_check") automatically block spam

Notes:
- CF7, Fluent Forms, and Elementor Pro use separate rate-limit buckets: `contact_form_cf7`, `contact_form_fluentforms`, and `contact_form_elementor`.
- You can enable/disable per plugin under SecureWP Pro → Settings → Security Features → "Contact Form Plugins".
- Elementor Pro honeypot protection works automatically with zero configuration.

## Admin Dashboard (requires Administrator)
- Main dashboard:
  - BASE_URL/wp-admin/admin.php?page=securewp-pro
- Tabs (query parameter `tab`):
  - Overview: BASE_URL/wp-admin/admin.php?page=securewp-pro&tab=overview
  - Settings: BASE_URL/wp-admin/admin.php?page=securewp-pro&tab=settings
  - Lockouts: BASE_URL/wp-admin/admin.php?page=securewp-pro&tab=lockouts
  - Logs: BASE_URL/wp-admin/admin.php?page=securewp-pro&tab=logs
  - Statistics: BASE_URL/wp-admin/admin.php?page=securewp-pro&tab=statistics
- Submenu shortcuts (route to the same tabbed UI):
  - Lockouts: BASE_URL/wp-admin/admin.php?page=securewp-pro-lockouts
  - Logs: BASE_URL/wp-admin/admin.php?page=securewp-pro-logs

## Admin Actions (nonce-protected; for reference)
- Clear Logs (Admin Post):
  - BASE_URL/wp-admin/admin-post.php?action=securewp_pro_clean_old_logs
- Reset Settings (Admin Post):
  - BASE_URL/wp-admin/admin-post.php?action=securewp_pro_reset_settings
- Generate Cron Secret Key (AJAX; via admin UI button, requires nonce):
  - POST BASE_URL/wp-admin/admin-ajax.php?action=securewp_pro_generate_cron_key

## Tips for Testing
- Always replicate from the same IP when verifying rate limiting and lockouts.
- Wait for the lockout window to expire or clear via admin if you need to retest quickly.
- Ensure `ALTERNATE_WP_CRON` is defined and true in `wp-config.php` when testing the `?secret=` variant.

## Email Notifications (Evidence)
- Overview:
  - Notifications are sent on certain events if enabled in settings.
  - Configure at `SecureWP Pro → Settings`:
    - `securewp_pro_notify_admin` (master toggle)
    - `securewp_pro_notify_email` (recipient; defaults to Admin Email)
    - Feature toggles: `securewp_pro_notify_login_failures`, `securewp_pro_notify_lockouts`, `securewp_pro_notify_xmlrpc`, `securewp_pro_notify_cron`

- Lockout-related notifications (from `includes/class-notifier.php`):
  - On temporary lockout escalation:
    - Subject: "SecureWP Pro: Lockout triggered for IP <IP>"
  - On permanent lockout:
    - Subject: "SecureWP Pro: Permanent lockout triggered for IP <IP>"

- XML‑RPC blocked access (from `includes/class-xmlrpc-security.php`):
  - When direct access to `xmlrpc.php` is blocked (403), an email is sent if `securewp_pro_notify_admin` is enabled.
  - Subject: "[SecureWP Pro] XML-RPC Access Blocked"

- Cron blocked access (from `includes/class-cron-security.php`):
  - When unauthorized access to `wp-cron.php` is blocked (403), an email is sent if `securewp_pro_notify_admin` and `securewp_pro_notify_cron` are enabled.
  - Subject: "[SecureWP Pro] Cron Access Blocked"

- Evidence steps:
  - Trigger a lockout (e.g., repeated failed logins) and verify email receipt at the configured address.
  - Access `BASE_URL/xmlrpc.php` while XML‑RPC complete block is enabled and verify the "XML-RPC Access Blocked" email.
  - Attempt unauthorized `wp-cron.php` access (no header/secret) and verify the "Cron Access Blocked" email if cron notifications are enabled.

## Step-by-Step Test Plan (Checklist)
- [ ] XML‑RPC Complete Block returns 403 for GET and POST to `BASE_URL/xmlrpc.php`.
- [ ] XML‑RPC suspicious methods return 429 (e.g., `system.multicall`, `pingback.ping`).
- [ ] XML‑RPC rate limiting triggers 429 after >10 requests/min from same IP.
- [ ] XML‑RPC flood detection triggers 403 for subsequent access after >20 requests/5min.
- [ ] REST API users endpoint is blocked for unauthenticated request.
- [ ] REST API users endpoint works for Administrator; non-admin responses exclude sensitive fields.
- [ ] Login rate limiting blocks after multiple failures; successful login clears lockout.
- [ ] Password reset rate limiting blocks excessive requests from same IP.
- [ ] Registration attempts are limited; error shown when rate limited.
- [ ] Contact Form 7 rapid submissions are blocked with 403.
- [ ] Fluent Forms rapid submissions show a validation error and are blocked.
- [ ] **Elementor Pro Forms rapid submissions show validation error and are blocked (v2.0.0).**
- [ ] **Elementor Pro honeypot protection blocks spam when hidden fields are filled (v2.0.0).**
- [ ] **NEW: Admin interface tab navigation prevention with unsaved changes (v2.0.0).**
- [ ] **NEW: Visual feedback system shows modified fields with highlighting (v2.0.0).**
- [ ] **NEW: Professional dialog system for unsaved changes navigation (v2.0.0).**
- [ ] Cron unauthorized direct access returns 403.
- [ ] Cron header-based access succeeds with `X-SecureWP-Cron: CRON_SECRET_KEY`.
- [ ] Cron `?secret=CRON_SECRET_KEY` path works when `ALTERNATE_WP_CRON` is true; wrong key is blocked.
- [ ] Admin Dashboard tabs load correctly (Overview/Settings/Lockouts/Logs/Statistics).
- [ ] Security logs record blocked/failed/success events; visible under `Logs` tab.
- [ ] Lockout and (if enabled) XML‑RPC/Cron notification emails are received.
