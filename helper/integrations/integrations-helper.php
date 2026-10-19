<?php
/**
 * Integrations catalog: every plugin and form set Advanced CAPTCHA can protect, with its saved state.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Helper\Integrations;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Integrations_Helper' ) ) {
	/**
	 * Integrations helper class
	 */
	class DDWCAC_Integrations_Helper {
		/**
		 * Card groups, in display order.
		 *
		 * @return array
		 */
		public function ddwcac_get_groups() {
			return [
				'core'       => __( 'WordPress & WooCommerce', 'advanced-captcha' ),
				'forms'      => __( 'Form Builders', 'advanced-captcha' ),
				'community'  => __( 'Community & Membership', 'advanced-captcha' ),
				'marketing'  => __( 'Newsletter', 'advanced-captcha' ),
				'devdiggers' => __( 'DevDiggers Plugins', 'advanced-captcha' ),
			];
		}

		/**
		 * Every card. A card saves either a list of forms (choices), one entry of the integrations
		 * list (integration) or a yes/no option (toggle).
		 *
		 * @return array
		 */
		public function ddwcac_get_catalog() {
			$builder = function ( $key, $group, $description ) {
				$integration = ddwcac_get_integrations()[ $key ];

				return [
					'name'        => $integration[0],
					'group'       => $group,
					'description' => $description,
					'detected'    => ddwcac_is_integration_active( $key ),
					'type'        => 'integration',
					'option'      => 'integrations',
				];
			};

			$catalog = [
				'wordpress'   => [
					'name'        => 'WordPress',
					'group'       => 'core',
					'description' => __( 'The wp-login.php screens and blog comments, the first place bots try.', 'advanced-captcha' ),
					'detected'    => true,
					'type'        => 'choices',
					'option'      => 'wordpress_forms',
					'choices'     => [
						'login'         => __( 'Login', 'advanced-captcha' ),
						'registration'  => __( 'Registration', 'advanced-captcha' ),
						'lost_password' => __( 'Lost Password', 'advanced-captcha' ),
						'post_comment'  => __( 'Comments', 'advanced-captcha' ),
					],
					'recommended' => [ 'login', 'registration', 'lost_password', 'post_comment' ],
				],
				'woocommerce' => [
					'name'        => 'WooCommerce',
					'group'       => 'core',
					'description' => __( 'My Account, checkout (classic and block) and reviews. Guest Checkout stops card testing without bothering logged in customers.', 'advanced-captcha' ),
					'detected'    => class_exists( 'WooCommerce' ),
					'type'        => 'choices',
					'option'      => 'woocommerce_forms',
					'choices'     => [
						'login'              => __( 'Login', 'advanced-captcha' ),
						'registration'       => __( 'Registration', 'advanced-captcha' ),
						'lost_password'      => __( 'Lost Password', 'advanced-captcha' ),
						'guest_checkout'     => __( 'Guest Checkout', 'advanced-captcha' ),
						'users_checkout'     => __( 'All Users Checkout', 'advanced-captcha' ),
						'pay_for_order'      => __( 'Pay for Order', 'advanced-captcha' ),
						'add_payment_method' => __( 'Add Payment Method', 'advanced-captcha' ),
						'review_product'     => __( 'Product Reviews', 'advanced-captcha' ),
						'account_details'    => __( 'Account Details', 'advanced-captcha' ),
						'billing_address'    => __( 'Billing Address', 'advanced-captcha' ),
						'shipping_address'   => __( 'Shipping Address', 'advanced-captcha' ),
					],
					'recommended' => [ 'login', 'registration', 'lost_password', 'guest_checkout', 'review_product' ],
				],
				'wpcf7'       => [
					'name'        => 'Contact Form 7',
					'group'       => 'forms',
					'description' => __( 'Adds an Advanced CAPTCHA button to the form editor. Insert the [ddwcac_captcha] tag where the widget should appear.', 'advanced-captcha' ),
					'detected'    => defined( 'WPCF7_VERSION' ),
					'type'        => 'toggle',
					'option'      => 'wpcf7_captcha_enabled',
				],
				'wpforms'     => $builder( 'wpforms', 'forms', __( 'Every WPForms form, above the submit button.', 'advanced-captcha' ) ),
				'fluentform'  => $builder( 'fluentform', 'forms', __( 'Every Fluent Forms form, including multi-step ones.', 'advanced-captcha' ) ),
			];

			foreach ( $catalog as $key => $card ) {
				$catalog[ $key ] = $this->ddwcac_with_state( $key, $card );
			}

			return $catalog;
		}

		/**
		 * Plugins protected by Advanced CAPTCHA Pro. Display only: these cards have no switch and nothing
		 * in this plugin hooks into their forms.
		 *
		 * @return array Card key => [ name, group, probe class or constant, description ].
		 */
		public function ddwcac_get_pro_catalog() {
			$cards = [
				'gravityforms'   => [ 'Gravity Forms', 'forms', 'GFForms', __( 'Every Gravity Forms form, checked during its own validation.', 'advanced-captcha' ) ],
				'elementor'      => [ 'Elementor Pro Forms', 'forms', 'ElementorPro\Plugin', __( 'The Elementor Pro Form widget on any page you build.', 'advanced-captcha' ) ],
				'forminator'     => [ 'Forminator', 'forms', 'Forminator', __( 'Forminator forms, above the submit button.', 'advanced-captcha' ) ],
				'formidable'     => [ 'Formidable Forms', 'forms', 'FrmAppHelper', __( 'Formidable Forms, checked on the first validation pass.', 'advanced-captcha' ) ],
				'buddypress'     => [ 'BuddyPress', 'community', 'BuddyPress', __( 'The BuddyPress member registration page.', 'advanced-captcha' ) ],
				'bbpress'        => [ 'bbPress', 'community', 'bbPress', __( 'New topics and replies in bbPress forums.', 'advanced-captcha' ) ],
				'ultimatemember' => [ 'Ultimate Member', 'community', 'UM', __( 'Ultimate Member login, registration and password reset forms.', 'advanced-captcha' ) ],
				'mc4wp'          => [ 'Mailchimp for WordPress', 'marketing', 'MC4WP_Form', __( 'Mailchimp for WordPress signup forms, so fake subscribers never reach your list.', 'advanced-captcha' ) ],
				'affiliates'     => [ __( 'WooCommerce Affiliates', 'advanced-captcha' ), 'devdiggers', 'DDWCAF_PLUGIN_FILE', __( 'Affiliate signup and settings forms from DevDiggers Affiliates.', 'advanced-captcha' ) ],
				'multipos'       => [ 'MultiPOS', 'devdiggers', 'DDWCPOS_PLUGIN_FILE', __( 'The point of sale login screen from DevDiggers MultiPOS.', 'advanced-captcha' ) ],
			];

			$catalog = [];
			foreach ( $cards as $key => $card ) {
				$detected        = class_exists( $card[2] ) || defined( $card[2] );
				$catalog[ $key ] = [
					'name'        => $card[0],
					'group'       => $card[1],
					'description' => $card[3],
					'detected'    => $detected,
					'type'        => 'pro',
					'enabled'     => false,
					'logo'        => $this->ddwcac_get_logo( $key ),
					'status'      => $detected ? [ 'locked', __( 'Installed, needs Pro', 'advanced-captcha' ) ] : [ 'missing', __( 'Not installed', 'advanced-captcha' ) ],
				];
			}

			return $catalog;
		}

		/**
		 * Add the saved state: on/off, the forms selected and the badge.
		 *
		 * @param string $key  Card key.
		 * @param array  $card Card.
		 * @return array
		 */
		protected function ddwcac_with_state( $key, $card ) {
			$card['logo'] = $this->ddwcac_get_logo( $key );

			$value = get_option( '_ddwcac_' . $card['option'], ddwcac_get_option_defaults()[ $card['option'] ] ?? '' );

			switch ( $card['type'] ) {
				case 'choices':
					$card['selected'] = array_values( array_intersect( (array) $value, array_keys( $card['choices'] ) ) );
					$card['enabled']  = ! empty( $card['selected'] );
					break;
				case 'integration':
					$card['enabled'] = in_array( $key, (array) $value, true );
					break;
				default:
					// Older versions stored '1' or 'on'; the front end treats any of them as on, so the card does too.
					$card['enabled'] = in_array( $value, [ 'yes', '1', 'on' ], true );
			}

			if ( ! $card['detected'] ) {
				$card['status'] = [ 'missing', __( 'Not installed', 'advanced-captcha' ) ];
			} elseif ( $card['enabled'] ) {
				$card['status'] = [ 'passed', __( 'Protected', 'advanced-captcha' ) ];
			} else {
				$card['status'] = [ 'locked', __( 'Not protected', 'advanced-captcha' ) ];
			}

			return $card;
		}

		/**
		 * Logo URL: the plugin's own icon shipped in assets/images/brands, or the DevDiggers mark.
		 *
		 * @param string $key Card key.
		 * @return string
		 */
		protected function ddwcac_get_logo( $key ) {
			foreach ( [ 'svg', 'png' ] as $extension ) {
				if ( file_exists( DDWCAC_PLUGIN_FILE . "assets/images/brands/{$key}.{$extension}" ) ) {
					return DDWCAC_PLUGIN_URL . "assets/images/brands/{$key}.{$extension}";
				}
			}

			return function_exists( 'ddfw_get_devdiggers_plugin_menu_icon_src' ) ? ddfw_get_devdiggers_plugin_menu_icon_src() : '';
		}

		/**
		 * Save one card and return its new state.
		 *
		 * @param string $key     Card key.
		 * @param bool   $enabled Master switch.
		 * @param array  $forms   Forms picked, for choice cards. Empty with the switch on means the recommended set.
		 * @return array|false
		 */
		public function ddwcac_save_card( $key, $enabled, $forms = [] ) {
			$catalog = $this->ddwcac_get_catalog();

			if ( ! isset( $catalog[ $key ] ) ) {
				return false;
			}

			$card   = $catalog[ $key ];
			$option = '_ddwcac_' . $card['option'];

			switch ( $card['type'] ) {
				case 'choices':
					$forms = array_values( array_intersect( array_map( 'sanitize_key', (array) $forms ), array_keys( $card['choices'] ) ) );
					update_option( $option, $enabled ? ( $forms ? $forms : $card['recommended'] ) : [] );
					break;
				case 'integration':
					$list = array_diff( (array) get_option( $option, [] ), [ $key ] );
					update_option( $option, $enabled ? array_merge( $list, [ $key ] ) : array_values( $list ) );
					break;
				default:
					update_option( $option, $enabled ? 'yes' : '' );
			}

			return $this->ddwcac_get_catalog()[ $key ];
		}

		/**
		 * Header numbers: plugins protected, plugins found but unprotected, forms covered.
		 *
		 * @param array $catalog Catalog with state.
		 * @return array
		 */
		public function ddwcac_get_counts( $catalog ) {
			$catalog += $this->ddwcac_get_pro_catalog();
			$counts   = [
				'protected' => 0,
				'waiting'   => 0,
				'forms'     => 0,
				'total'     => count( $catalog ),
			];

			foreach ( $catalog as $card ) {
				if ( $card['detected'] && $card['enabled'] ) {
					++$counts['protected'];
					$counts['forms'] += 'choices' === $card['type'] ? count( $card['selected'] ) : 1;
				} elseif ( $card['detected'] ) {
					++$counts['waiting'];
				}
			}

			return $counts;
		}
	}
}
