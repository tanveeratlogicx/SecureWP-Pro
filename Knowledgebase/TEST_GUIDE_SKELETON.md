# SecureWP Pro v1.1 – Deployment-Agnostic Test Guide (Skeleton)

Use this template to validate SecureWP Pro on any environment. Replace the variables in the Variables section before running tests. Keep this file in version control and update only the Variables block per environment.

Reference: For quick, client-facing endpoints and curl examples, see `EVIDENCE_URLS.md`.

## Quick Links
- [Evidence URLs (Client-Facing)](./EVIDENCE_URLS.md)
- [Security Test Report](./SECURITY_TEST_REPORT.md)
- [Folder Structure & Changelog](./folder-structure.md)

## Variables (Edit for each environment)

- BASE_URL: {{BASE_URL}}            
  Example: https://example.com
- ADMIN_URL: {{ADMIN_URL}}          
  Example: https://example.com/wp-admin
- CRON_SECRET_KEY: {{CRON_SECRET_KEY}}  
  Example: AbCdEfGhIjKlMnOpQrStUvWxYz012345
- YOUR_IP: {{YOUR_IP}}              
  Example: 203.0.113.10 or 203.0.113.0/24

Optional toggles in wp-config.php:
- ALTERNATE_WP_CRON: {{ALTERNATE_WP_CRON}} (true/false)

Note: Replace all {{...}} placeholders above before testing.

---

## 1) WP‑Cron Security

Feature files: `includes/class-cron-security.php`, UI: Settings → Advanced

- Preconditions:
  - “WP‑Cron Security” enabled.
  - “Cron Secret Key” set in the Advanced tab.
  - Optional: Add YOUR_IP (supports CIDR) in “Authorized Cron IPs”.
  - You can regenerate the key via the “Generate New Key” button (AJAX, no page reload). The UI provides Show/Hide and Copy controls.

- Tests:
  - No header (expect 403):
    - curl -i {{BASE_URL}}/wp-cron.php
  - Wrong header (expect 403):
    - curl -i -H "X-SecureWP-Cron: WRONG" {{BASE_URL}}/wp-cron.php
  - Correct header (expect 200):
    - curl -i -H "X-SecureWP-Cron: {{CRON_SECRET_KEY}}" {{BASE_URL}}/wp-cron.php
  - Authorized IP (expect 200 without header):
    - curl -i {{BASE_URL}}/wp-cron.php
  - Alternate (if ALTERNATE_WP_CRON=true):
    - curl -i "{{BASE_URL}}/wp-cron.php?secret={{CRON_SECRET_KEY}}"
  - Rate limit (>10/min → 403): run ~12 requests within 60s.

- Verify:
  - Admin → SecureWP Pro → Logs: `cron_security` (`access`/`blocked`).

---

## 2) XML‑RPC Protection

Feature file: `includes/class-xmlrpc-security.php`, UI: Settings → Security Features

- Preconditions: Enable “XML‑RPC Protection”. For complete block, also enable “Block XML‑RPC Completely”.

- Tests:
  - Complete block (expect 403):
    - curl -i {{BASE_URL}}/xmlrpc.php
  - Suspicious methods blocked (if not fully blocked):
    - curl -i -X POST {{BASE_URL}}/xmlrpc.php -H "Content-Type: text/xml" --data '<methodCall><methodName>pingback.ping</methodName><params></params></methodCall>'
  - Rate limit (eventual 429):
    - Repeat `system.multicall` requests ~15 times with 2s gaps.

- Verify:
  - Logs: `xmlrpc_security` (`request`, `suspicious_method`, `pingback_attempt`, `rate_limited`, `blocked`).

---

## 3) Login Rate Limiting

Feature files: `includes/class-login-rate-limiting.php`, `includes/class-lockout-manager.php`

- Preconditions: Enable “Login Rate Limiting” and configure lockout times under Lockout Settings.

- Tests:
  - Multiple failed logins at {{BASE_URL}}/wp-login.php using a known username.
  - Expect lockout message after thresholds. Then perform a successful login from same IP.

- Verify:
  - Logs: `login_rate_limiting` (`failed_attempt`, `blocked`, `successful_login`).
  - Lockouts: entries for `event_type = login_failure`.

---

## 4) Password Reset Rate Limiting

Feature file: `includes/class-password-reset-rate-limiting.php`

- Preconditions: Enable “Password Reset Rate Limiting”.

- Tests:
  - Submit multiple requests at {{BASE_URL}}/wp-login.php?action=lostpassword
  - Expect eventual block of reset flow.

- Verify:
  - Logs: `password_reset_rate_limiting` (`request`, `blocked`).

---

## 5) REST API Hardening (Users)

Feature file: `includes/class-rest-api-security.php`

- Preconditions: Enable “REST API Security”. Prepare one admin and one non-admin user.

- Tests:
  - Unauthenticated:
    - curl -i {{BASE_URL}}/wp-json/wp/v2/users (expect not allowed)
  - Authenticated admin: list allowed.
    - Example using Application Passwords:
      - curl -i -u "admin:APP_PASSWORD_HERE" {{BASE_URL}}/wp-json/wp/v2/users
  - Authenticated non-admin: list blocked or data redacted (`email`, `url`, `capabilities`, `extra_capabilities`).

- Verify:
  - Inspect response payloads for permission enforcement and redaction.

---

## 6) General Rate Limiting (Contact + Registration)

Feature file: `includes/class-general-rate-limiting.php`

- Preconditions:
  - Enable "General Rate Limiting".
  - In Settings → Security Features → "Contact Form Plugins", enable the plugins you want protected:
    - Contact Form 7
    - Fluent Forms
    - **Elementor Pro Forms (v1.1 NEW)**
    - **Elementor Pro Forms Honeypot Protection (v1.1 NEW)**
  - Ensure at least one form is embedded/published for the enabled plugin(s).
  - Registration enabled at {{ADMIN_URL}}/options-general.php (Anyone can register).

- Tests:
  - Contact Form 7: rapid submissions → expect HTTP 403 block ("Too many form submissions. Please try again later.").
  - Fluent Forms: rapid submissions → expect an on-form global validation error ("Too many form submissions. Please try again later."); submission prevented.
  - **Elementor Pro Forms: rapid submissions → expect validation error ("Too many form submissions. Please try again later."); submission prevented (v1.1).**
  - **Elementor Pro Honeypot: add hidden field with ID "comments", "phone_number", "address", "email_confirm", or "human_check", fill it and submit → expect spam detection block (v1.1).**
  - Registration: repeated attempts → expect error mentioning rate limit.

- Verify:
  - Logs: `general_rate_limiting` (`blocked`, `honeypot_detected`).
  - Lockouts: event types `contact_form_cf7`, `contact_form_fluentforms`, `contact_form_elementor`, and/or `registration`.

---

## 7) Notifications

Feature file: `includes/class-notifier.php`

- Preconditions: Enable notifications in Settings → Notifications and set email.

- Tests:
  - Trigger any of the above blocks/lockouts.

- Verify:
  - Admin email receives expected alerts.
  - Logs: `notification` (`lockout_triggered`, `permanent_lockout_triggered`).

---

## Admin Utilities (for testers)

- Regenerate Cron Secret Key (AJAX, no page reload):
  - {{ADMIN_URL}} → SecureWP Pro → Settings → Advanced → “Generate New Key” (use Show/Hide and Copy as needed)
- Clean Old Logs:
  - {{ADMIN_URL}} → SecureWP Pro → Settings → Advanced → “Clean Now”
- Reset All Settings:
  - {{ADMIN_URL}} → SecureWP Pro → Settings → Advanced → “Reset to Defaults”
- Clear All Logs (Quick Action):
  - {{ADMIN_URL}} → SecureWP Pro → Overview → “Clear All Logs”

Notes:
- The “Lockouts” and “Logs” submenu items or their corresponding horizontal tabs open the same tabbed UI and land on their respective tabs.

---

## Pass/Fail Summary (fill after tests)

- Cron Security: PASS / FAIL — Notes: __________________________
- XML‑RPC Protection: PASS / FAIL — Notes: ______________________
- Login Rate Limiting: PASS / FAIL — Notes: _____________________
- Password Reset Rate Limiting: PASS / FAIL — Notes: ____________
- REST API Hardening: PASS / FAIL — Notes: ______________________
- General Rate Limiting: PASS / FAIL — Notes: ___________________
- Notifications: PASS / FAIL — Notes: ___________________________
