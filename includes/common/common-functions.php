<?php
/**
 * Callbacks for the common hooks.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Includes\Common;

use DDWCAdvancedCAPTCHA\Helper\Logs\DDWCAC_Logs_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Common_Functions' ) ) {
	/**
	 * Common functions class
	 */
	class DDWCAC_Common_Functions {
		/**
		 * Days the verification log is kept.
		 */
		const LOG_RETENTION_DAYS = 30;

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
		 * Daily log cleanup.
		 *
		 * @return void
		 */
		public function ddwcac_purge_logs() {
			( new DDWCAC_Logs_Helper() )->ddwcac_purge_old_logs( self::LOG_RETENTION_DAYS );
		}

		/**
		 * Return a fresh built-in challenge for the refresh button and stale cached pages.
		 *
		 * @return void
		 */
		public function ddwcac_builtin_challenge() {
			$challenge = ddwcac_create_builtin_challenge();

			wp_send_json_success(
				[
					'image' => ddwcac_get_builtin_image( $challenge['question'] ),
					'token' => $challenge['token'],
				]
			);
		}
	}
}
