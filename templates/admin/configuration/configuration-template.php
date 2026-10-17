<?php
/**
 * Configuration tabs.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Templates\Admin\Configuration;

use DevDiggers\Framework\Includes\DDFW_Layout;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Configuration_Template' ) ) {
	/**
	 * Configuration template class.
	 */
	class DDWCAC_Configuration_Template {
		/**
		 * Configuration.
		 *
		 * @var array
		 */
		protected $config;

		/**
		 * Hidden inputs that let an emptied multi-select save as empty.
		 *
		 * @var string
		 */
		protected $hidden = '';

		/**
		 * Construct.
		 *
		 * @param array $configuration Configuration.
		 */
		public function __construct( $configuration ) {
			$this->config = $configuration;
		}

		/**
		 * General tab.
		 *
		 * @return void
		 */
		public function general() {
			$roles = [];
			foreach ( get_editable_roles() as $key => $role ) {
				$roles[ $key ] = translate_user_role( $role['name'] );
			}

			$this->render(
				[
					[
						'header' => [
							'heading'     => __( 'Protection', 'advanced-captcha' ),
							'description' => __( 'Switch protection on and pick the challenge visitors see. The Built-in CAPTCHA works straight away with no account or keys.', 'advanced-captcha' ),
						],
						'fields' => [
							$this->field( 'checkbox', 'enabled', __( 'Enable protection', 'advanced-captcha' ), __( 'Turn every check on or off at once: the CAPTCHA and the invisible honeypot.', 'advanced-captcha' ), [ 'checkbox_label' => __( 'Protect the selected forms', 'advanced-captcha' ) ] ),
							$this->field(
								'select',
								'captcha_type',
								__( 'CAPTCHA provider', 'advanced-captcha' ),
								__( 'Built-in needs no keys and sends no data to third parties. Cloudflare Turnstile is the friendliest hosted option, usually invisible to real customers. Each hosted provider has its own tab for keys.', 'advanced-captcha' ),
								[
									'options' => [
										'builtin'    => __( 'Built-in CAPTCHA (no keys needed)', 'advanced-captcha' ),
										'cloudflare' => __( 'Cloudflare Turnstile', 'advanced-captcha' ),
										'google'     => __( 'Google reCAPTCHA', 'advanced-captcha' ),
										'h'          => __( 'hCaptcha', 'advanced-captcha' ),
									],
								]
							),
							$this->field(
								'select',
								'language',
								__( 'Widget language', 'advanced-captcha' ),
								__( 'Auto follows the site language, and the visitor language on WPML sites.', 'advanced-captcha' ),
								[
									'options' => [
										'auto'  => __( 'Auto detect', 'advanced-captcha' ),
										'ar'    => __( 'Arabic', 'advanced-captcha' ),
										'zh-CN' => __( 'Chinese (Simplified)', 'advanced-captcha' ),
										'zh-TW' => __( 'Chinese (Traditional)', 'advanced-captcha' ),
										'nl'    => __( 'Dutch', 'advanced-captcha' ),
										'en'    => __( 'English', 'advanced-captcha' ),
										'fr'    => __( 'French', 'advanced-captcha' ),
										'de'    => __( 'German', 'advanced-captcha' ),
										'hi'    => __( 'Hindi', 'advanced-captcha' ),
										'id'    => __( 'Indonesian', 'advanced-captcha' ),
										'it'    => __( 'Italian', 'advanced-captcha' ),
										'ja'    => __( 'Japanese', 'advanced-captcha' ),
										'ko'    => __( 'Korean', 'advanced-captcha' ),
										'fa'    => __( 'Persian', 'advanced-captcha' ),
										'pl'    => __( 'Polish', 'advanced-captcha' ),
										'pt-BR' => __( 'Portuguese (Brazil)', 'advanced-captcha' ),
										'ru'    => __( 'Russian', 'advanced-captcha' ),
										'es'    => __( 'Spanish', 'advanced-captcha' ),
										'tr'    => __( 'Turkish', 'advanced-captcha' ),
										'uk'    => __( 'Ukrainian', 'advanced-captcha' ),
										'vi'    => __( 'Vietnamese', 'advanced-captcha' ),
									],
								]
							),
							$this->field( 'textarea', 'error_message', __( 'Error message', 'advanced-captcha' ), __( 'Shown when the CAPTCHA is missing or wrong. Keep it short and friendly.', 'advanced-captcha' ) ),
						],
					],
					[
						'header' => [
							'heading'     => __( 'Who skips the checks', 'advanced-captcha' ),
							'description' => __( 'Trusted people never see a challenge. Trusted IPs are managed on the Firewall screen.', 'advanced-captcha' ),
						],
						'fields' => [
							$this->field(
								'select',
								'excluded_user_roles',
								__( 'Trusted user roles', 'advanced-captcha' ),
								__( 'Logged in users with these roles skip every check, for example Shop Manager or Wholesale Customer.', 'advanced-captcha' ),
								[
									'options'     => $roles,
									'placeholder' => __( 'No trusted roles', 'advanced-captcha' ),
								]
							),
						],
					],
				],
				'general'
			);
		}

		/**
		 * Forms tab.
		 *
		 * @return void
		 */
		public function forms() {
			$this->render(
				[
					[
						'header'            => [
							'heading'     => __( 'Which forms are protected?', 'advanced-captcha' ),
							'description' => __( 'WordPress, WooCommerce, Contact Form 7, WPForms and Fluent Forms are switched on from the Integrations screen, one card per plugin.', 'advanced-captcha' ),
						],
						'after_header_html' => '<p><a class="button" href="' . esc_url( ddwcac_get_dashboard_url( [ 'menu' => 'integrations' ] ) ) . '">' . esc_html__( 'Open Integrations', 'advanced-captcha' ) . '</a></p>',
					],
					[
						'header' => [
							'heading'     => __( 'Placement', 'advanced-captcha' ),
							'description' => __( 'Where the widget sits on pages where you have a choice.', 'advanced-captcha' ),
						],
						'fields' => [
							$this->field(
								'select',
								'woocommerce_checkout_form_position',
								__( 'Checkout position', 'advanced-captcha' ),
								__( 'Where the CAPTCHA sits on the classic checkout page. Just above the Place Order button is the most natural spot.', 'advanced-captcha' ),
								[
									'options' => [
										'before_place_order'     => __( 'Above the Place Order button', 'advanced-captcha' ),
										'after_place_order'      => __( 'Below the Place Order button', 'advanced-captcha' ),
										'before_payment_methods' => __( 'Above the payment methods', 'advanced-captcha' ),
										'before_billing_details' => __( 'Above the billing details', 'advanced-captcha' ),
										'after_billing_details'  => __( 'Below the billing details', 'advanced-captcha' ),
									],
								]
							),
							$this->field( 'text', 'shortcode', __( 'Shortcode', 'advanced-captcha' ), __( 'Place the CAPTCHA in any custom form, then verify it in the form handler with ddwcac_check_captcha_response().', 'advanced-captcha' ) ),
						],
					],
				],
				'forms'
			);
		}

		/**
		 * Bot defense tab.
		 *
		 * @return void
		 */
		public function defense() {
			$this->render(
				[
					[
						'header' => [
							'heading'     => __( 'Invisible checks', 'advanced-captcha' ),
							'description' => __( 'These run on every protected form before the CAPTCHA, cost customers nothing and stop most automated spam on their own.', 'advanced-captcha' ),
						],
						'fields' => [
							$this->field( 'checkbox', 'honeypot_enabled', __( 'Honeypot', 'advanced-captcha' ), __( 'Adds a hidden field people never see. Bots that fill in every field are rejected straight away.', 'advanced-captcha' ), [ 'checkbox_label' => __( 'Add an invisible honeypot', 'advanced-captcha' ) ] ),
							$this->field(
								'number',
								'honeypot_min_time',
								__( 'Minimum fill time (seconds)', 'advanced-captcha' ),
								__( 'Forms submitted faster than this after the page loaded are rejected. Real people need a few seconds; 2 is safe, 0 switches it off.', 'advanced-captcha' ),
								[
									'custom_attributes' => [
										'min' => '0',
										'max' => '30',
									],
								]
							),
							$this->locked( 'checkbox', 'smart_mode', __( 'Smart mode', 'advanced-captcha' ), __( 'Visitors with a clean record only get the invisible checks and never see a CAPTCHA. After a wrong password, a failed check or a failed payment, that IP gets the full CAPTCHA for 24 hours.', 'advanced-captcha' ), [ 'checkbox_label' => __( 'Only challenge suspicious visitors', 'advanced-captcha' ) ] ),
						],
					],
					[
						'header'            => [
							'heading'     => __( 'Country rules', 'advanced-captcha' ),
							'description' => __( 'Refuse protected forms from countries you do not sell to.', 'advanced-captcha' ),
						],
						'after_header_html' => $this->pro_card(
							'country-rules',
							__( 'Shut out the countries your spam comes from, in Pro', 'advanced-captcha' ),
							__( 'Most fake signups and card testing runs come from a handful of countries you never ship to. Pro refuses them before any CAPTCHA is even shown.', 'advanced-captcha' ),
							[
								__( 'Block a list of countries, or allow only the countries you sell to', 'advanced-captcha' ),
								__( 'Uses Cloudflare\'s country header or WooCommerce geolocation, no extra service or API key', 'advanced-captcha' ),
								__( 'Applies to every protected form: login, registration, checkout, reviews and form plugins', 'advanced-captcha' ),
							]
						),
					],
				],
				'defense'
			);
		}

		/**
		 * Fraud shield tab.
		 *
		 * @return void
		 */
		public function fraud() {
			// The store's own failed payments make the case better than any copy.
			$failed = function_exists( 'wc_get_orders' ) ? count(
				wc_get_orders(
					[
						'status'       => 'failed',
						'date_created' => '>' . ( time() - 30 * DAY_IN_SECONDS ),
						'return'       => 'ids',
						'limit'        => 500,
					]
				)
			) : 0;

			$this->render(
				[
					[
						'header'            => [
							'heading'     => __( 'Card-testing shield', 'advanced-captcha' ),
							'description' => __( 'Card testers push hundreds of stolen cards through checkout, costing gateway fees and risking your merchant account.', 'advanced-captcha' ),
						],
						'after_header_html' => $this->pro_card(
							'card-testing',
							__( 'Stop card testing before your gateway suspends you, in Pro', 'advanced-captcha' ),
							( $failed
								/* translators: %s: number of failed orders. */
								? sprintf( _n( 'Your store had %s failed payment in the last 30 days.', 'Your store had %s failed payments in the last 30 days.', $failed, 'advanced-captcha' ), number_format_i18n( $failed ) ) . ' '
								: '' ) . __( 'A CAPTCHA alone does not stop a human-assisted card testing run. Pro counts failed payments per IP and per billing email and locks both out of checkout once the pattern is clear.', 'advanced-captcha' ),
							[
								__( 'Works with every gateway that marks orders Failed: Stripe, PayPal, Square, Authorize.net and more', 'advanced-captcha' ),
								__( 'Set how many failed payments are allowed and within how many minutes', 'advanced-captcha' ),
								__( 'Limit how many orders one IP can place per hour', 'advanced-captcha' ),
								__( 'Runs on the classic and the block checkout', 'advanced-captcha' ),
							]
						),
					],
					[
						'header'            => [
							'heading'     => __( 'Email guard', 'advanced-captcha' ),
							'description' => __( 'Throwaway inboxes are how bots create fake accounts and abuse coupons and trials.', 'advanced-captcha' ),
						],
						'after_header_html' => $this->pro_card(
							'email-guard',
							__( 'Refuse throwaway email addresses, in Pro', 'advanced-captcha' ),
							__( 'Fake accounts and coupon abuse almost always use a disposable inbox. Pro refuses them at registration, checkout and in comments.', 'advanced-captcha' ),
							[
								__( 'A built-in list of around 180 disposable email services, kept with the plugin', 'advanced-captcha' ),
								__( 'Your own blocked domains, subdomains included', 'advanced-captcha' ),
								__( 'Pick where it runs and write the message customers see', 'advanced-captcha' ),
							]
						),
					],
				],
				'fraud'
			);
		}

		/**
		 * Lockout and IPs tab.
		 *
		 * @return void
		 */
		public function lockout() {
			$this->render(
				[
					[
						'header'            => [
							'heading'     => __( 'Brute-force lockout', 'advanced-captcha' ),
							'description' => __( 'A CAPTCHA only slows attackers down. Lockout stops them.', 'advanced-captcha' ),
						],
						'after_header_html' => $this->pro_card(
							'lockout',
							__( 'Lock out attackers instead of letting them retry forever, in Pro', 'advanced-captcha' ),
							__( 'Credential stuffing bots try thousands of passwords. Pro locks an IP out of every protected form after a few failures, and lets a real customer back in with one click.', 'advanced-captcha' ),
							[
								__( 'Choose the failed attempts allowed, the time window and the lockout length', 'advanced-captcha' ),
								__( 'A permanent block list with single IPs, wildcards and ranges', 'advanced-captcha' ),
								__( 'Live list of locked out IPs on the Firewall screen, with Lift Lockout and Block buttons', 'advanced-captcha' ),
								__( 'Your own message for locked out visitors', 'advanced-captcha' ),
							]
						),
					],
					[
						'header' => [
							'heading'     => __( 'Visitor IP', 'advanced-captcha' ),
							'description' => __( 'Make sure trusted IPs and the logs see the real visitor. The trusted list lives on the Firewall screen.', 'advanced-captcha' ),
						],
						'fields' => [
							$this->field(
								'select',
								'ip_header',
								__( 'Visitor IP source', 'advanced-captcha' ),
								__( 'Behind Cloudflare, a load balancer or a reverse proxy every visitor shares the proxy IP, so the logs would show one address for everyone. Pick the header your proxy sets. Leave on Direct connection otherwise, because these headers can be forged.', 'advanced-captcha' ),
								[
									'options' => [
										'REMOTE_ADDR'    => __( 'Direct connection (REMOTE_ADDR)', 'advanced-captcha' ),
										'HTTP_CF_CONNECTING_IP' => __( 'Cloudflare (CF-Connecting-IP)', 'advanced-captcha' ),
										'HTTP_X_FORWARDED_FOR' => __( 'Proxy or load balancer (X-Forwarded-For)', 'advanced-captcha' ),
										'HTTP_X_REAL_IP' => __( 'Nginx proxy (X-Real-IP)', 'advanced-captcha' ),
									],
								]
							),
						],
					],
				],
				'lockout'
			);
		}

		/**
		 * Notifications tab.
		 *
		 * @return void
		 */
		public function notifications() {
			$this->render(
				[
					[
						'header'            => [
							'heading'     => __( 'Email notifications', 'advanced-captcha' ),
							'description' => __( 'Know when your store is under attack without opening the dashboard.', 'advanced-captcha' ),
						],
						'after_header_html' => $this->pro_card(
							'notifications',
							__( 'Hear about an attack while it is happening, in Pro', 'advanced-captcha' ),
							__( 'Pro emails you the moment an IP is locked out and sends a short report every Monday.', 'advanced-captcha' ),
							[
								__( 'Lockout alerts for brute force and card testing, at most one every 15 minutes', 'advanced-captcha' ),
								__( 'A weekly report: verifications, bots stopped, lockouts and the top offending IP', 'advanced-captcha' ),
								__( 'Send them to any address, not only the site admin', 'advanced-captcha' ),
							]
						),
					],
				],
				'notifications'
			);
		}

		/**
		 * Advanced tab.
		 *
		 * @return void
		 */
		public function advanced() {
			$this->render(
				[
					[
						'header' => [
							'heading'     => __( 'Reliability & logging', 'advanced-captcha' ),
							'description' => __( 'How the plugin behaves when a provider is down, and whether it keeps a record of every check.', 'advanced-captcha' ),
						],
						'fields' => [
							$this->field( 'checkbox', 'fail_open', __( 'Provider outage', 'advanced-captcha' ), __( 'When Google, Cloudflare or hCaptcha cannot be reached, let customers through instead of blocking checkout and login. The honeypot still runs, and the attempt is logged as a provider error.', 'advanced-captcha' ), [ 'checkbox_label' => __( 'Keep forms working during an outage', 'advanced-captcha' ) ] ),
							$this->field( 'checkbox', 'logging_enabled', __( 'Verification log', 'advanced-captcha' ), __( 'Records every check (form, result, reason, IP) for the dashboard and the Logs screen. Entries older than 30 days are removed automatically once a day.', 'advanced-captcha' ), [ 'checkbox_label' => __( 'Log verifications', 'advanced-captcha' ) ] ),
							$this->locked(
								'number',
								'log_retention',
								__( 'Keep logs for (days)', 'advanced-captcha' ),
								__( 'Keep the history for up to a year to spot slow, repeated attacks.', 'advanced-captcha' ),
								[
									'custom_attributes' => [
										'min' => '1',
										'max' => '365',
									],
								]
							),
						],
					],
					[
						'header'            => [
							'heading'     => __( 'Import & export', 'advanced-captcha' ),
							'description' => __( 'Copy your setup to another store, or keep a backup before changing things.', 'advanced-captcha' ),
						],
						'after_header_html' => $this->pro_card(
							'import-export',
							__( 'Set up every store the same way in seconds, in Pro', 'advanced-captcha' ),
							__( 'Agencies and store owners with several sites export the whole configuration to a file and import it anywhere.', 'advanced-captcha' ),
							[
								__( 'One JSON file with every setting, secret keys only if you choose', 'advanced-captcha' ),
								__( 'Every imported value is checked before it is saved', 'advanced-captcha' ),
							]
						),
					],
				],
				'advanced'
			);
		}

		/**
		 * Built-in CAPTCHA tab.
		 *
		 * @return void
		 */
		public function builtin() {
			$this->render(
				[
					[
						'header' => [
							'heading'     => __( 'Built-in CAPTCHA', 'advanced-captcha' ),
							'description' => __( 'A self-hosted challenge drawn by your own server. No account, no keys, no cookies and nothing sent to a third party, so it needs no extra GDPR consent.', 'advanced-captcha' ),
						],
						'fields' => [
							$this->field(
								'select',
								'builtin_mode',
								__( 'Challenge', 'advanced-captcha' ),
								__( 'A quick sum is easiest for customers. Characters are harder for bots to guess.', 'advanced-captcha' ),
								[
									'options' => [
										'math' => __( 'Simple sum (7 + 4 =)', 'advanced-captcha' ),
										'text' => __( 'Distorted characters (K7M3P)', 'advanced-captcha' ),
									],
								]
							),
							$this->field( 'text', 'builtin_label', __( 'Label', 'advanced-captcha' ), __( 'Shown above the challenge.', 'advanced-captcha' ) ),
						],
					],
				],
				'builtin',
				'builtin'
			);
		}

		/**
		 * Google reCAPTCHA tab.
		 *
		 * @return void
		 */
		public function google() {
			$this->render(
				[
					[
						'header' => [
							'heading'        => __( 'Google reCAPTCHA', 'advanced-captcha' ),
							'description'    => __( 'Create keys for this domain in the reCAPTCHA admin console. v2 and v3 keys are different: keys made for one version do not work with the other.', 'advanced-captcha' ),
							'header_buttons' => [
								[
									'label'  => __( 'Get keys', 'advanced-captcha' ),
									'url'    => 'https://www.google.com/recaptcha/admin/create',
									'target' => '_blank',
								],
							],
						],
						'fields' => [
							$this->field(
								'select',
								'google_captcha_version',
								__( 'Version', 'advanced-captcha' ),
								__( 'v2 Checkbox shows the "I am not a robot" box. v2 Invisible only challenges suspicious visitors. v3 never interrupts anyone and scores every request instead.', 'advanced-captcha' ),
								[
									'id'      => 'ddwcac-google-captcha-version',
									'options' => [
										'v2_checkbox'  => __( 'v2 Checkbox', 'advanced-captcha' ),
										'v2_invisible' => __( 'v2 Invisible', 'advanced-captcha' ),
										'v3'           => __( 'v3 Invisible score', 'advanced-captcha' ),
									],
								]
							),
							$this->field( 'text', 'google_site_key', __( 'Site key', 'advanced-captcha' ), __( 'The public key for the version above.', 'advanced-captcha' ) ),
							$this->field( 'password', 'google_site_secret', __( 'Secret key', 'advanced-captcha' ), __( 'The private key. It never leaves your server.', 'advanced-captcha' ) ),
							$this->field(
								'select',
								'google_captcha_theme',
								__( 'Theme', 'advanced-captcha' ),
								__( 'Light or dark checkbox (v2 Checkbox only).', 'advanced-captcha' ),
								[
									'options'     => [
										'light' => __( 'Light', 'advanced-captcha' ),
										'dark'  => __( 'Dark', 'advanced-captcha' ),
									],
									'input_class' => [ 'ddwcac-google-v2-enable' ],
								]
							),
							$this->field(
								'select',
								'google_captcha_size',
								__( 'Size', 'advanced-captcha' ),
								__( 'Compact fits narrow sidebars and popups (v2 Checkbox only).', 'advanced-captcha' ),
								[
									'options'     => [
										'normal'  => __( 'Normal', 'advanced-captcha' ),
										'compact' => __( 'Compact', 'advanced-captcha' ),
									],
									'input_class' => [ 'ddwcac-google-v2-enable' ],
								]
							),
							$this->field(
								'checkbox',
								'google_disable_submit',
								__( 'Submit button', 'advanced-captcha' ),
								__( 'Keeps the submit button disabled until the box is ticked (v2 Checkbox only).', 'advanced-captcha' ),
								[
									'checkbox_label' => __( 'Disable submit until verified', 'advanced-captcha' ),
									'input_class'    => [ 'ddwcac-google-v2-enable' ],
								]
							),
							$this->field(
								'number',
								'google_v3_score',
								__( 'Minimum score', 'advanced-captcha' ),
								__( 'Scores run from 0.0 (bot) to 1.0 (human). Submissions below this are refused; 0.5 suits most stores (v3 only).', 'advanced-captcha' ),
								[
									'custom_attributes' => [
										'min'  => '0',
										'max'  => '1',
										'step' => '0.1',
									],
									'input_class'       => [ 'ddwcac-google-v3-enable' ],
								]
							),
							$this->field(
								'checkbox',
								'google_hide_badge',
								__( 'Badge', 'advanced-captcha' ),
								__( 'Hides the floating badge and shows Google\'s required notice under the form instead, as Google allows (v3 only).', 'advanced-captcha' ),
								[
									'checkbox_label' => __( 'Replace the badge with a text notice', 'advanced-captcha' ),
									'input_class'    => [ 'ddwcac-google-v3-enable' ],
								]
							),
							$this->locked(
								'checkbox',
								'google_v3_fallback',
								__( 'Low score fallback', 'advanced-captcha' ),
								__( 'Instead of turning away a customer with a low score, show them a v2 checkbox for 30 minutes. Needs a separate v2 Checkbox key pair below (v3 only).', 'advanced-captcha' ),
								[
									'checkbox_label' => __( 'Fall back to a v2 checkbox', 'advanced-captcha' ),
									'input_class'    => [ 'ddwcac-google-v3-enable' ],
								]
							),
							$this->locked( 'text', 'google_v2_site_key', __( 'Fallback v2 site key', 'advanced-captcha' ), __( 'A v2 Checkbox site key for the same domain.', 'advanced-captcha' ), [ 'input_class' => [ 'ddwcac-google-v3-enable' ] ] ),
							$this->locked( 'password', 'google_v2_site_secret', __( 'Fallback v2 secret key', 'advanced-captcha' ), __( 'The matching v2 secret key.', 'advanced-captcha' ), [ 'input_class' => [ 'ddwcac-google-v3-enable' ] ] ),
							$this->field(
								'select',
								'google_domain',
								__( 'Script domain', 'advanced-captcha' ),
								__( 'Use recaptcha.net where google.com is blocked, for example in China.', 'advanced-captcha' ),
								[
									'options' => [
										'google.com'    => 'google.com',
										'recaptcha.net' => 'recaptcha.net',
									],
								]
							),
						],
					],
				],
				'google',
				'google'
			);
		}

		/**
		 * Cloudflare Turnstile tab.
		 *
		 * @return void
		 */
		public function cloudflare() {
			$this->render(
				[
					[
						'header' => [
							'heading'        => __( 'Cloudflare Turnstile', 'advanced-captcha' ),
							'description'    => __( 'Free, privacy-first and usually invisible: most customers never click anything. Your site does not need to use Cloudflare.', 'advanced-captcha' ),
							'header_buttons' => [
								[
									'label'  => __( 'Get keys', 'advanced-captcha' ),
									'url'    => 'https://dash.cloudflare.com/?to=/:account/turnstile', // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- A link to the Cloudflare dashboard, nothing is loaded from it.
									'target' => '_blank',
								],
							],
						],
						'fields' => [
							$this->field( 'text', 'cloudflare_site_key', __( 'Site key', 'advanced-captcha' ), __( 'From your Turnstile widget settings.', 'advanced-captcha' ) ),
							$this->field( 'password', 'cloudflare_site_secret', __( 'Secret key', 'advanced-captcha' ), __( 'The private key. It never leaves your server.', 'advanced-captcha' ) ),
							$this->field(
								'select',
								'cloudflare_captcha_theme',
								__( 'Theme', 'advanced-captcha' ),
								__( 'Auto follows the visitor\'s light or dark mode.', 'advanced-captcha' ),
								[
									'options' => [
										'auto'  => __( 'Auto', 'advanced-captcha' ),
										'light' => __( 'Light', 'advanced-captcha' ),
										'dark'  => __( 'Dark', 'advanced-captcha' ),
									],
								]
							),
							$this->field(
								'select',
								'cloudflare_captcha_size',
								__( 'Size', 'advanced-captcha' ),
								__( 'Flexible stretches to the width of the form.', 'advanced-captcha' ),
								[
									'options' => [
										'normal'   => __( 'Normal', 'advanced-captcha' ),
										'flexible' => __( 'Flexible', 'advanced-captcha' ),
										'compact'  => __( 'Compact', 'advanced-captcha' ),
									],
								]
							),
							$this->field( 'checkbox', 'cloudflare_disable_submit', __( 'Submit button', 'advanced-captcha' ), __( 'Keeps the submit button disabled until Turnstile has verified the visitor.', 'advanced-captcha' ), [ 'checkbox_label' => __( 'Disable submit until verified', 'advanced-captcha' ) ] ),
						],
					],
				],
				'cloudflare',
				'cloudflare'
			);
		}

		/**
		 * HCaptcha tab.
		 *
		 * @return void
		 */
		public function hcaptcha() {
			$this->render(
				[
					[
						'header' => [
							'heading'        => __( 'hCaptcha', 'advanced-captcha' ),
							'description'    => __( 'A privacy-focused alternative to reCAPTCHA, popular with stores serving the EU.', 'advanced-captcha' ),
							'header_buttons' => [
								[
									'label'  => __( 'Get keys', 'advanced-captcha' ),
									'url'    => 'https://dashboard.hcaptcha.com/sites',
									'target' => '_blank',
								],
							],
						],
						'fields' => [
							$this->field( 'text', 'h_site_key', __( 'Site key', 'advanced-captcha' ), __( 'From your hCaptcha site settings.', 'advanced-captcha' ) ),
							$this->field( 'password', 'h_site_secret', __( 'Secret key', 'advanced-captcha' ), __( 'Your account secret. It never leaves your server.', 'advanced-captcha' ) ),
							$this->field(
								'select',
								'h_captcha_theme',
								__( 'Theme', 'advanced-captcha' ),
								__( 'Light or dark widget.', 'advanced-captcha' ),
								[
									'options' => [
										'light' => __( 'Light', 'advanced-captcha' ),
										'dark'  => __( 'Dark', 'advanced-captcha' ),
									],
								]
							),
							$this->field(
								'select',
								'h_captcha_size',
								__( 'Size', 'advanced-captcha' ),
								__( 'Compact fits narrow sidebars and popups.', 'advanced-captcha' ),
								[
									'options' => [
										'normal'  => __( 'Normal', 'advanced-captcha' ),
										'compact' => __( 'Compact', 'advanced-captcha' ),
									],
								]
							),
							$this->field( 'checkbox', 'h_disable_submit', __( 'Submit button', 'advanced-captcha' ), __( 'Keeps the submit button disabled until the challenge is solved.', 'advanced-captcha' ), [ 'checkbox_label' => __( 'Disable submit until verified', 'advanced-captcha' ) ] ),
						],
					],
				],
				'hcaptcha',
				'h'
			);
		}


		/**
		 * Live preview with a key test, shown above the provider tab's Save button.
		 *
		 * @param string $type Provider.
		 * @return string
		 */
		protected function ddwcac_get_preview( $type ) {
			$ready = ddwcac_is_provider_ready( $type, $this->config );
			ob_start();
			?>
			<div class="ddfw-fields-section ddwcac-preview" data-type="<?php echo esc_attr( $type ); ?>">
				<div class="ddfw-fields-section-header">
					<h3><?php esc_html_e( 'Live preview & key test', 'advanced-captcha' ); ?></h3>
					<p><?php esc_html_e( 'This is the widget your customers see, using the saved settings. Solve it and press Test to confirm the provider accepts your keys before you rely on them.', 'advanced-captcha' ); ?></p>
				</div>
				<?php if ( $ready ) : ?>
					<div class="ddwcac-preview-stage">
						<?php echo wp_kses( ddwcac_get_captcha_field( '', $type, true ), ddwcac_kses_captcha() ); ?>
					</div>
					<p class="ddwcac-preview-actions">
						<button type="button" class="button button-primary ddwcac-preview-test"><?php esc_html_e( 'Test', 'advanced-captcha' ); ?></button>
						<span class="ddwcac-preview-result" role="status" aria-live="polite"></span>
					</p>
				<?php else : ?>
					<div class="ddwcac-dash-empty"><strong><?php esc_html_e( 'Save a site key and secret key to see the preview.', 'advanced-captcha' ); ?></strong></div>
				<?php endif; ?>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Field definition with the saved value. Multi-selects get the [] name and a clearing input.
		 *
		 * @param string $type        Field type.
		 * @param string $key         Option key without prefix.
		 * @param string $label       Label.
		 * @param string $description Description.
		 * @param array  $extra       Extra framework args.
		 * @return array
		 */
		protected function field( $type, $key, $label, $description, $extra = [] ) {
			$value = $this->config[ $key ] ?? ddwcac_get_option_defaults()[ $key ] ?? '';
			$name  = '_ddwcac_' . $key;

			if ( is_array( $value ) ) {
				$name                      .= '[]';
				$extra['custom_attributes'] = array_merge( [ 'multiple' => 'multiple' ], $extra['custom_attributes'] ?? [] );
				$this->hidden              .= '<input type="hidden" name="_ddwcac_' . esc_attr( $key ) . '[]" value="" />';
			}

			if ( ! empty( $extra['placeholder'] ) && 'select' === $type ) {
				$extra['custom_attributes']['data-placeholder'] = $extra['placeholder'];
			}

			return array_merge(
				[
					'type'        => $type,
					'id'          => 'ddwcac-' . str_replace( '_', '-', $key ),
					'name'        => $name,
					'label'       => $label,
					'description' => $description,
					'value'       => $value,
				],
				$extra
			);
		}

		/**
		 * A Pro setting shown disabled with a PRO badge. Its id and name are renamed by the framework, so
		 * nothing is registered under them and a re-enabled field saves nothing.
		 *
		 * @param string $type        Field type.
		 * @param string $key         Pro option key without prefix.
		 * @param string $label       Label.
		 * @param string $description Description.
		 * @param array  $extra       Extra framework args.
		 * @return array
		 */
		protected function locked( $type, $key, $label, $description, $extra = [] ) {
			return ddfw_locked_field(
				array_merge(
					[
						'type'        => $type,
						'id'          => str_replace( '_', '-', $key ),
						'label'       => $label,
						'description' => $description,
						'value'       => '',
					],
					$extra
				),
				'ddwcac'
			);
		}

		/**
		 * Upgrade card for a section that is Pro from top to bottom.
		 *
		 * @param string   $image       Screenshot name in assets/images/pro.
		 * @param string   $heading     Heading.
		 * @param string   $description Description.
		 * @param string[] $features    Feature bullets.
		 * @return string
		 */
		protected function pro_card( $image, $heading, $description, $features ) {
			return ddfw_get_upgrade_to_pro_section(
				[
					'image_url'     => ddwcac_get_pro_image( $image ),
					'heading'       => $heading,
					'description'   => $description,
					'list_features' => $features,
					'upgrade_url'   => 'https://devdiggers.com/product/woocommerce-advanced-captcha/',
				]
			);
		}

		/**
		 * Render a tab's sections inside its own settings group.
		 *
		 * @param array  $sections Sections.
		 * @param string $group    Tab key.
		 * @param string $preview  Provider whose live preview goes last, above Save.
		 * @return void
		 */
		protected function render( $sections, $group, $preview = '' ) {
			$sections[0]['after_header_html'] = $this->hidden . ( $sections[0]['after_header_html'] ?? '' );
			$this->hidden                     = '';

			if ( $preview ) {
				$sections[] = [ 'after_header_html' => $this->ddwcac_get_preview( $preview ) ];
				add_filter( 'ddfw_kses_allowed_form_html', [ $this, 'ddwcac_allow_captcha_html' ] );
			}

			// A tab with no settings of its own (only Pro cards) gets no form, so no Save button posts to an unregistered group.
			$has_settings = ! empty( \DDWCAdvancedCAPTCHA\Includes\Admin\DDWCAC_Admin_Functions::GROUPS[ $group ] );

			( new DDFW_Layout() )->get_form_section_layout( $sections, $has_settings ? "ddwcac-{$group}-configuration-fields" : '' );

			remove_filter( 'ddfw_kses_allowed_form_html', [ $this, 'ddwcac_allow_captcha_html' ] );
		}

		/**
		 * Let the framework keep the CAPTCHA widget markup (SVG text, positioning) in the preview.
		 *
		 * @param array $allowed Allowed HTML.
		 * @return array
		 */
		public function ddwcac_allow_captcha_html( $allowed ) {
			foreach ( ddwcac_kses_captcha() as $tag => $attributes ) {
				$allowed[ $tag ] = array_merge( $allowed[ $tag ] ?? [], $attributes );
			}

			return $allowed;
		}
	}
}
