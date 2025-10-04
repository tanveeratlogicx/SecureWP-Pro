Updated
*******
SecureWP Pro/
├── Main Plugin Class (securewp-pro.php)
├── Security Modules:
│   ├── Cron Security (DoS protection)
│   ├── XML-RPC Security (pingback protection)
│   ├── Login Rate Limiting
│   ├── REST API Security (user endpoint protection)
│   ├── Password Reset Rate Limiting
│   └── General Rate Limiting (forms)
├── Core Systems:
│   ├── Logger (comprehensive logging)
│   ├── Lockout Manager (progressive lockouts)
│   └── Notifier (admin email alerts)
└── Admin Interface:
    ├── Settings Page (feature controls)
    ├── Lockouts Page (IP management)
    └── Logs Page (security monitoring)

---------------------------------------------------------
securewp-pro/
├── securewp-pro.php
├── includes/
│   ├── class-cron-security.php
│   ├── class-xmlrpc-security.php
│   ├── class-login-rate-limiting.php
│   ├── class-rest-api-security.php
│   ├── class-password-reset-rate-limiting.php
│   ├── class-general-rate-limiting.php
│   ├── class-lockout-manager.php
│   ├── class-logger.php
│   └── class-notifier.php
├── admin/
│   ├── css/
│   │   └── admin.css
│   ├── js/
│   │   └── admin.js
│   └── partials/
│       ├── admin-tabs.php
│       ├── tab-overview.php
│       ├── tab-settings.php
│       ├── tab-lockouts.php
│       ├── tab-logs.php
│       └── tab-statistics.php
├── uninstall.php
├── folder-structure.md
├── SECURITY_TEST_REPORT.md
├── TEST_GUIDE_SKELETON.md
├── EVIDENCE_URLS.md
├── EVIDENCE_URLS_RAW.txt
├── Prompt.md

---

## Changelog (structure and admin UI highlights)

- AJAX-based Cron Secret Key generation implemented in `securewp-pro.php` + `admin/js/admin.js`.
  - No page reload; key field supports Show/Hide and Copy actions.
- Submenu-to-tab sync: `admin/partials/admin-tabs.php` maps `page=securewp-pro-lockouts` and `page=securewp-pro-logs` to the proper tabs.
- Legacy standalone admin pages deprecated and removed from active structure:
  - Replaced by unified tabbed UI: `admin/partials/admin-tabs.php` with `tab-*.php` files.
- Documentation updates:
  - `SECURITY_TEST_REPORT.md` and `TEST_GUIDE_SKELETON.md` updated to reflect AJAX key generation and submenu/tab sync.
  - Added client-facing evidence links: `EVIDENCE_URLS.md` and `EVIDENCE_URLS_RAW.txt`.