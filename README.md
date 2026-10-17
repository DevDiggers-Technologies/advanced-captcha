# Advanced CAPTCHA

Spam protection for WordPress and WooCommerce forms: a no-keys built-in CAPTCHA, Cloudflare Turnstile, Google reCAPTCHA or hCaptcha, plus an invisible honeypot and a security dashboard.

* **Slug / text domain:** `advanced-captcha`
* **Version:** 1.0.0
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
