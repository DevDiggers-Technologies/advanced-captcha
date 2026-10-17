<?php
/**
 * This file handles all front end action hooks.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Includes\Front;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Front_Hooks' ) ) {
	/**
	 * Front end hooks class
	 */
	class DDWCAC_Front_Hooks extends DDWCAC_Front_Functions {
		/**
		 * Checkout position => hook that renders the widget there.
		 */
		const CHECKOUT_POSITIONS = [
			'before_payment_methods' => 'woocommerce_review_order_before_payment',
			'before_billing_details' => 'woocommerce_before_checkout_billing_form',
			'after_billing_details'  => 'woocommerce_after_checkout_billing_form',
			'before_place_order'     => 'woocommerce_review_order_before_submit',
			'after_place_order'      => 'woocommerce_review_order_after_payment',
		];

		/**
		 * Construct
		 *
		 * @param array $ddwcac_configuration Configuration.
		 */
		public function __construct( $ddwcac_configuration ) {
			parent::__construct( $ddwcac_configuration );

			if ( ! $this->ddwcac_should_protect() ) {
				return;
			}

			add_action( 'wp_enqueue_scripts', [ $this, 'ddwcac_front_scripts' ] );
			add_action( 'login_enqueue_scripts', [ $this, 'ddwcac_front_scripts' ] );

			$wordpress_forms   = $ddwcac_configuration['wordpress_forms'];
			$woocommerce_forms = $ddwcac_configuration['woocommerce_forms'];

			// Login: WordPress and WooCommerce share the authenticate filter, so register it once.
			if ( in_array( 'login', $wordpress_forms, true ) ) {
				add_action( 'login_form', [ $this, 'ddwcac_display_captcha_field_on_wordpress_form' ], 99 );
			}
			if ( in_array( 'login', $woocommerce_forms, true ) ) {
				add_action( 'woocommerce_login_form', [ $this, 'ddwcac_display_captcha_field_on_woocommerce_form' ], 99 );
			}
			if ( in_array( 'login', $wordpress_forms, true ) || in_array( 'login', $woocommerce_forms, true ) ) {
				add_filter( 'authenticate', [ $this, 'ddwcac_login_check' ], 99 );
			}

			// Registration.
			if ( in_array( 'registration', $wordpress_forms, true ) ) {
				add_action( 'register_form', [ $this, 'ddwcac_display_captcha_field_on_wordpress_form' ], 99 );
				add_filter( 'registration_errors', [ $this, 'ddwcac_registration_errors' ], 99 );
			}
			if ( in_array( 'registration', $woocommerce_forms, true ) ) {
				add_action( 'woocommerce_register_form', [ $this, 'ddwcac_display_captcha_field_on_woocommerce_form' ], 99 );
				add_action( 'woocommerce_register_post', [ $this, 'ddwcac_register_post' ], 10, 3 );
			}

			// Lost password.
			if ( in_array( 'lost_password', $wordpress_forms, true ) ) {
				add_action( 'lostpassword_form', [ $this, 'ddwcac_display_captcha_field_on_wordpress_form' ], 99 );
			}
			if ( in_array( 'lost_password', $woocommerce_forms, true ) ) {
				add_action( 'woocommerce_lostpassword_form', [ $this, 'ddwcac_display_captcha_field_on_woocommerce_form' ], 99 );
			}
			if ( in_array( 'lost_password', $wordpress_forms, true ) || in_array( 'lost_password', $woocommerce_forms, true ) ) {
				add_action( 'lostpassword_post', [ $this, 'ddwcac_lost_password_check' ], 99 );
			}

			// Checkout, guest only or every customer.
			if ( ( in_array( 'guest_checkout', $woocommerce_forms, true ) && ! is_user_logged_in() ) || in_array( 'users_checkout', $woocommerce_forms, true ) ) {
				$position = $ddwcac_configuration['woocommerce_checkout_form_position'];
				add_action( self::CHECKOUT_POSITIONS[ $position ] ?? self::CHECKOUT_POSITIONS['before_place_order'], [ $this, 'ddwcac_display_captcha_field_on_woocommerce_form' ] );
				add_action( 'woocommerce_checkout_process', [ $this, 'ddwcac_checkout_check' ], 99 );
				add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before', [ $this, 'ddwcac_front_scripts' ] );
			}

			// The Store API request runs before is_user_logged_in() is meaningful for the check above, so it decides itself.
			add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ $this, 'ddwcac_blocks_checkout_validation' ], 10, 2 );

			if ( in_array( 'add_payment_method', $woocommerce_forms, true ) ) {
				add_action( 'woocommerce_add_payment_method_form_bottom', [ $this, 'ddwcac_display_captcha_field_on_woocommerce_form' ], 99 );
				add_filter( 'woocommerce_add_payment_method_form_is_valid', [ $this, 'ddwcac_verify_for_add_payment_method' ], 99 );
			}
			if ( in_array( 'pay_for_order', $woocommerce_forms, true ) ) {
				add_action( 'woocommerce_pay_order_before_submit', [ $this, 'ddwcac_display_captcha_field_on_woocommerce_form' ], 99 );
				add_action( 'woocommerce_before_pay_action', [ $this, 'ddwcac_pay_for_order_check' ], 99 );
			}
			if ( in_array( 'account_details', $woocommerce_forms, true ) ) {
				add_action( 'woocommerce_edit_account_form', [ $this, 'ddwcac_display_captcha_field_on_woocommerce_form' ], 99 );
				add_action( 'woocommerce_save_account_details_errors', [ $this, 'ddwcac_account_details_errors' ], 99 );
			}
			if ( in_array( 'billing_address', $woocommerce_forms, true ) ) {
				add_action( 'woocommerce_after_edit_address_form_billing', [ $this, 'ddwcac_display_captcha_field_on_woocommerce_form' ], 99 );
			}
			if ( in_array( 'shipping_address', $woocommerce_forms, true ) ) {
				add_action( 'woocommerce_after_edit_address_form_shipping', [ $this, 'ddwcac_display_captcha_field_on_woocommerce_form' ], 99 );
			}
			if ( array_intersect( [ 'billing_address', 'shipping_address' ], $woocommerce_forms ) ) {
				add_action( 'woocommerce_after_save_address_validation', [ $this, 'ddwcac_verify_and_display_notice_for_address' ], 99, 2 );
			}

			// WordPress comments and WooCommerce reviews.
			if ( in_array( 'post_comment', $wordpress_forms, true ) || in_array( 'review_product', $woocommerce_forms, true ) ) {
				add_filter( 'comment_form_defaults', [ $this, 'ddwcac_add_captcha_field_on_post_comment_form' ], 99 );
				add_filter( 'preprocess_comment', [ $this, 'ddwcac_preprocess_comment' ], 99 );
			}

			// Contact Form 7.
			if ( ! empty( $ddwcac_configuration['wpcf7_captcha_enabled'] ) ) {
				add_action( 'wpcf7_init', [ $this, 'ddwcac_add_contact_form_7_tag_button' ] );
				add_filter( 'wpcf7_validate_ddwcac_captcha', [ $this, 'ddwcac_verify_contact_form_7_captcha' ], 10, 2 );
				add_filter( 'wpcf7_validate_ddwcac_captcha*', [ $this, 'ddwcac_verify_contact_form_7_captcha' ], 10, 2 );
			}

			if ( $ddwcac_configuration['integrations'] ) {
				new DDWCAC_Integrations_Hooks( $ddwcac_configuration );
			}

			add_shortcode( str_replace( [ '[', ']', ' ' ], '', $ddwcac_configuration['shortcode'] ), [ $this, 'ddwcac_get_captcha_shortcode_content' ] );
		}

		/**
		 * Whether this visitor gets a CAPTCHA at all.
		 *
		 * @return bool
		 */
		protected function ddwcac_should_protect() {
			$config = $this->ddwcac_configuration;
			$type   = $config['captcha_type'];

			// Without both keys no widget can render, so enforcing would lock everyone out (admin login included).
			if ( ! ddwcac_is_provider_ready( $type, $config ) ) {
				return false;
			}

			if ( array_intersect( (array) wp_get_current_user()->roles, $config['excluded_user_roles'] ) ) {
				return false;
			}

			$protect = ! ddwcac_ip_in_list( ddwcac_get_ip_address(), $config['whitelisted_ips'] );

			/**
			 * Filter whether the current request is protected by CAPTCHA.
			 *
			 * @since 1.0.0
			 *
			 * @param bool $protect Protect or skip.
			 */
			return (bool) apply_filters( 'ddwcac_should_protect_request', $protect );
		}
	}
}
