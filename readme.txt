=== Advanced CAPTCHA ===
Contributors: devdiggers
Plugin URI: https://devdiggers.com/product/woocommerce-advanced-captcha/
Author: DevDiggers
Author URI: https://devdiggers.com/
Tags: captcha, recaptcha, turnstile, hcaptcha, spam protection
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 9.0
WC tested up to: 11.2
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Stop spam and fake signups on login, comments, contact forms and WooCommerce checkout. Built-in CAPTCHA, Turnstile, reCAPTCHA or hCaptcha.

== Description ==

Bots do not care how small your site is. They try passwords on the login page, create fake customer accounts, fill your comments with links and push stolen cards through checkout. A CAPTCHA on the right forms stops most of it before it reaches your inbox or your orders list.

Advanced CAPTCHA protects WordPress and WooCommerce forms with the challenge you choose. Start with the **built-in CAPTCHA**, which needs no account, no API keys and sends nothing to a third party, or connect **Cloudflare Turnstile**, **Google reCAPTCHA** (v2 Checkbox, v2 Invisible or v3) or **hCaptcha**. An invisible honeypot runs on every protected form as well, so most bots are refused before a real customer ever sees a puzzle.

Everything is set up from one dashboard. A short setup wizard picks the provider and the forms, and the Integrations screen switches protection on per plugin with a single toggle. No shortcodes, no theme edits.

= Quick links =

* [Live Demo](https://demo.devdiggers.com/woocommerce-advanced-captcha-free/)
* [Documentation](https://docs.devdiggers.com/advanced-captcha/)
* [Advanced CAPTCHA Pro](https://devdiggers.com/product/woocommerce-advanced-captcha/)
* [Support forum](https://wordpress.org/support/plugin/advanced-captcha/)

= How it works =

1. Install and activate. The setup wizard opens.
2. Pick a CAPTCHA. The built-in one works straight away; hosted providers ask for a site key and secret key.
3. Choose the forms to protect. Login, registration, lost password, guest checkout and reviews are ticked for you.
4. Done. Every passed and stopped attempt shows up on the dashboard.

= What the free plugin does =

=== Four CAPTCHA providers, one setting ===

Switch providers at any time without touching your forms.

* **Built-in CAPTCHA**: a simple sum (7 + 4 =) or distorted characters, drawn by your own server. No keys, no cookies, no data sent anywhere, so it needs no extra GDPR consent
* **Cloudflare Turnstile**: free, privacy friendly and usually invisible. Your site does not need to use Cloudflare
* **Google reCAPTCHA**: v2 Checkbox, v2 Invisible or v3 with your own minimum score, plus the recaptcha.net domain for regions where google.com is blocked
* **hCaptcha**: a privacy focused alternative to reCAPTCHA
* Theme, size and language for every provider, with automatic language detection and WPML support
* A live preview with a **Test** button on each provider tab, so you know your keys work before customers rely on them

=== WordPress forms ===

* Login (wp-login.php)
* Registration
* Lost password
* Blog comments

=== WooCommerce forms ===

Every form on the store, classic and block based.

* My Account login, registration and lost password
* Checkout for guests only, or for every customer, on the **classic checkout and the Checkout block**
* Choose where the CAPTCHA sits on the classic checkout: above or below Place Order, above the payment methods or around the billing details
* Product reviews
* Pay for order, add payment method, account details, billing address and shipping address

=== Contact form plugins ===

* **Contact Form 7**: an Advanced CAPTCHA button in the form editor adds the `[ddwcac_captcha]` tag
* **WPForms**: every form, above the submit button
* **Fluent Forms**: every form, including multi step ones
* Any custom form: place `[ddwcac_captcha]` in it and verify the submission with `ddwcac_check_captcha_response()`

=== Invisible honeypot ===

A hidden field people never see, plus a signed timer. Bots that fill every field, or submit a form within a second or two of loading it, are refused before the CAPTCHA is even checked. You set the minimum fill time.

=== Security dashboard and logs ===

See what the plugin is doing for you.

* Verifications, bots stopped, humans passed, block rate and attacking IPs, compared with the previous period
* An activity chart, attacks by form and threats by type
* Top offending IPs and the latest stopped attempts
* A protection checklist that shows what is still unprotected
* A searchable log of every check with the form, result, reason, IP and page, kept for 30 days and cleaned up automatically

=== Trusted users and IPs ===

* Trusted user roles, such as Shop Manager or Wholesale Customer, never see a challenge
* A trusted IP list for your office, staff and payment gateway callbacks, with single IPs, wildcards (203.0.113.*) and ranges (203.0.113.0/24)
* Trust an IP straight from the log in one click
* Works behind Cloudflare, load balancers and Nginx proxies, so the real visitor IP is used

=== Reliable by default ===

* If Google, Cloudflare or hCaptcha cannot be reached, forms keep working and the attempt is logged, instead of blocking every login and checkout. You can switch this off
* Without both keys a hosted provider simply stays off, so you can never lock yourself out of wp-admin
* HPOS and Cart and Checkout block compatible
* Works on any WordPress site. WooCommerce is optional

= Free vs Pro =

The free plugin is a complete CAPTCHA plugin. [Advanced CAPTCHA Pro](https://devdiggers.com/product/woocommerce-advanced-captcha/) adds the tools that stop an attacker who keeps coming back, plus fraud protection for WooCommerce stores. Pro features are shown in the free plugin's settings with a PRO badge, so you can see exactly what each one does.

=== Firewall and brute force protection (Pro) ===

* Brute force lockout: an IP that keeps failing is locked out of every protected form for a time you choose
* A live Locked Out list with one click Lift Lockout
* A permanent block list with single IPs, wildcards and ranges, and a Block button next to every repeat offender
* Country rules: block the countries you never sell to, or allow only the ones you do

=== WooCommerce fraud shield (Pro) ===

* Card testing shield: failed payments are counted per IP and per billing email, and both are locked out of checkout once the pattern is clear. Works with Stripe, PayPal, Square and any gateway that marks orders Failed
* Orders per IP per hour limit
* Disposable email guard with a built-in list of around 180 throwaway services and your own blocked domains

=== Smarter challenges (Pro) ===

* Smart mode: visitors with a clean record never see a CAPTCHA, only those who already failed something
* reCAPTCHA v3 low score fallback: a customer with a low score gets a v2 checkbox instead of a dead end

=== More integrations and tools (Pro) ===

* Gravity Forms, Elementor Pro Forms, Forminator, Formidable Forms, BuddyPress, bbPress, Ultimate Member and Mailchimp for WordPress
* DevDiggers Affiliates and MultiPOS login forms
* Lockout alerts and a weekly protection report by email
* Log history kept for up to a year
* Settings import and export

== Installation ==

= Automatic installation =

1. In your WordPress admin go to Plugins > Add New.
2. Search for "Advanced CAPTCHA".
3. Click Install Now, then Activate.

= Manual installation =

1. Download the plugin zip file.
2. Go to Plugins > Add New > Upload Plugin and choose the zip file.
3. Click Install Now, then Activate.

= After activating =

The setup wizard opens. Choose a CAPTCHA, tick the forms to protect and finish. You can change everything later under **Advanced CAPTCHA** in the admin menu (inside the DevDiggers menu).

To check it works, open your login page in a private browser window.

== Frequently Asked Questions ==

= Do I need API keys? =

No. The built-in CAPTCHA works without any account or keys. Keys are only needed for Cloudflare Turnstile, Google reCAPTCHA or hCaptcha, and each provider tab has a Get keys button.

= Which CAPTCHA should I use? =

Cloudflare Turnstile is the friendliest hosted option: most customers never click anything. The built-in CAPTCHA is the most private, because nothing leaves your server. Use reCAPTCHA or hCaptcha if you already have keys.

= Does it work with the WooCommerce Checkout block? =

Yes. The CAPTCHA appears in the Checkout block and is verified through the Store API, as well as on the classic checkout.

= Can I protect only guest checkout? =

Yes. On Advanced CAPTCHA > Integrations, tick Guest Checkout on the WooCommerce card. Logged in customers then check out without a challenge. Tick All Users Checkout to protect everyone.

= Will I be locked out of my own site? =

No. A hosted provider stays off until both keys are saved, and you can add your role to Trusted user roles or your IP to the trusted list on the Firewall screen.

= Does it work without WooCommerce? =

Yes. WordPress login, registration, lost password and comments, Contact Form 7, WPForms and Fluent Forms are protected on any WordPress site.

= How do I add the CAPTCHA to Contact Form 7? =

Edit your form and click the Advanced CAPTCHA button above the editor. It inserts the `[ddwcac_captcha]` tag. Place it above the submit button and save.

= Can I add the CAPTCHA to my own form? =

Yes. Put the `[ddwcac_captcha]` shortcode in the form, then call `ddwcac_check_captcha_response()` in your form handler. It returns an array with `success` and a `message` to show the visitor.

= Is the built-in CAPTCHA GDPR friendly? =

Yes. It is drawn by your own server, sets no cookies and sends nothing to a third party. Hosted providers receive the visitor's IP address and challenge token, see External services below. The plugin also adds suggested wording for your privacy policy under Settings > Privacy.

= What does the log record? =

The form, the provider, the result and reason, the visitor IP, user agent and page. Entries are deleted automatically after 30 days, and you can switch logging off under Configuration > Advanced.

= How do I stop the same IP attacking again and again? =

The free plugin challenges every attempt. Locking an IP out after repeated failures and keeping a permanent block list are part of Advanced CAPTCHA Pro.

= Will my settings be kept if I upgrade to Pro? =

Yes. Pro uses the same settings, so everything you configured stays in place.

= Where can I get help? =

Ask in the [support forum](https://wordpress.org/support/plugin/advanced-captcha/) or read the [documentation](https://docs.devdiggers.com/advanced-captcha/).

== Screenshots ==

1. Security dashboard with verifications, bots stopped, activity chart and top offending IPs.
2. Integrations screen: switch protection on for WordPress, WooCommerce and form plugins.
3. The built-in CAPTCHA on the WooCommerce My Account login form.
4. Cloudflare Turnstile on the WooCommerce Checkout block.
5. Provider settings with a live preview and key test.
6. Verification logs with search, filters and one click Trust IP.
7. Firewall screen with repeat offenders and the trusted IP list.
8. Setup wizard.

== External services ==

The built-in CAPTCHA works entirely on your server. The services below are used only when you choose them as your CAPTCHA provider and save their keys.

**Cloudflare Turnstile**

* What it is used for: Showing the Turnstile challenge and checking the result.
* When data is sent: The visitor's browser loads the Turnstile script from challenges.cloudflare.com on pages with a protected form. When the form is submitted, your server sends the challenge token, your secret key and the visitor's IP address to Cloudflare to verify it.
* Provided by Cloudflare, Inc.: [Terms](https://www.cloudflare.com/website-terms/), [Privacy Policy](https://www.cloudflare.com/privacypolicy/).

**Google reCAPTCHA**

* What it is used for: Showing the reCAPTCHA widget or v3 score and checking the result.
* When data is sent: The visitor's browser loads the reCAPTCHA script from www.google.com (or www.recaptcha.net if you choose it) on pages with a protected form. When the form is submitted, your server sends the token, your secret key and the visitor's IP address to Google to verify it.
* Provided by Google LLC: [Terms of Service](https://policies.google.com/terms), [Privacy Policy](https://policies.google.com/privacy).

**hCaptcha**

* What it is used for: Showing the hCaptcha challenge and checking the result.
* When data is sent: The visitor's browser loads the hCaptcha script from js.hcaptcha.com on pages with a protected form. When the form is submitted, your server sends the token, your secret key and the visitor's IP address to api.hcaptcha.com to verify it.
* Provided by Intuition Machines, Inc.: [Terms of Service](https://www.hcaptcha.com/terms), [Privacy Policy](https://www.hcaptcha.com/privacy).

The bundled DevDiggers framework, which draws the admin screens, connects to the services below. All of them run only inside the WordPress admin.

**DevDiggers extensions directory**

* What it is used for: Showing available DevDiggers extensions on the Extensions admin page.
* When data is sent: Only when a logged in administrator opens the Extensions admin page. The response is cached for 24 hours.
* What data is sent: A standard outbound HTTP request only, meaning your server's IP address and a plugin user agent string. No personal data is sent.
* Endpoint: https://devdiggers.com/wp-json/ddwcs/v1/plugins

**Newsletter subscription (optional)**

* What it is used for: Adding your email address to the DevDiggers newsletter, only if you choose to subscribe.
* When data is sent: Only when an administrator submits the optional newsletter form in the plugin dashboard.
* What data is sent: The email address you enter and your site URL.
* Endpoint: https://devdiggers.com/?fluentcrm=1&route=contact

These two services are provided by DevDiggers: [Terms and Conditions](https://devdiggers.com/terms-and-conditions/), [Privacy Policy](https://devdiggers.com/privacy-policy/).

**Gravatar**

* What it is used for: WordPress core's `get_avatar_url()` shows the logged in administrator's avatar in the plugin dashboard header.
* When data is sent: Only when an administrator opens the plugin dashboard and avatars are enabled under Settings > Discussion.
* What data is sent: A hash of the administrator's email address inside the image URL, plus the usual request data such as IP address and user agent.
* Provided by Automattic: [Terms of Service](https://automattic.com/terms/), [Privacy Policy](https://automattic.com/privacy/).

== Source code and build tools ==

The admin and storefront JavaScript and CSS are compiled from the `src/` folder with webpack and Babel. The complete, human readable source code, including all build configuration, is published in our public GitHub repository:

https://github.com/DevDiggers-Technologies/advanced-captcha

To build the assets from source:

1. Run `npm install` to install the build dependencies listed in `package.json`.
2. Run `npm run build` to compile the production assets into `assets/js` and `assets/css`.

The build configuration is in `webpack.config.js` and `babel.config.js`. Two third party libraries used by the admin screens ship unmodified under the MIT licence inside `devdiggers-framework/assets/js/`: Chart.js (https://www.chartjs.org) and Select2 (https://select2.org).

== Changelog ==

= 1.0.0 =
* New: Built-in CAPTCHA (simple sum or distorted characters) that works without keys, cookies or third party calls.
* New: Cloudflare Turnstile, Google reCAPTCHA (v2 Checkbox, v2 Invisible, v3 with minimum score) and hCaptcha, with theme, size and language options.
* New: Live preview and Test button on every provider tab to confirm your keys work.
* New: Protection for WordPress login, registration, lost password and comments.
* New: Protection for WooCommerce My Account forms, product reviews, pay for order, add payment method, account details, addresses and checkout, including the Checkout block.
* New: Contact Form 7, WPForms and Fluent Forms integrations, plus the [ddwcac_captcha] shortcode and ddwcac_check_captcha_response() for custom forms.
* New: Invisible honeypot with a minimum fill time.
* New: Security dashboard with charts, top offending IPs, recent stopped attempts and a protection checklist.
* New: Verification log with search, filters and one click Trust IP, cleaned up automatically after 30 days.
* New: Trusted user roles, trusted IP list (single IPs, wildcards and ranges) and visitor IP detection behind Cloudflare and proxies.
* New: Forms keep working during a provider outage, and a hosted provider without keys stays off so you cannot lock yourself out.
* New: Setup wizard, suggested privacy policy text, HPOS and Cart and Checkout blocks compatibility.

== Upgrade Notice ==

= 1.0.0 =
First release. After activating, run the setup wizard to choose a CAPTCHA and the forms to protect.
