<?php
/**
 * Activation handler.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Install' ) ) {
	/**
	 * Install class
	 */
	class DDWCAC_Install {
		/**
		 * On plugin activation function
		 *
		 * @return void
		 */
		public static function ddwcac_on_plugin_activation() {
			// The review notice counts its delay from here.
			add_option( 'ddwcac_installed_at', time() );

			// A fresh install goes through the setup wizard; a store that was already configured never does.
			if ( false === get_option( '_ddwcac_enabled', false ) ) {
				set_transient( 'ddfw_activation_redirect_advanced-captcha', true, 30 );
			} else {
				update_option( 'ddfw_setup_wizard_completed_advanced-captcha', true );
			}

			self::ddwcac_create_tables();

			if ( ! wp_next_scheduled( 'ddwcac_purge_logs' ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ddwcac_purge_logs' );
			}
		}

		/**
		 * Create the verification log table. dbDelta leaves an existing table and its rows alone.
		 *
		 * @return void
		 */
		public static function ddwcac_create_tables() {
			global $wpdb;

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$charset_collate = $wpdb->get_charset_collate();

			dbDelta(
				"CREATE TABLE {$wpdb->prefix}ddwcac_logs (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					form varchar(50) NOT NULL DEFAULT '',
					provider varchar(20) NOT NULL DEFAULT '',
					status varchar(20) NOT NULL DEFAULT '',
					reason varchar(100) NOT NULL DEFAULT '',
					ip varchar(45) NOT NULL DEFAULT '',
					user_agent varchar(255) NOT NULL DEFAULT '',
					url varchar(255) NOT NULL DEFAULT '',
					created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					PRIMARY KEY  (id),
					KEY created_at (created_at),
					KEY status_created (status, created_at),
					KEY ip (ip)
				) $charset_collate;"
			);
		}

		/**
		 * On plugin deactivation function. Clears the hook by name, so it needs nothing autoloaded.
		 *
		 * @return void
		 */
		public static function ddwcac_on_plugin_deactivation() {
			wp_clear_scheduled_hook( 'ddwcac_purge_logs' );
		}
	}
}
