<?php
/**
 * This file handles all front end action callbacks.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Includes\Front;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Front_Functions' ) ) {
	/**
	 * Front functions class
	 */
	class DDWCAC_Front_Functions {
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
		 * Error HTML for core WordPress error boxes.
		 *
		 * @param string $message Message.
		 * @return string
		 */
		protected function ddwcac_error_html( $message ) {
			return '<strong>' . esc_html__( 'Error:', 'advanced-captcha' ) . '</strong> ' . esc_html( $message );
		}

		/**
		 * Whether the request is a real form POST from a browser (not XML-RPC, REST or a cron/CLI call).
		 *
		 * @return bool
		 */
		protected function ddwcac_is_form_post() {
			if ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
				return false;
			}

			return isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'];
		}

		/**
		 * Get CAPTCHA shortcode content function
		 *
		 * @return string
		 */
		public function ddwcac_get_captcha_shortcode_content() {
			return wp_kses( ddwcac_get_captcha_field(), ddwcac_kses_captcha() );
		}

		/**
		 * Front scripts enqueue function
		 *
		 * @return void
		 */
		public function ddwcac_front_scripts() {
			ddwcac_register_front_scripts( $this->ddwcac_configuration );
		}

		/**
		 * Display CAPTCHA field on WordPress form function
		 *
		 * @return void
		 */
		public function ddwcac_display_captcha_field_on_wordpress_form() {
			echo wp_kses( ddwcac_get_captcha_field( 'margin-left: -15px' ), ddwcac_kses_captcha() );
		}

		/**
		 * Display CAPTCHA field on WooCommerce form function
		 *
		 * @return void
		 */
		public function ddwcac_display_captcha_field_on_woocommerce_form() {
			echo wp_kses( ddwcac_get_captcha_field(), ddwcac_kses_captcha() );
		}

		/**
		 * Login check function
		 *
		 * @param \WP_User|\WP_Error|null $user User or error.
		 * @return \WP_User|\WP_Error|null
		 */
		public function ddwcac_login_check( $user ) {
			// wp-login.php calls authenticate on first load with an empty form, and XML-RPC/REST app passwords never show a widget.
			if ( ! $this->ddwcac_is_form_post() ) {
				return $user;
			}

			$response = ddwcac_check_captcha_response( 'login' );

			if ( ! $response['success'] ) {
				// Replace any username or password error: the CAPTCHA failed first, and WooCommerce shows only one message.
				$user = new \WP_Error( 'ddwcac_verify_error', $this->ddwcac_error_html( $response['message'] ) );
			}

			return $user;
		}

		/**
		 * Lost password check function
		 *
		 * @param \WP_Error $errors Errors.
		 * @return void
		 */
		public function ddwcac_lost_password_check( $errors ) {
			if ( ! $this->ddwcac_is_form_post() ) {
				return;
			}

			$response = ddwcac_check_captcha_response( 'lost_password' );

			if ( ! $response['success'] ) {
				$errors->add( 'ddwcac_verify_error', $this->ddwcac_error_html( $response['message'] ) );
			}
		}

		/**
		 * Registration errors function
		 *
		 * @param \WP_Error $errors Errors.
		 * @return \WP_Error
		 */
		public function ddwcac_registration_errors( $errors ) {
			return $this->ddwcac_add_error( $errors, 'registration' );
		}

		/**
		 * Account details errors function
		 *
		 * @param \WP_Error $errors Errors.
		 * @return void
		 */
		public function ddwcac_account_details_errors( $errors ) {
			$this->ddwcac_add_error( $errors, 'account_details' );
		}

		/**
		 * Add the CAPTCHA error to a WP_Error when verification fails.
		 *
		 * @param \WP_Error $errors Errors.
		 * @param string    $form   Form key.
		 * @return \WP_Error
		 */
		protected function ddwcac_add_error( $errors, $form ) {
			if ( ! $this->ddwcac_is_form_post() ) {
				return $errors;
			}

			$response = ddwcac_check_captcha_response( $form );

			if ( ! $response['success'] ) {
				$errors->add( 'ddwcac_verify_error', $this->ddwcac_error_html( $response['message'] ) );
			}

			return $errors;
		}

		/**
		 * Add CAPTCHA field on post comment form function
		 *
		 * @param array $defaults Comment form defaults.
		 * @return array
		 */
		public function ddwcac_add_captcha_field_on_post_comment_form( $defaults ) {
			if ( $this->ddwcac_is_comment_protected( function_exists( 'is_product' ) && is_product() ) ) {
				$defaults['submit_field'] = wp_kses( ddwcac_get_captcha_field(), ddwcac_kses_captcha() ) . $defaults['submit_field'];
			}

			return $defaults;
		}

		/**
		 * Whether a comment (or product review) form is protected.
		 *
		 * @param bool $is_review Product review or plain comment.
		 * @return bool
		 */
		protected function ddwcac_is_comment_protected( $is_review ) {
			return $is_review
				? in_array( 'review_product', $this->ddwcac_configuration['woocommerce_forms'], true )
				: in_array( 'post_comment', $this->ddwcac_configuration['wordpress_forms'], true );
		}

		/**
		 * Preprocess comment function
		 *
		 * @param array $comment_data Comment data.
		 * @return array
		 */
		public function ddwcac_preprocess_comment( $comment_data ) {
			// Admin replies, REST and XML-RPC comments have no widget.
			if ( is_admin() || ! $this->ddwcac_is_form_post() ) {
				return $comment_data;
			}

			$is_review = 'review' === ( $comment_data['comment_type'] ?? '' ) || 'product' === get_post_type( $comment_data['comment_post_ID'] ?? 0 );

			if ( ! $this->ddwcac_is_comment_protected( $is_review ) ) {
				return $comment_data;
			}

			$response = ddwcac_check_captcha_response( $is_review ? 'review' : 'comment' );

			if ( ! $response['success'] ) {
				wp_die(
					'<p>' . wp_kses_post( $this->ddwcac_error_html( $response['message'] ) ) . '</p>',
					esc_html__( 'CAPTCHA verification failed', 'advanced-captcha' ),
					[
						'response'  => 403,
						'back_link' => true,
					]
				);
			}

			return $comment_data;
		}

		/**
		 * Register post function
		 *
		 * @param string    $username          Username.
		 * @param string    $email             Email.
		 * @param \WP_Error $validation_errors Errors.
		 * @return void
		 */
		public function ddwcac_register_post( $username, $email, $validation_errors ) {
			// Account creation during checkout is covered by the checkout check.
			if ( function_exists( 'is_checkout' ) && is_checkout() ) {
				return;
			}

			$this->ddwcac_add_error( $validation_errors, 'registration' );
		}

		/**
		 * Verify and add a WooCommerce notice on failure.
		 *
		 * @param string $form Form key.
		 * @return bool Passed.
		 */
		protected function ddwcac_wc_notice_check( $form ) {
			$response = ddwcac_check_captcha_response( $form );

			if ( ! $response['success'] ) {
				wc_add_notice( esc_html( $response['message'] ), 'error' );
			}

			return $response['success'];
		}

		/**
		 * Classic checkout check.
		 *
		 * @return void
		 */
		public function ddwcac_checkout_check() {
			$this->ddwcac_wc_notice_check( 'checkout' );
		}

		/**
		 * Pay for order check.
		 *
		 * @return void
		 */
		public function ddwcac_pay_for_order_check() {
			$this->ddwcac_wc_notice_check( 'pay_for_order' );
		}

		/**
		 * Address forms check.
		 *
		 * @param int    $user_id      User id.
		 * @param string $load_address billing|shipping.
		 * @return void
		 */
		public function ddwcac_verify_and_display_notice_for_address( $user_id, $load_address ) {
			if ( in_array( $load_address . '_address', $this->ddwcac_configuration['woocommerce_forms'], true ) ) {
				$this->ddwcac_wc_notice_check( 'address' );
			}
		}

		/**
		 * WooCommerce Blocks checkout validation function
		 *
		 * @param \WC_Order        $order   Order.
		 * @param \WP_REST_Request $request Request.
		 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException When verification fails, shown by the Store API as a notice.
		 * @throws \Exception On WooCommerce versions without RouteException.
		 * @return void
		 */
		public function ddwcac_blocks_checkout_validation( $order, $request ) {
			$forms = $this->ddwcac_configuration['woocommerce_forms'];

			if ( ! in_array( 'users_checkout', $forms, true ) && ! ( in_array( 'guest_checkout', $forms, true ) && ! is_user_logged_in() ) ) {
				return;
			}

			// The Store API sends JSON; hand the verifier the fields the block checkout script added.
			$data = [];
			foreach ( [ 'g-recaptcha-response', 'cf-turnstile-response', 'h-captcha-response', 'ddwcac_builtin_token', 'ddwcac_builtin_answer', 'ddwcac_hp', 'ddwcac_ts' ] as $field ) {
				$data[ $field ] = sanitize_text_field( (string) $request->get_param( $field ) );
			}

			$response = ddwcac_check_captcha_response( 'checkout', array_filter( $data, 'strlen' ) );

			if ( ! $response['success'] ) {
				if ( class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
					throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'ddwcac_captcha_failed', esc_html( $response['message'] ), 400 );
				}
				throw new \Exception( esc_html( $response['message'] ) );
			}
		}

		/**
		 * Verify for add payment method function
		 *
		 * @param bool $is_valid Current state.
		 * @return bool
		 */
		public function ddwcac_verify_for_add_payment_method( $is_valid = true ) {
			return $this->ddwcac_wc_notice_check( 'add_payment_method' ) ? $is_valid : false;
		}

		/**
		 * Add contact form 7 tag function
		 *
		 * @return void
		 */
		public function ddwcac_add_contact_form_7_tag_button() {
			wpcf7_add_form_tag( [ 'ddwcac_captcha', 'ddwcac_captcha*' ], [ $this, 'ddwcac_get_captcha_field_for_contact_form_7' ] );
		}

		/**
		 * Get CAPTCHA field for contact form 7 function
		 *
		 * @return string
		 */
		public function ddwcac_get_captcha_field_for_contact_form_7() {
			return wp_kses( ddwcac_get_captcha_field(), ddwcac_kses_captcha() ) . '<span class="wpcf7-form-control-wrap" data-name="ddwcac_captcha"></span>';
		}

		/**
		 * Verify Contact Form 7 CAPTCHA function
		 *
		 * @param \WPCF7_Validation $result Result.
		 * @param \WPCF7_FormTag    $tag    Tag.
		 * @return \WPCF7_Validation
		 */
		public function ddwcac_verify_contact_form_7_captcha( $result, $tag ) {
			if ( empty( $tag->name ) ) {
				$tag->name = 'ddwcac_captcha';
			}

			$response = ddwcac_check_captcha_response( 'contact_form_7' );

			if ( ! $response['success'] ) {
				$result->invalidate( $tag, $response['message'] );
			}

			return $result;
		}
	}
}
