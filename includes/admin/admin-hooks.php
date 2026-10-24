<?php
/**
 * This file handles all admin end action hooks.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Includes\Admin;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Admin_Hooks' ) ) {
	/**
	 * Admin end hook handler class
	 */
	class DDWCAC_Admin_Hooks extends DDWCAC_Admin_Functions {
		/**
		 * Construct
		 *
		 * @param array $ddwcac_configuration Configuration.
		 */
		public function __construct( $ddwcac_configuration ) {
			parent::__construct( $ddwcac_configuration );

			add_action( 'admin_init', [ $this, 'ddwcac_register_settings' ] );
			add_action( 'admin_init', [ $this, 'ddwcac_add_privacy_policy_content' ] );
			add_action( 'admin_notices', [ $this, 'ddwcac_missing_keys_notice' ] );
			// The framework removes every admin notice on its own pages during admin_head; re-add this one afterwards.
			add_action( 'admin_head', fn() => add_action( 'admin_notices', [ $this, 'ddwcac_missing_keys_notice' ] ), 99 );
			add_action( 'admin_init', [ $this, 'ddwcac_handle_bulk_logs' ] );
			add_action( 'admin_post_ddwcac_log_action', [ $this, 'ddwcac_handle_log_action' ] );
			add_action( 'wp_ajax_ddwcac_test_captcha', [ $this, 'ddwcac_test_captcha' ] );
			add_action( 'wp_ajax_ddwcac_save_integration', [ $this, 'ddwcac_save_integration' ] );
			add_action( 'wpcf7_admin_init', [ $this, 'ddwcac_add_tag_generator_button' ], 55 );
		}
	}
}
