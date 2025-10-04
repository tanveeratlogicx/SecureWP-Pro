# SecureWP Pro — User Guide v2.0.0

Welcome to SecureWP Pro version 2.0.0. This guide will help you install, configure, and get the most out of the plugin's enhanced protections: rate limiting, XML‑RPC hardening, REST API privacy, cron security, logging, lockouts, admin notifications, comprehensive form protection, and the revolutionary new Admin Interface with enhanced user experience features.


## Overview

SecureWP Pro protects your WordPress site against common abuse patterns and reconnaissance:

- Login and password reset rate limiting
- Contact/registration form rate limiting (Contact Form 7, Fluent Forms, and Elementor Pro Forms supported)
- Advanced honeypot spam protection for Elementor Pro Forms
- XML‑RPC protection (complete block, suspicious methods, rate limiting)
- REST API hardening for user endpoints
- WP‑Cron security (header/secret auth, IP allowlist, rate limiting)
- Progressive lockout engine with automatic escalation
- Detailed security logging and optional email notifications
- Enterprise-grade security hardening against 19+ vulnerabilities
- **NEW in v2.0.0**: Enhanced Admin Interface with unsaved changes protection and intuitive visual feedback


## Requirements

- WordPress 5.4+
- Administrator access to configure settings
- (Optional) Email delivery set up for notifications


## Installation

1. Upload the plugin folder `securewp-pro` to `wp-content/plugins/`.
2. Activate "SecureWP Pro" via WordPress → Plugins.
3. Go to Admin → SecureWP Pro to review the dashboard and settings.


## Getting Started

Open Admin → `SecureWP Pro` and use the top tabs or left submenu:

- Overview
- Settings
- Lockouts
- Logs
- Statistics

The Settings page itself has vertical sections (Security Features, Lockout Settings, Notifications, Advanced).

**NEW in v2.0.0**: The admin interface now includes:
- **Unsaved Changes Protection**: Prevents accidental navigation away from unsaved settings
- **Visual Feedback System**: Modified fields are highlighted with visual indicators
- **Professional Dialog System**: User-friendly confirmation dialogs for navigation with unsaved changes
- **Mobile-Optimized Interface**: Responsive design works perfectly on all devices


## Key Features and How to Configure Them

### 0) Enhanced Admin Interface (NEW in v2.0.0)

**What’s New:**
- **Unsaved Changes Detection**: The system automatically tracks any modifications to form fields
- **Visual Field Highlighting**: Modified fields are highlighted with a subtle red border and background tint
- **Tab Navigation Prevention**: Prevents switching tabs when unsaved changes exist
- **Smart Dialog System**: Professional confirmation dialog with three options:
  - **Save & Continue**: Saves changes and navigates to selected tab
  - **Discard Changes**: Discards unsaved changes and navigates
  - **Stay Here**: Cancels navigation to review changes
- **Cross-Browser Compatible**: Works on all modern browsers with fallback support
- **Mobile Responsive**: Optimized for tablets and smartphones
- **Keyboard Accessible**: Full keyboard navigation support

**How It Works:**
- Simply start editing any field in the admin interface
- Notice the subtle visual feedback indicating the field has been modified
- Try to navigate to another tab - you’ll see a professional dialog asking what to do
- Choose your preferred action and the system handles the rest

**Benefits:**
- Prevents accidental loss of configuration changes
- Provides clear visual feedback about unsaved work
- Streamlines the admin workflow with intelligent navigation
- Reduces user frustration from lost work

### 1) WP‑Cron Security

- Toggle: Settings → Security Features → "WP‑Cron Security"
- What it does:
  - Requires a secret for calling `wp-cron.php` (via header or, if enabled, `?secret=` param)
  - Rate limits excessive hits to cron
  - Optional allowlist for trusted IPs (Settings → Advanced → Authorized Cron IPs)
- Where to find the secret:
  - Settings → Advanced → "Cron Secret Key"
  - Use the Generate, Show/Hide, and Copy controls as needed

Tips:
- If you define `ALTERNATE_WP_CRON` in `wp-config.php`, you can trigger cron via `wp-cron.php?secret=YOUR_KEY` directly.


### 2) XML‑RPC Protection

- Toggles: Settings → Security Features → "XML‑RPC Protection", "Block XML‑RPC Completely", "Block Suspicious Methods"
- What it does:
  - Optionally blocks `xmlrpc.php` entirely
  - Removes/blocks dangerous XML‑RPC methods and rate limits request bursts


### 3) REST API Hardening (Users Endpoint)

- Toggle: Settings → Security Features → "REST API Security"
- What it does:
  - Restricts unauthenticated access to `/wp/v2/users`
  - Redacts sensitive fields for non‑admin viewers


### 4) Login Rate Limiting

- Toggle: Settings → Security Features → "Login Rate Limiting"
- What it does:
  - Rate limits failed logins per IP with progressive lockouts
- Tip:
  - A successful login clears the lockout for that IP


### 5) Password Reset Rate Limiting

- Toggle: Settings → Security Features → "Password Reset Rate Limiting"
- What it does:
  - Throttles the lost password flow to prevent abuse


### 6) General Rate Limiting (Contact + Registration)

- Toggles: Settings → Security Features
  - "General Rate Limiting" (master)
  - Under "Contact Form Plugins":
    - "Enable rate limit for Contact Form 7"
    - "Enable rate limit for Fluent Forms"
    - "Enable rate limit for Elementor Pro Forms"
    - "Enable Elementor Pro Forms Honeypot Protection"
- What it does:
  - CF7 (Contact Form 7): blocks rapid submissions with HTTP 403 and an error message
  - Fluent Forms: blocks rapid submissions by injecting a global validation error on the form
  - Elementor Pro Forms: blocks rapid submissions with validation error message
  - Registration: prevents repeated/automated account creation attempts
- Buckets (independent counters per feature):
  - CF7: `contact_form_cf7`
  - Fluent Forms: `contact_form_fluentforms`
  - Elementor Pro: `contact_form_elementor`
  - Registration: `registration`

**NEW: Elementor Pro Forms Honeypot Protection**
- Automatically detects and blocks spam submissions
- Looks for hidden fields: "website", "url", "honeypot", "bot_check", "company_url"
- Zero configuration required - works automatically with existing forms
- Add honeypot field instructions provided in admin interface

Note:
- You can enable CF7, Fluent Forms, and Elementor Pro independently. Each has its own rate‑limit bucket.


## Lockout Settings (Progressive Timings)

- Location: Settings → Lockout Settings
- Controls the escalation window for repeated failures/abuse from the same IP:
  - Initial → Second → Third → Fourth → Fifth → Max → Permanent
- Defaults (seconds): 0, 30, 60, 300, 600, 4800 (80 minutes), 86400 (24 hours)
- When the max threshold is reached, subsequent events may yield a permanent lockout until manually unlocked.


## Logs and Lockouts

- Logs: Admin → SecureWP Pro → Logs
  - Shows recorded events by type (e.g., `login_rate_limiting`, `general_rate_limiting`, `xmlrpc_security`, `cron_security`)
  - You can export or clear logs
- Lockouts: Admin → SecureWP Pro → Lockouts
  - View, unlock, or delete lockout records per IP and event type


## Notifications (Email)

- Location: Settings → Notifications
- Options:
  - Enable Admin Notifications and set a recipient
  - Choose which events to notify (Login Failures, IP Lockouts, XML‑RPC, Cron)
- Example subjects:
  - "SecureWP Pro: Lockout triggered for IP <IP>"
  - "SecureWP Pro: Permanent lockout triggered for IP <IP>"
  - "[SecureWP Pro] XML‑RPC Access Blocked"
  - "[SecureWP Pro] Cron Access Blocked"


## Advanced Settings

- Authorized Cron IPs: allowlist trusted IPs/ranges (one per line, supports CIDR)
- Cron Secret Key: regenerate key (AJAX), Show/Hide, Copy helpers
- Log Retention: choose retention period or never delete
- Debug Mode: verbose logging for troubleshooting


## Best Practices

- Keep General Rate Limiting enabled, and enable the plugins you actively use (CF7, Fluent Forms)
- Adjust Lockout Settings to balance security with usability for your audience
- Use Authorized Cron IPs for external uptime monitors or schedulers that must hit `wp-cron.php`
- Monitor Logs and Lockouts periodically and tune thresholds as needed
- Enable notifications for visibility into critical events


## Troubleshooting

- Not seeing logs? Ensure the plugin is active and DB tables exist; check `wp-content/debug.log` if `WP_DEBUG_LOG` is enabled
- Behind a CDN/proxy? Confirm real client IPs are forwarded in `HTTP_X_FORWARDED_FOR` and not masked by your edge provider
- CF7 submissions always blocked?
  - Check if you reached a lockout window; unlock under Lockouts or wait for expiry
  - Confirm "General Rate Limiting" and "Contact Form 7" toggles are configured as intended
- Fluent Forms errors not showing?
  - Ensure Fluent Forms is active and the per‑plugin toggle is enabled
  - Verify that the form displays global validation errors (default Fluent Forms behavior)
- **Elementor Pro Forms issues?**
  - Ensure Elementor Pro is active and the "Elementor Pro Forms" toggle is enabled
  - Check if you've reached a lockout; unlock via Lockouts page or wait for expiry
  - For honeypot protection: add a hidden field with ID "website", "url", "honeypot", "bot_check", or "company_url"
  - Honeypot field CSS should be: `position:absolute;left:-9999px;opacity:0;`
- Cron tests not working?
  - Verify the header name `X-SecureWP-Cron` and value (Cron Secret Key)
  - If using `?secret=`, ensure `ALTERNATE_WP_CRON` is true in `wp-config.php`


## Frequently Asked Questions (FAQ)

- Can I have different thresholds per feature?
  - Not yet via the UI. All features share the same progressive lockout timings. Contact us if you need per‑feature thresholds.

- Will enabling XML‑RPC Complete Block break mobile apps/Jetpack?
  - Yes, it can. Only enable if you do not rely on XML‑RPC‑based clients.

- Does a successful login clear all lockouts?
  - It clears the login failure lockout for the IP. Other buckets (e.g., contact forms) are independent.

- Where can I see what was blocked?
  - Check SecureWP Pro → Logs. Use the Lockouts page to see current lockout status for your IP.

- **How does Elementor Pro Forms honeypot protection work?**
  - The plugin automatically detects honeypot fields with common IDs ("website", "url", "honeypot", "bot_check", "company_url")
  - If a submission fills these hidden fields, it's blocked as spam
  - No configuration needed - just add a hidden field with the right ID to your form

- **What's the difference between the three form plugins?**
  - Contact Form 7: Returns HTTP 403 error when rate limited
  - Fluent Forms: Shows validation error message on the form
  - Elementor Pro: Shows validation error and includes honeypot spam protection


## Reference

- Main plugin bootstrap: `securewp-pro.php`
- Important classes in `includes/`:
  - `class-cron-security.php`
  - `class-xmlrpc-security.php`
  - `class-login-rate-limiting.php`
  - `class-rest-api-security.php`
  - `class-password-reset-rate-limiting.php`
  - `class-general-rate-limiting.php`
  - `class-lockout-manager.php`
  - `class-logger.php`
  - `class-notifier.php`
- Admin UI partials: `admin/partials/`


## Change History (Summary)

- **2025-09-24 (Version 2.0.0)**
  - **MAJOR**: Revolutionary Admin Interface with unsaved changes protection
  - **MAJOR**: Enhanced visual feedback system with field modification indicators
  - **MAJOR**: Professional dialog system for navigation with unsaved changes
  - **MAJOR**: Cross-browser compatibility with mobile-responsive design
  - **MAJOR**: Smart tab navigation prevention with three-option dialog
  - **FEATURE**: Auto-redirect functionality after form saves
  - **FEATURE**: Keyboard accessibility with full navigation support
  - **ENHANCEMENT**: CSS animations and transitions for professional UX
  - **ENHANCEMENT**: Mobile-optimized interface for tablets and smartphones
  - **TECHNICAL**: Advanced JavaScript form change detection system
  - **TECHNICAL**: Session storage integration for navigation state management
  - **TECHNICAL**: Comprehensive CSS fallback system for older browsers

- **2025‑09‑24 (Version 1.1)**
  - Added Elementor Pro Forms rate limiting with distinct bucket (`contact_form_elementor`)
  - Implemented advanced honeypot spam protection for Elementor Pro Forms
  - Comprehensive security hardening against 19+ vulnerabilities including SQL injection, CSRF, and XSS
  - Enhanced input validation and authorization controls
  - Updated vulnerability protection documentation
  - Fixed critical PHP syntax error causing site crashes
  - Improved admin interface with enhanced save functionality

- 2025‑09‑19
  - Added Fluent Forms rate limiting with a distinct bucket (`contact_form_fluentforms`)
  - Separated CF7 into its own bucket (`contact_form_cf7`)
  - Added per‑plugin toggles under Settings → Security Features → Contact Form Plugins
  - Updated docs and examples

- 2025‑09‑18
  - Added AJAX‑based Cron Secret Key management (Generate/Show/Copy)
  - Synchronized submenu items with tabbed UI
  - Documentation improvements


## Support

If you need help or want to request features like per‑form buckets or per‑feature thresholds, please reach out to your site administrator or the plugin maintainer.
