<?php
/**
 * Hooks that run on every request type: log cleanup and the built-in challenge refresh.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Includes\Common;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Common_Hooks' ) ) {
	/**
	 * Common hooks class
	 */
	class DDWCAC_Common_Hooks extends DDWCAC_Common_Functions {
		/**
		 * Construct
		 *
		 * @param array $ddwcac_configuration Configuration.
		 */
		public function __construct( $ddwcac_configuration ) {
			parent::__construct( $ddwcac_configuration );

			add_action( 'ddwcac_purge_logs', [ $this, 'ddwcac_purge_logs' ] );

			// The built-in challenge refresh is public and read-only: it hands out a new question.
			add_action( 'wp_ajax_ddwcac_builtin_challenge', [ $this, 'ddwcac_builtin_challenge' ] );
			add_action( 'wp_ajax_nopriv_ddwcac_builtin_challenge', [ $this, 'ddwcac_builtin_challenge' ] );
		}
	}
}
