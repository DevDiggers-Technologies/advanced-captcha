<?php
/**
 * Form builder hooks. Each is registered only when that plugin is active and switched on in the
 * Integrations screen.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Includes\Front;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Integrations_Hooks' ) ) {
	/**
	 * Integrations hooks class
	 */
	class DDWCAC_Integrations_Hooks extends DDWCAC_Integrations_Functions {
		/**
		 * Construct
		 *
		 * @param array $ddwcac_configuration Configuration.
		 */
		public function __construct( $ddwcac_configuration ) {
			parent::__construct( $ddwcac_configuration );

			foreach ( $ddwcac_configuration['integrations'] as $key ) {
				if ( ddwcac_is_integration_active( $key ) && method_exists( $this, "ddwcac_register_{$key}" ) ) {
					$this->{"ddwcac_register_{$key}"}();
				}
			}
		}

		/**
		 * WPForms.
		 *
		 * @return void
		 */
		protected function ddwcac_register_wpforms() {
			add_action( 'wpforms_display_submit_before', [ $this, 'ddwcac_echo_field' ], 20 );
			add_action( 'wpforms_process_before', [ $this, 'ddwcac_validate_wpforms' ], 10, 2 );
		}

		/**
		 * Fluent Forms.
		 *
		 * @return void
		 */
		protected function ddwcac_register_fluentform() {
			add_action( 'fluentform/render_item_submit_button', [ $this, 'ddwcac_echo_field' ], 1 );
			add_filter( 'fluentform/validation_errors', [ $this, 'ddwcac_validate_fluentform' ], 10, 2 );
		}
	}
}
