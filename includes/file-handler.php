<?php
/**
 * File handler
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Includes;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_File_Handler' ) ) {
	/**
	 * File handler class
	 */
	class DDWCAC_File_Handler {
		/**
		 * Configuration Variable
		 *
		 * @var array
		 */
		protected $ddwcac_configuration;

		/**
		 * Constructor
		 */
		public function __construct() {
			require_once DDWCAC_PLUGIN_FILE . 'includes/global-functions.php';

			$this->ddwcac_configuration = $this->ddwcac_set_globals();

			// Log cleanup and the built-in challenge refresh run whatever the request type.
			new Common\DDWCAC_Common_Hooks( $this->ddwcac_configuration );

			if ( is_admin() ) {
				new DDWCAC_Admin_Dashboard( $this->ddwcac_configuration );
				new Admin\DDWCAC_Setup_Wizard( $this->ddwcac_configuration );
				new Admin\DDWCAC_Admin_Hooks( $this->ddwcac_configuration );
			}

			if ( 'yes' === $this->ddwcac_configuration['enabled'] ) {
				new Front\DDWCAC_Front_Hooks( $this->ddwcac_configuration );
			}
		}

		/**
		 * Build the configuration from the defaults map.
		 *
		 * @return array
		 */
		public function ddwcac_set_globals() {
			global $ddwcac_configuration;

			$ddwcac_configuration = [];

			foreach ( ddwcac_get_option_defaults() as $key => $default ) {
				$value = get_option( '_ddwcac_' . $key, $default );

				// Lists saved as empty strings by older versions read back as empty lists.
				if ( is_array( $default ) ) {
					$value = array_values( array_filter( (array) $value ) );
				} elseif ( '' === $value && in_array( $key, [ 'error_message', 'builtin_label', 'shortcode' ], true ) ) {
					// An emptied text field falls back to its wording instead of showing nothing.
					$value = $default;
				}

				$ddwcac_configuration[ $key ] = $value;
			}

			// Old versions stored "on" checkboxes as 'yes' only; anything else is off.
			$ddwcac_configuration['enabled'] = ! empty( $ddwcac_configuration['enabled'] ) ? 'yes' : '';

			return $ddwcac_configuration;
		}
	}
}
