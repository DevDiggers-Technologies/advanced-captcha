<?php
/**
 * Integration callbacks: render the widget inside each plugin's form, verify on submit.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Includes\Front;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Integrations_Functions' ) ) {
	/**
	 * Integrations functions class
	 */
	class DDWCAC_Integrations_Functions {
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
		 * Escaped widget markup.
		 *
		 * @return string
		 */
		protected function ddwcac_field() {
			return '<div class="ddwcac-integration-field">' . wp_kses( ddwcac_get_captcha_field(), ddwcac_kses_captcha() ) . '</div>';
		}

		/**
		 * Echo the widget (action hooks).
		 *
		 * @return void
		 */
		public function ddwcac_echo_field() {
			echo $this->ddwcac_field(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in ddwcac_field().
		}

		/**
		 * Run the shared verifier for a form plugin.
		 *
		 * @param string     $form Integration key.
		 * @param array|null $data Fields, when the plugin does not post them normally.
		 * @return array
		 */
		protected function ddwcac_check( $form, $data = null ) {
			return ddwcac_check_captcha_response( $form, $data );
		}

		/**
		 * WPForms.
		 *
		 * @param array $entry     Entry.
		 * @param array $form_data Form data.
		 * @return void
		 */
		public function ddwcac_validate_wpforms( $entry, $form_data ) {
			$response = $this->ddwcac_check( 'wpforms' );

			if ( ! $response['success'] && function_exists( 'wpforms' ) ) {
				wpforms()->obj( 'process' )->errors[ $form_data['id'] ]['header'] = esc_html( $response['message'] );
			}
		}

		/**
		 * Fluent Forms posts its fields as one serialized string, so its parsed copy is verified.
		 *
		 * @param array $errors    Errors.
		 * @param array $form_data Parsed fields.
		 * @return array
		 */
		public function ddwcac_validate_fluentform( $errors, $form_data ) {
			// Fluent drops fields it does not know from $form_data, so read its raw serialized payload too.
			$raw = [];
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Fluent Forms verifies its own nonce; this only reads the CAPTCHA fields.
			if ( isset( $_POST['data'] ) && is_string( $_POST['data'] ) ) {
				wp_parse_str( wp_unslash( $_POST['data'] ), $raw ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- Each field is sanitized by the verifier.
			}

			$response = $this->ddwcac_check( 'fluentform', $raw + (array) $form_data );

			if ( ! $response['success'] ) {
				$errors['ddwcac_captcha'] = [ $response['message'] ];
			}

			return $errors;
		}
	}
}
