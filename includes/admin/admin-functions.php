<?php
/**
 * This file handles all admin end action callbacks.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Includes\Admin;

use DDWCAdvancedCAPTCHA\Helper\Integrations\DDWCAC_Integrations_Helper;
use DDWCAdvancedCAPTCHA\Helper\Logs\DDWCAC_Logs_Helper;
use DDWCAdvancedCAPTCHA\Helper\Verification\DDWCAC_Verification_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Admin_Functions' ) ) {
	/**
	 * Admin Functions Class
	 */
	class DDWCAC_Admin_Functions {
		/**
		 * Configuration tab => option keys (without the `_ddwcac_` prefix). One settings group per tab,
		 * so saving a tab never empties another tab's options.
		 */
		const GROUPS = [
			'general'      => [ 'enabled', 'captcha_type', 'language', 'error_message', 'excluded_user_roles' ],
			'forms'        => [ 'woocommerce_checkout_form_position', 'shortcode' ],
			// Saved from the Integrations and Firewall screens, never through options.php.
			'integrations' => [ 'wordpress_forms', 'woocommerce_forms', 'wpcf7_captcha_enabled', 'integrations' ],
			'firewall'     => [ 'whitelisted_ips' ],
			'defense'      => [ 'honeypot_enabled', 'honeypot_min_time' ],
			'lockout'      => [ 'ip_header' ],
			'advanced'     => [ 'fail_open', 'logging_enabled' ],
			'builtin'      => [ 'builtin_mode', 'builtin_label' ],
			'google'       => [ 'google_captcha_version', 'google_domain', 'google_captcha_size', 'google_captcha_theme', 'google_disable_submit', 'google_v3_score', 'google_hide_badge', 'google_site_key', 'google_site_secret' ],
			'cloudflare'   => [ 'cloudflare_captcha_theme', 'cloudflare_captcha_size', 'cloudflare_disable_submit', 'cloudflare_site_key', 'cloudflare_site_secret' ],
			'hcaptcha'     => [ 'h_captcha_theme', 'h_captcha_size', 'h_disable_submit', 'h_site_key', 'h_site_secret' ],
		];

		/**
		 * Single-choice options and their allowed values; the first is the fallback.
		 */
		const CHOICES = [
			'captcha_type'                       => [ 'builtin', 'google', 'cloudflare', 'h' ],
			'language'                           => [ 'auto', 'ar', 'de', 'en', 'es', 'fa', 'fr', 'hi', 'id', 'it', 'ja', 'ko', 'nl', 'pl', 'pt-BR', 'ru', 'tr', 'uk', 'vi', 'zh-CN', 'zh-TW' ],
			'woocommerce_checkout_form_position' => [ 'before_place_order', 'before_payment_methods', 'before_billing_details', 'after_billing_details', 'after_place_order' ],
			'ip_header'                          => [ 'REMOTE_ADDR', 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP' ],
			'builtin_mode'                       => [ 'math', 'text' ],
			'google_captcha_version'             => [ 'v2_checkbox', 'v2_invisible', 'v3' ],
			'google_domain'                      => [ 'google.com', 'recaptcha.net' ],
			'google_captcha_size'                => [ 'normal', 'compact' ],
			'google_captcha_theme'               => [ 'light', 'dark' ],
			'cloudflare_captcha_theme'           => [ 'auto', 'light', 'dark' ],
			'cloudflare_captcha_size'            => [ 'normal', 'flexible', 'compact' ],
			'h_captcha_theme'                    => [ 'light', 'dark' ],
			'h_captcha_size'                     => [ 'normal', 'compact' ],
		];

		/**
		 * Number options: [ min, max ].
		 */
		const NUMBERS = [
			'honeypot_min_time' => [ 0, 30 ],
		];

		/**
		 * Configuration Variable
		 *
		 * @var array
		 */
		protected $ddwcac_configuration;

		/**
		 * Construct
		 *
		 * @param array $ddwcac_configuration Configuration.
		 */
		public function __construct( $ddwcac_configuration ) {
			$this->ddwcac_configuration = $ddwcac_configuration;
		}

		/**
		 * Register settings function
		 *
		 * @return void
		 */
		public function ddwcac_register_settings() {
			foreach ( self::GROUPS as $group => $keys ) {
				// options.php asks for manage_options by default; let whoever can open the screen also save it.
				add_filter(
					"option_page_capability_ddwcac-{$group}-configuration-fields",
					function () {
						return ddwcac_get_capability();
					}
				);

				foreach ( $keys as $key ) {
					register_setting(
						"ddwcac-{$group}-configuration-fields",
						'_ddwcac_' . $key,
						[
							'sanitize_callback' => function ( $value ) use ( $key ) {
								return $this->sanitize_setting( '_ddwcac_' . $key, $value );
							},
						]
					);
				}
			}
		}

		/**
		 * Suggested privacy policy text under Settings > Privacy.
		 *
		 * @return void
		 */
		public function ddwcac_add_privacy_policy_content() {
			if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
				return;
			}

			$content  = '<p>' . esc_html__( 'When you submit a form protected by Advanced CAPTCHA (for example login, registration, comments, contact forms or checkout), we record the form, the result of the check, your IP address, your browser user agent and the page address to detect spam and abuse. These records are deleted automatically after 30 days.', 'advanced-captcha' ) . '</p>';
			$content .= '<p>' . esc_html__( 'If this site uses Cloudflare Turnstile, Google reCAPTCHA or hCaptcha, the provider\'s script loads on pages with a protected form, and your IP address and the challenge response are sent to that provider to confirm you are human. Their own privacy policy applies.', 'advanced-captcha' ) . '</p>';

			wp_add_privacy_policy_content( esc_html__( 'Advanced CAPTCHA', 'advanced-captcha' ), wp_kses_post( $content ) );
		}

		/**
		 * Allowed values for list options.
		 *
		 * @param string $key Option key.
		 * @return array|null
		 */
		protected function ddwcac_get_list_choices( $key ) {
			switch ( $key ) {
				case 'excluded_user_roles':
					return array_keys( get_editable_roles() );
				case 'wordpress_forms':
					return [ 'login', 'registration', 'lost_password', 'post_comment' ];
				case 'woocommerce_forms':
					return [ 'login', 'registration', 'lost_password', 'users_checkout', 'guest_checkout', 'add_payment_method', 'pay_for_order', 'review_product', 'account_details', 'billing_address', 'shipping_address' ];
				case 'integrations':
					return array_keys( ddwcac_get_integrations() );
			}

			return null;
		}

		/**
		 * Sanitize one saved option. Types come from the defaults map.
		 *
		 * @param string $name  Option name.
		 * @param mixed  $value Submitted value.
		 * @return mixed Sanitized value.
		 */
		public function sanitize_setting( $name, $value ) {
			$key      = substr( $name, strlen( '_ddwcac_' ) );
			$defaults = ddwcac_get_option_defaults();
			$default  = $defaults[ $key ] ?? '';

			if ( is_array( $default ) ) {
				$choices = (array) $this->ddwcac_get_list_choices( $key );
				return array_values( array_unique( array_intersect( array_map( 'sanitize_text_field', (array) $value ), $choices ) ) );
			}

			if ( isset( self::CHOICES[ $key ] ) ) {
				return in_array( $value, self::CHOICES[ $key ], true ) ? $value : self::CHOICES[ $key ][0];
			}

			if ( isset( self::NUMBERS[ $key ] ) ) {
				list( $min, $max ) = self::NUMBERS[ $key ];
				return is_numeric( $value ) ? (int) min( $max, max( $min, (int) $value ) ) : (int) $default;
			}

			if ( 'yes' === $default || '' === $default && preg_match( '/(enabled|_submit|_badge)$/', $key ) ) {
				return in_array( $value, [ 'yes', '1', 'on', 1, true ], true ) ? 'yes' : '';
			}

			switch ( $key ) {
				case 'google_v3_score':
					return is_numeric( $value ) ? (string) min( 1, max( 0, (float) $value ) ) : '0.5';
				case 'whitelisted_ips':
					return $this->ddwcac_sanitize_ip_rules( $value );
				case 'error_message':
					return sanitize_textarea_field( $value );
			}

			return sanitize_text_field( $value );
		}

		/**
		 * Keep only valid IP rules (exact, IPv4 wildcard, CIDR), one per line.
		 *
		 * @param string $value Raw textarea.
		 * @return string
		 */
		public function ddwcac_sanitize_ip_rules( $value ) {
			$rules = preg_split( '/[\s,]+/', sanitize_textarea_field( (string) $value ), -1, PREG_SPLIT_NO_EMPTY );

			$rules = array_filter(
				$rules,
				function ( $rule ) {
					if ( filter_var( $rule, FILTER_VALIDATE_IP ) || preg_match( '/^(\d{1,3}|\*)(\.(\d{1,3}|\*)){3}$/', $rule ) ) {
						return true;
					}
					$parts = explode( '/', $rule );
					return 2 === count( $parts ) && filter_var( $parts[0], FILTER_VALIDATE_IP ) && ctype_digit( $parts[1] );
				}
			);

			return implode( "\n", array_unique( $rules ) );
		}

		/**
		 * Save one card of the Integrations screen.
		 *
		 * @return void
		 */
		public function ddwcac_save_integration() {
			if ( ! current_user_can( ddwcac_get_capability() ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'You are not allowed to change integrations.', 'advanced-captcha' ) ], 403 );
			}

			check_ajax_referer( 'ddwcac_save_integration', 'nonce' );

			$key     = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
			$enabled = isset( $_POST['enabled'] ) && 'yes' === sanitize_key( wp_unslash( $_POST['enabled'] ) );
			$forms   = isset( $_POST['forms'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['forms'] ) ) : [];
			$helper  = new DDWCAC_Integrations_Helper();
			$card    = $helper->ddwcac_save_card( $key, $enabled, $forms );

			if ( ! $card ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Unknown integration.', 'advanced-captcha' ) ], 400 );
			}

			wp_send_json_success(
				[
					'enabled'  => $card['enabled'],
					'selected' => $card['selected'] ?? [],
					'status'   => $card['status'],
					'filter'   => $card['detected'] ? ( $card['enabled'] ? 'protected' : 'waiting' ) : 'missing',
					'counts'   => $helper->ddwcac_get_counts( $helper->ddwcac_get_catalog() ),
				]
			);
		}

		/**
		 * Verify a token from a configuration tab's live preview.
		 *
		 * @return void
		 */
		public function ddwcac_test_captcha() {
			if ( ! current_user_can( ddwcac_get_capability() ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'You are not allowed to do this.', 'advanced-captcha' ) ], 403 );
			}

			check_ajax_referer( 'ddwcac_test_captcha', 'nonce' );

			$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
			if ( ! isset( DDWCAC_Verification_Helper::PROVIDERS[ $type ] ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Unknown provider.', 'advanced-captcha' ) ] );
			}

			$config                 = $this->ddwcac_configuration;
			$config['captcha_type'] = $type;
			$result                 = ( new DDWCAC_Verification_Helper( $config ) )->ddwcac_test( $type, wp_unslash( $_POST ) );
			$success                = 'builtin' === $type ? esc_html__( 'Correct answer. The built-in CAPTCHA is working on this server.', 'advanced-captcha' ) : esc_html__( 'Keys work. The provider confirmed this challenge, so your forms are ready.', 'advanced-captcha' );
			$message                = 'passed' === $result['status']
				? $success
				/* translators: %s: reason the check failed. */
				: sprintf( esc_html__( 'Verification failed: %s.', 'advanced-captcha' ), esc_html( ddwcac_get_reason( $result['reason'] ) ) );

			wp_send_json(
				[
					'success' => 'passed' === $result['status'],
					'data'    => [ 'message' => $message ],
				]
			);
		}

		/**
		 * Add tag generator button function
		 *
		 * @return void
		 */
		public function ddwcac_add_tag_generator_button() {
			if ( ! class_exists( '\WPCF7_TagGenerator' ) ) {
				return;
			}

			\WPCF7_TagGenerator::get_instance()->add( 'ddwcac-captcha', esc_html__( 'Advanced CAPTCHA', 'advanced-captcha' ), [ $this, 'ddwcac_tag_generator_button' ], [ 'version' => '2' ] );
		}

		/**
		 * Tag generator panel function
		 *
		 * @param \WPCF7_ContactForm $contact_form Form.
		 * @param array              $args         Args.
		 * @return void
		 */
		public function ddwcac_tag_generator_button( $contact_form, $args = [] ) {
			?>
			<header class="description-box">
				<h3><?php esc_html_e( 'Advanced CAPTCHA', 'advanced-captcha' ); ?></h3>
				<p><?php esc_html_e( 'Insert the CAPTCHA selected in Advanced CAPTCHA settings. Submissions fail until it is solved.', 'advanced-captcha' ); ?></p>
			</header>
			<footer class="insert-box">
				<div class="flex-container">
					<input type="text" class="code" readonly="readonly" onfocus="this.select()" value="[ddwcac_captcha]" />
					<button type="button" class="button button-primary" data-taggen="insert-tag"><?php esc_html_e( 'Insert Tag', 'advanced-captcha' ); ?></button>
				</div>
			</footer>
			<?php
		}

		/**
		 * Warn on the plugin's own pages when the selected provider has no keys, because protection is off then.
		 *
		 * @return void
		 */
		public function ddwcac_missing_keys_notice() {
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing.
			if ( DDWCAC_DASHBOARD_PAGE !== $page || ! current_user_can( ddwcac_get_capability() ) || ddwcac_is_provider_ready( $this->ddwcac_configuration['captcha_type'], $this->ddwcac_configuration ) ) {
				return;
			}
			?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'CAPTCHA protection is paused: the site key or secret key for the selected provider is empty. Add both keys, or switch to the Built-in CAPTCHA, which needs no keys.', 'advanced-captcha' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Bulk delete from the logs list table, which submits back to its own page.
		 *
		 * @return void
		 */
		public function ddwcac_handle_bulk_logs() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Routing check only; the nonce is verified below before acting.
			$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			$action = isset( $_GET['action'] ) && '-1' !== $_GET['action'] ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ( isset( $_GET['action2'] ) ? sanitize_key( wp_unslash( $_GET['action2'] ) ) : '' );
			// phpcs:enable

			if ( DDWCAC_DASHBOARD_PAGE !== $page || 'delete' !== $action || empty( $_GET['log'] ) ) {
				return;
			}

			if ( ! current_user_can( ddwcac_get_capability() ) ) {
				wp_die( esc_html__( 'You are not allowed to manage CAPTCHA logs.', 'advanced-captcha' ), 403 );
			}

			check_admin_referer( 'bulk-logs' );

			( new DDWCAC_Logs_Helper() )->ddwcac_delete_logs( array_map( 'absint', (array) wp_unslash( $_GET['log'] ) ) );

			wp_safe_redirect(
				ddwcac_get_dashboard_url(
					[
						'menu'          => 'logs',
						'ddwcac-notice' => 'delete-done',
					]
				)
			);
			exit;
		}

		/**
		 * Handle log and trusted list actions: delete, clear, allow, disallow.
		 *
		 * @return void
		 */
		public function ddwcac_handle_log_action() {
			if ( ! current_user_can( ddwcac_get_capability() ) ) {
				wp_die( esc_html__( 'You are not allowed to manage CAPTCHA logs.', 'advanced-captcha' ), 403 );
			}

			check_admin_referer( 'ddwcac_log_action', 'ddwcac_nonce' );

			$task   = isset( $_REQUEST['task'] ) ? sanitize_key( wp_unslash( $_REQUEST['task'] ) ) : '';
			$ids    = isset( $_REQUEST['log'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['log'] ) ) : [];
			$helper = new DDWCAC_Logs_Helper();
			$notice = 'done';

			switch ( $task ) {
				case 'delete':
					$helper->ddwcac_delete_logs( $ids );
					break;
				case 'clear':
					$helper->ddwcac_clear_logs();
					break;
				case 'allow':
				case 'disallow':
					$raw   = isset( $_REQUEST['ip'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ip'] ) ) : '';
					$input = preg_split( '/\s+/', $this->ddwcac_sanitize_ip_rules( rawurldecode( $raw ) ), -1, PREG_SPLIT_NO_EMPTY );
					if ( ! $input ) {
						$notice = 'invalid-ip';
						break;
					}
					$rules = preg_split( '/\s+/', (string) get_option( '_ddwcac_whitelisted_ips', '' ), -1, PREG_SPLIT_NO_EMPTY );
					$rules = 'allow' === $task ? array_merge( $rules, $input ) : array_diff( $rules, $input );
					update_option( '_ddwcac_whitelisted_ips', implode( "\n", array_unique( $rules ) ) );
					break;
				default:
					$notice = 'unknown';
			}

			$redirect = wp_get_referer();
			$redirect = $redirect ? $redirect : ddwcac_get_dashboard_url( [ 'menu' => 'logs' ] );

			wp_safe_redirect( add_query_arg( 'ddwcac-notice', $task . '-' . $notice, remove_query_arg( [ 'ddwcac-notice', 'action', 'action2', 'log', '_wpnonce', 'ddwcac_nonce' ], $redirect ) ) );
			exit;
		}
	}
}
