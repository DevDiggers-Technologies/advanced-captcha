<?php
/**
 * Setup wizard.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Includes\Admin;

use DevDiggers\Framework\Includes\DDFW_Form_Field;
use DevDiggers\Framework\Includes\DDFW_Setup_Wizard;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Setup_Wizard' ) ) {
	/**
	 * Setup wizard class.
	 */
	class DDWCAC_Setup_Wizard {
		/**
		 * Configuration.
		 *
		 * @var array
		 */
		protected $config;

		/**
		 * Register wizard.
		 *
		 * @param array $configuration Configuration.
		 */
		public function __construct( $configuration = [] ) {
			$this->config = $configuration;
			new DDFW_Setup_Wizard( $this->get_wizard_config() );
		}

		/**
		 * Wizard settings.
		 *
		 * @return array
		 */
		public function get_wizard_config() {
			return [
				'plugin_slug'    => DDWCAC_PLUGIN_SLUG,
				'plugin_file'    => DDWCAC_PLUGIN_SLUG . '/functions.php',
				'dashboard_page' => DDWCAC_DASHBOARD_PAGE,
				'redirect_url'   => admin_url( 'admin.php?page=' . DDWCAC_DASHBOARD_PAGE ),
				'brand'          => [
					'name'        => __( 'Advanced CAPTCHA', 'advanced-captcha' ),
					'description' => __( 'A few quick answers and every login, signup and checkout on your store is protected.', 'advanced-captcha' ),
				],
				'steps'          => [
					'welcome'    => [
						'label'         => __( 'Welcome', 'advanced-captcha' ),
						'view_callback' => [ $this, 'welcome' ],
					],
					'general'    => [
						'label'         => __( 'Provider', 'advanced-captcha' ),
						'title'         => __( 'Choose your CAPTCHA', 'advanced-captcha' ),
						'description'   => __( 'The Built-in CAPTCHA works right now with no account. Pick a hosted provider if you already have keys.', 'advanced-captcha' ),
						'view_callback' => [ $this, 'general' ],
						'save_callback' => [ $this, 'save_general' ],
					],
					'forms'      => [
						'label'         => __( 'Forms', 'advanced-captcha' ),
						'title'         => __( 'Where should bots be stopped?', 'advanced-captcha' ),
						'description'   => __( 'The forms bots attack most are switched on already. Fine-tune everything later on the Integrations screen.', 'advanced-captcha' ),
						'view_callback' => [ $this, 'forms' ],
						'save_callback' => [ $this, 'save_forms' ],
					],
					'protection' => [
						'label'         => __( 'Protection', 'advanced-captcha' ),
						'title'         => __( 'Extra layers', 'advanced-captcha' ),
						'description'   => __( 'An invisible check that works alongside the CAPTCHA, plus the layers Pro adds.', 'advanced-captcha' ),
						'view_callback' => [ $this, 'protection' ],
						'save_callback' => [ $this, 'save_protection' ],
					],
					'ready'      => [
						'label'             => __( 'Ready!', 'advanced-captcha' ),
						'ready_title'       => __( 'Your store is protected.', 'advanced-captcha' ),
						'ready_description' => __( 'Open your login page in a private window to see the CAPTCHA. Every bot stopped will show up on the dashboard.', 'advanced-captcha' ),
					],
				],
			];
		}

		/**
		 * Welcome step.
		 *
		 * @return void
		 */
		public function welcome() {
			?>
			<div class="ddfw-setup-wizard-ready ddfw-setup-wizard-onboarding">
				<div class="ddfw-success-icon-wrap">
					<?php echo wp_kses( ddwcac_get_brand_mark( 100 ), ddfw_kses_allowed_svg_tags() ); ?>
				</div>
				<h2 class="ddfw-setup-wizard-ready-title"><?php esc_html_e( 'Welcome to Advanced CAPTCHA!', 'advanced-captcha' ); ?></h2>
				<p class="ddfw-setup-wizard-ready-desc"><?php esc_html_e( 'Stop fake accounts, login attacks and spam. In three short steps you will choose a CAPTCHA, pick the forms to protect and switch on the invisible honeypot.', 'advanced-captcha' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Render fields inside a wizard table.
		 *
		 * @param array $fields Fields.
		 * @return void
		 */
		protected function fields( $fields ) {
			?>
			<div class="ddfw-fields-section"><table class="form-table"><tbody>
			<?php
			foreach ( $fields as $field ) {
				DDFW_Form_Field::display_form_field( $field );
			}
			?>
			</tbody></table></div>
			<?php
		}

		/**
		 * Provider step.
		 *
		 * @return void
		 */
		public function general() {
			$fields = [
				[
					'type'        => 'select',
					'id'          => 'ddwcac-wizard-provider',
					'name'        => '_ddwcac_captcha_type',
					'label'       => __( 'CAPTCHA provider', 'advanced-captcha' ),
					'description' => __( 'You can switch at any time without touching your forms.', 'advanced-captcha' ),
					'options'     => [
						'builtin'    => __( 'Built-in CAPTCHA (no keys needed)', 'advanced-captcha' ),
						'cloudflare' => __( 'Cloudflare Turnstile', 'advanced-captcha' ),
						'google'     => __( 'Google reCAPTCHA v2 Checkbox', 'advanced-captcha' ),
						'h'          => __( 'hCaptcha', 'advanced-captcha' ),
					],
					'value'       => $this->config['captcha_type'],
				],
			];

			$labels = [
				'cloudflare' => __( 'Cloudflare', 'advanced-captcha' ),
				'google'     => __( 'Google', 'advanced-captcha' ),
				'h'          => __( 'hCaptcha', 'advanced-captcha' ),
			];

			foreach ( $labels as $type => $label ) {
				$fields[] = [
					'type'        => 'text',
					'name'        => "_ddwcac_{$type}_site_key",
					/* translators: %s: provider name. */
					'label'       => sprintf( __( '%s site key', 'advanced-captcha' ), $label ),
					'description' => __( 'The public key from the provider dashboard.', 'advanced-captcha' ),
					'value'       => $this->config[ "{$type}_site_key" ],
					'input_class' => [ "ddwcac-wizard-keys-{$type}" ],
				];
				$fields[] = [
					'type'        => 'password',
					'name'        => "_ddwcac_{$type}_site_secret",
					/* translators: %s: provider name. */
					'label'       => sprintf( __( '%s secret key', 'advanced-captcha' ), $label ),
					'description' => __( 'The private key; it never leaves your server.', 'advanced-captcha' ),
					'value'       => $this->config[ "{$type}_site_secret" ],
					'input_class' => [ "ddwcac-wizard-keys-{$type}" ],
				];
			}

			$this->fields( $fields );
		}

		/**
		 * Save the provider and its keys, refusing a hosted provider without keys.
		 *
		 * @param array $form_data Submitted fields.
		 * @return true|\WP_Error
		 */
		public function save_general( $form_data ) {
			$values = array_column( (array) $form_data, 'value', 'name' );
			$admin  = new DDWCAC_Admin_Functions( [] );
			$type   = $admin->sanitize_setting( '_ddwcac_captcha_type', $values['_ddwcac_captcha_type'] ?? 'builtin' );

			foreach ( [ 'google', 'cloudflare', 'h' ] as $provider ) {
				foreach ( [ 'site_key', 'site_secret' ] as $part ) {
					$key = "_ddwcac_{$provider}_{$part}";
					if ( ! empty( $values[ $key ] ) ) {
						update_option( $key, sanitize_text_field( $values[ $key ] ) );
					}
				}
			}

			if ( 'builtin' !== $type && ( ! get_option( "_ddwcac_{$type}_site_key" ) || ! get_option( "_ddwcac_{$type}_site_secret" ) ) ) {
				return new \WP_Error( 'missing_captcha_keys', __( 'Enter both keys for the selected provider, or choose the Built-in CAPTCHA.', 'advanced-captcha' ) );
			}

			if ( 'google' === $type ) {
				update_option( '_ddwcac_google_captcha_version', 'v2_checkbox' );
			}

			update_option( '_ddwcac_captcha_type', $type );
			update_option( '_ddwcac_enabled', 'yes' );

			return true;
		}

		/**
		 * Forms step.
		 *
		 * @return void
		 */
		public function forms() {
			$woo    = $this->config['woocommerce_forms'];
			$wp     = $this->config['wordpress_forms'];
			$fields = [
				[ '_ddwcac_wizard_login', __( 'Login', 'advanced-captcha' ), __( 'wp-login.php and My Account, the main brute-force target.', 'advanced-captcha' ), in_array( 'login', $wp, true ) || in_array( 'login', $woo, true ) ],
				[ '_ddwcac_wizard_registration', __( 'Registration', 'advanced-captcha' ), __( 'Stops fake and spam customer accounts.', 'advanced-captcha' ), in_array( 'registration', $woo, true ) || in_array( 'registration', $wp, true ) ],
				[ '_ddwcac_wizard_lost_password', __( 'Lost password', 'advanced-captcha' ), __( 'Prevents reset-email flooding.', 'advanced-captcha' ), in_array( 'lost_password', $woo, true ) || in_array( 'lost_password', $wp, true ) ],
				[ '_ddwcac_wizard_checkout', __( 'Guest checkout', 'advanced-captcha' ), __( 'Blocks card-testing bots on classic and block checkout.', 'advanced-captcha' ), (bool) array_intersect( [ 'guest_checkout', 'users_checkout' ], $woo ) ],
				[ '_ddwcac_wizard_reviews', __( 'Comments & reviews', 'advanced-captcha' ), __( 'Keeps spam out of product reviews and blog comments.', 'advanced-captcha' ), in_array( 'review_product', $woo, true ) || in_array( 'post_comment', $wp, true ) ],
			];

			$active = array_filter( array_keys( ddwcac_get_integrations() ), 'ddwcac_is_integration_active' );
			if ( $active ) {
				$names    = array_map( fn( $key ) => ddwcac_get_integrations()[ $key ][0], $active );
				$fields[] = [ '_ddwcac_wizard_builders', __( 'Your form plugins', 'advanced-captcha' ), implode( ', ', $names ), (bool) array_intersect( $active, $this->config['integrations'] ) ];
			}

			$this->fields( $this->checkboxes( $fields ) );
		}

		/**
		 * Save the forms step into the real options.
		 *
		 * @param array $form_data Submitted fields.
		 * @return true
		 */
		public function save_forms( $form_data ) {
			$on  = $this->checked( $form_data );
			$woo = array_diff( $this->config['woocommerce_forms'], [ 'login', 'registration', 'lost_password', 'guest_checkout', 'users_checkout', 'review_product' ] );
			$wp  = array_diff( $this->config['wordpress_forms'], [ 'login', 'registration', 'lost_password', 'post_comment' ] );
			$map = [
				'_ddwcac_wizard_login'         => [ [ 'login' ], [ 'login' ] ],
				'_ddwcac_wizard_registration'  => [ [ 'registration' ], [ 'registration' ] ],
				'_ddwcac_wizard_lost_password' => [ [ 'lost_password' ], [ 'lost_password' ] ],
				'_ddwcac_wizard_checkout'      => [ in_array( 'users_checkout', $this->config['woocommerce_forms'], true ) ? [ 'users_checkout' ] : [ 'guest_checkout' ], [] ],
				'_ddwcac_wizard_reviews'       => [ [ 'review_product' ], [ 'post_comment' ] ],
			];

			foreach ( $map as $key => $forms ) {
				if ( in_array( $key, $on, true ) ) {
					$woo = array_merge( $woo, $forms[0] );
					$wp  = array_merge( $wp, $forms[1] );
				}
			}

			update_option( '_ddwcac_woocommerce_forms', array_values( array_unique( $woo ) ) );
			update_option( '_ddwcac_wordpress_forms', array_values( array_unique( $wp ) ) );
			update_option( '_ddwcac_integrations', in_array( '_ddwcac_wizard_builders', $on, true ) ? array_keys( ddwcac_get_integrations() ) : [] );

			return true;
		}

		/**
		 * Protection step.
		 *
		 * @return void
		 */
		public function protection() {
			$c = $this->config;

			$fields = $this->checkboxes(
				[
					[ '_ddwcac_honeypot_enabled', __( 'Invisible honeypot', 'advanced-captcha' ), __( 'Rejects bots that fill hidden fields or submit within seconds.', 'advanced-captcha' ), 'yes' === $c['honeypot_enabled'] ],
				]
			);

			// Pro layers, shown so the store owner knows they exist. Disabled and renamed, so they save nothing.
			foreach (
				[
					'lockout'      => [ __( 'Brute-force lockout', 'advanced-captcha' ), __( 'Locks an IP for an hour after 5 failures in 15 minutes.', 'advanced-captcha' ) ],
					'card-testing' => [ __( 'Card-testing shield', 'advanced-captcha' ), __( 'Locks out checkout after 3 failed payments in an hour from one IP or email.', 'advanced-captcha' ) ],
					'disposable'   => [ __( 'Disposable email guard', 'advanced-captcha' ), __( 'Refuses throwaway inboxes on registration, checkout and comments.', 'advanced-captcha' ) ],
					'smart-mode'   => [ __( 'Smart mode', 'advanced-captcha' ), __( 'Only show the CAPTCHA to visitors who already failed something.', 'advanced-captcha' ) ],
				] as $id => $row
			) {
				$fields[] = ddfw_locked_field(
					[
						'type'           => 'checkbox',
						'id'             => 'wizard-' . $id,
						'label'          => $row[0],
						'checkbox_label' => $row[1],
						'value'          => '',
					],
					'ddwcac'
				);
			}

			$this->fields( $fields );
		}

		/**
		 * Save the protection step.
		 *
		 * @param array $form_data Submitted fields.
		 * @return true
		 */
		public function save_protection( $form_data ) {
			$on = $this->checked( $form_data );

			update_option( '_ddwcac_honeypot_enabled', in_array( '_ddwcac_honeypot_enabled', $on, true ) ? 'yes' : '' );

			return true;
		}

		/**
		 * Checkbox field definitions from [ name, label, description, checked ] rows.
		 *
		 * @param array $rows Rows.
		 * @return array
		 */
		protected function checkboxes( $rows ) {
			return array_map(
				fn( $row ) => [
					'type'           => 'checkbox',
					'name'           => $row[0],
					'label'          => $row[1],
					'checkbox_label' => $row[2],
					'value'          => $row[3] ? 'yes' : '',
				],
				$rows
			);
		}

		/**
		 * Names of the checked wizard boxes.
		 *
		 * @param array $form_data Submitted fields.
		 * @return array
		 */
		protected function checked( $form_data ) {
			return array_keys( array_filter( array_column( (array) $form_data, 'value', 'name' ), fn( $value ) => 'yes' === $value ) );
		}
	}
}
