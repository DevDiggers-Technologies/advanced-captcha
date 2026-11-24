# Advanced CAPTCHA

Spam protection for WordPress and WooCommerce forms: a no-keys built-in CAPTCHA, Cloudflare Turnstile, Google reCAPTCHA or hCaptcha, plus an invisible honeypot and a security dashboard.

* **Slug / text domain:** `advanced-captcha`
* **Version:** 1.1.0
* **Live Demo:** https://demo.devdiggers.com/woocommerce-advanced-captcha-free/
* **Pro:** https://devdiggers.com/product/woocommerce-advanced-captcha/
* **License:** GPLv3 or later

## Requirements

| | |
|---|---|
| WordPress | 6.5+ (tested up to 7.1) |
| PHP | 7.4+ |
| WooCommerce | Optional, 9.0+ (tested up to 11.2) |

## What it protects

* **WordPress:** login, registration, lost password, comments
* **WooCommerce:** My Account login, registration and lost password, classic checkout and the Checkout block (guests only or everyone), product reviews, pay for order, add payment method, account details, billing and shipping address
* **Form plugins:** Contact Form 7, WPForms, Fluent Forms
* **Custom forms:** `[ddwcac_captcha]` shortcode plus `ddwcac_check_captcha_response()`

## Free features

* Four providers: built-in (math or distorted text, server-side, no cookies), Cloudflare Turnstile, Google reCAPTCHA v2 Checkbox / v2 Invisible / v3, hCaptcha
* Live preview and key test on each provider tab
* Invisible honeypot with a signed minimum fill time
* Dashboard: verifications, bots stopped, block rate, attacking IPs, charts, top IPs, recent stops, protection checklist
* Verification log (30 days, purged daily by the `ddwcac_purge_logs` cron)
* Trusted roles and trusted IPs (exact, wildcard, CIDR, IPv6), visitor IP header for proxies
* Fail-open on provider outages; a hosted provider without both keys stays off
* Setup wizard, HPOS and Cart/Checkout blocks compatible

## AI assistants (MCP)

Ask Claude, ChatGPT, Cursor, VS Code or any MCP client about your protection in plain words: "Is my checkout protected?", "How many bots did we stop this week?", "Which IPs keep attacking the login?"

* Tools for protection status (provider and every protected form), a security report with the dashboard numbers, the verification log, the firewall (trusted IPs and repeat offenders) and a settings summary, plus a Weekly security review prompt
* Optional: add or remove trusted IPs, off until the owner switches it on, administrators only, logged under WooCommerce > Status > Logs when WooCommerce is active
* Secret keys are never returned
* Managed in DevDiggers Plugins > AI & MCP: one MCP server for every DevDiggers plugin, an on / off switch per plugin and per write tool, a Try panel and an activity log
* Needs WordPress 6.9+ (Abilities API) and the MCP Adapter (WooCommerce 10.3+ bundles it)

## Pro

[Advanced CAPTCHA Pro](https://devdiggers.com/product/woocommerce-advanced-captcha/) is an add-on that needs this plugin installed. When Pro is active this plugin stands down (`DDWCAC_Init` exists) and Pro runs the full feature set with the same options:

* Brute-force lockout, block list, live lockouts, country rules
* Card-testing shield, orders per IP per hour, disposable email guard
* Smart mode and reCAPTCHA v3 to v2 fallback
* Gravity Forms, Elementor Pro, Forminator, Formidable, BuddyPress, bbPress, Ultimate Member, Mailchimp for WordPress, DevDiggers Affiliates and MultiPOS
* Lockout alerts, weekly report, configurable log retention, settings import/export

## Developer notes

```php
// In a custom form handler.
$result = ddwcac_check_captcha_response( 'my_form' );
if ( ! $result['success'] ) {
	wp_die( esc_html( $result['message'] ) );
}
```

| Hook | Type | Purpose |
|---|---|---|
| `ddwcac_should_protect_request` | filter | Skip protection for a request |
| `ddwcac_verification_result` | filter | Change a verification result |
| `ddwcac_form_labels` | filter | Labels for form keys in the logs |
| `ddwcac_modify_admin_menu_capability` | filter | Capability for the admin screens |
| `ddwcac_loaded` | action | Plugin loaded |

MCP abilities live in `includes/integrations/mcp-integration.php` under the `captcha/` namespace. Add, change or remove one with the `ddwcac_mcp_abilities` filter.

Options are stored as `_ddwcac_<key>`; logs live in `{prefix}ddwcac_logs`.

## Build

```bash
npm install
npm run build          # webpack, text domain sync, .pot
npm run build:release  # zip in build/
php bin/test-free-boundary.php
```

## Support

* Forum: https://wordpress.org/support/plugin/advanced-captcha/
* Docs: https://docs.devdiggers.com/advanced-captcha/

## Changelog

### 1.1.0

* Added AI assistants (MCP): ask Claude, ChatGPT, Cursor, VS Code or any MCP client whether your forms are protected, how many bots were stopped, what the verification log and firewall show and how the plugin is set up, plus a Weekly security review prompt. Secret keys are never shared.
* Added an AI tool to add or remove trusted IPs. It stays off until you allow changes, and only administrators can use it.
* Updated the DevDiggers framework to 1.3.0, with a new DevDiggers Plugins > AI & MCP page: connect Claude, ChatGPT, Claude Code, Codex, Cursor, VS Code or any MCP client by signing in and clicking Allow in WordPress (nothing to copy), switch each plugin and each write tool on or off (write tools start off), try the tools and review an activity log.
* Dashboard cards show the previous period's number, and the Block Rate card shows how many of all attempts were stopped.
* Each recent stopped attempt on the dashboard opens the verification log filtered to that IP.
* Softer outline on the built-in CAPTCHA image and the integration logos.
* On edit screens the title bar with the Save button stays visible on desktop while you scroll.
* Fixed dashboard cards showing a 100% rise when the previous period had no activity.
* Fixed blank charts and unresponsive pickers on the plugin screens when a theme or another plugin replaces the shared Select2 script.

### 1.0.0 (Initial release)

* Built-in CAPTCHA (simple sum or distorted characters) that works without keys, cookies or third party calls.
* Cloudflare Turnstile, Google reCAPTCHA (v2 Checkbox, v2 Invisible, v3 with minimum score) and hCaptcha, with theme, size and language options.
* Live preview and Test button on every provider tab to confirm your keys work.
* Protection for WordPress login, registration, lost password and comments.
* Protection for WooCommerce My Account forms, product reviews, pay for order, add payment method, account details, addresses and checkout, including the Checkout block.
* Contact Form 7, WPForms and Fluent Forms integrations, plus the [ddwcac_captcha] shortcode and ddwcac_check_captcha_response() for custom forms.
* Invisible honeypot with a minimum fill time.
* Security dashboard with charts, top offending IPs, recent stopped attempts and a protection checklist.
* Verification log with search, filters and one click Trust IP, cleaned up automatically after 30 days.
* Trusted user roles, trusted IP list (single IPs, wildcards and ranges) and visitor IP detection behind Cloudflare and proxies.
* Forms keep working during a provider outage, and a hosted provider without keys stays off so you cannot lock yourself out.
* Setup wizard, suggested privacy policy text, HPOS and Cart and Checkout blocks compatibility.
