<?php
/**
 * Plugin Name: Advanced CAPTCHA
 * Description: Stop spam, fake signups and login bots on WordPress and WooCommerce with a no-keys built-in CAPTCHA, Cloudflare Turnstile, Google reCAPTCHA or hCaptcha, plus an invisible honeypot and a live security dashboard.
 * Plugin URI: https://devdiggers.com/product/woocommerce-advanced-captcha/
 * Author: DevDiggers
 * Author URI: https://devdiggers.com/
 * Version: 1.0.0
 * Text Domain: advanced-captcha
 * Domain Path: /i18n
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * WC requires at least: 9.0
 * WC tested up to: 11.2
 * DevDiggersPrefix: ddwcac
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Advanced CAPTCHA
 */

// ddwcac: Advanced CAPTCHA.

use DDWCAdvancedCAPTCHA\Includes\DDWCAC_File_Handler;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Free_Init' ) ) {
	/**
	 * Free Init class.
	 */
	final class DDWCAC_Free_Init {
		/**
		 * The single instance of this class.
		 *
		 * @var DDWCAC_Free_Init|null
		 */
		private static $instance = null;

		/**
		 * Class constructor.
		 */
		public function __construct() {
			add_action( 'init', [ $this, 'ddwcac_init' ], 0 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'ddwcac_plugin_settings_link' ] );
			add_filter( 'plugin_row_meta', [ $this, 'ddwcac_plugin_row_meta' ], 10, 2 );
		}

		/**
		 * Point the plugin constants at this directory.
		 *
		 * @return void
		 */
		public static function define_constants() {
			defined( 'DDWCAC_PLUGIN_FILE' ) || define( 'DDWCAC_PLUGIN_FILE', plugin_dir_path( __FILE__ ) );
			defined( 'DDWCAC_PLUGIN_URL' ) || define( 'DDWCAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			defined( 'DDWCAC_PLUGIN_SLUG' ) || define( 'DDWCAC_PLUGIN_SLUG', 'advanced-captcha' );
			defined( 'DDWCAC_DASHBOARD_PAGE' ) || define( 'DDWCAC_DASHBOARD_PAGE', 'ddwcac-dashboard' );
		}

		/**
		 * Create a plugin instance.
		 *
		 * @return static
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();

				/**
				 * Fires when the main plugin instance is loaded.
				 *
				 * @since 1.0.0
				 */
				do_action( 'ddwcac_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Init function. Runs on any WordPress site; the WooCommerce forms simply stay idle without WooCommerce.
		 *
		 * @return void
		 */
		public function ddwcac_init() {
			// WordPress.org loads plugin translations automatically.
			require_once DDWCAC_PLUGIN_FILE . 'includes/global-functions.php';
			require_once DDWCAC_PLUGIN_FILE . 'autoload/autoload.php';
			new DDWCAC_File_Handler();

			// Only on the plugin's own screens (plus AJAX so the dismiss button works).
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing input.
			$on_plugin_page = is_admin() && isset( $_GET['page'] ) && DDWCAC_DASHBOARD_PAGE === sanitize_key( wp_unslash( $_GET['page'] ) );

			if ( ( $on_plugin_page || wp_doing_ajax() ) && class_exists( '\DevDiggers\Framework\Includes\DDFW_Review_Notice' ) ) {
				new \DevDiggers\Framework\Includes\DDFW_Review_Notice(
					[
						'plugin_name'   => esc_html__( 'Advanced CAPTCHA', 'advanced-captcha' ),
						'plugin_prefix' => 'ddwcac',
						'review_url'    => 'https://wordpress.org/support/plugin/advanced-captcha/reviews/#new-post',
					]
				);
			}
		}

		/**
		 * Plugin settings link.
		 *
		 * @param array $links Links array.
		 * @return array
		 */
		public function ddwcac_plugin_settings_link( $links ) {
			$action_links = [
				'dashboard'     => '<a href="' . esc_url( admin_url( 'admin.php?page=' . DDWCAC_DASHBOARD_PAGE ) ) . '">' . esc_html__( 'Dashboard', 'advanced-captcha' ) . '</a>',
				'configuration' => '<a href="' . esc_url( admin_url( 'admin.php?page=' . DDWCAC_DASHBOARD_PAGE . '&menu=configuration' ) ) . '">' . esc_html__( 'Configuration', 'advanced-captcha' ) . '</a>',
				'setup-wizard'  => '<a href="' . esc_url( admin_url( 'admin.php?page=' . DDWCAC_DASHBOARD_PAGE . '&setup-wizard=true' ) ) . '">' . esc_html__( 'Setup Wizard', 'advanced-captcha' ) . '</a>',
				'upgrade'       => '<a href="' . esc_url( 'https://devdiggers.com/product/woocommerce-advanced-captcha/' ) . '" style="color: #0256ff; font-weight: bold;" target="_blank">' . esc_html__( 'Upgrade to Pro', 'advanced-captcha' ) . '</a>',
			];

			return array_merge( $action_links, $links );
		}

		/**
		 * Plugin row meta links.
		 *
		 * @param array  $links Links.
		 * @param string $file  File name.
		 * @return array
		 */
		public function ddwcac_plugin_row_meta( $links, $file ) {
			if ( plugin_basename( __FILE__ ) === $file ) {
				$links = array_merge(
					$links,
					[
						'support'       => '<a href="' . esc_url( 'https://wordpress.org/support/plugin/advanced-captcha/' ) . '" target="_blank">' . esc_html__( 'Support', 'advanced-captcha' ) . '</a>',
						'documentation' => '<a href="' . esc_url( 'https://docs.devdiggers.com/advanced-captcha/' ) . '" target="_blank">' . esc_html__( 'Documentation', 'advanced-captcha' ) . '</a>',
						'review'        => '<a href="' . esc_url( 'https://wordpress.org/support/plugin/advanced-captcha/reviews/#new-post' ) . '" target="_blank">' . esc_html__( 'Rate the plugin', 'advanced-captcha' ) . '</a>',
					]
				);
			}

			return $links;
		}
	}
}

add_action(
	'plugins_loaded',
	function () {
		// Advanced CAPTCHA Pro is active: it carries every Free feature, so this copy stays out.
		if ( class_exists( 'DDWCAC_Init' ) ) {
			return;
		}

		DDWCAC_Free_Init::define_constants();

		DDWCAC_Free_Init::get_instance();

		// Load DevDiggers Framework if not loaded already.
		if ( ! defined( 'DDFW_LOADED' ) && file_exists( DDWCAC_PLUGIN_FILE . 'devdiggers-framework/init.php' ) ) {
			$should_load = true;

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing input.
			if ( ! empty( $_GET['page'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing input.
				$current_page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
				$prefix       = explode( '-', $current_page )[0];

				if ( 0 === strpos( $prefix, 'ddwc' ) || 0 === strpos( $prefix, 'ddwp' ) ) {
					$pro_class  = strtoupper( $prefix ) . '_Init';
					$free_class = strtoupper( $prefix ) . '_Free_Init';

					// Yield to a sibling DevDiggers plugin except on this plugin's own pages.
					if ( class_exists( $free_class ) && ! class_exists( $pro_class ) && 'ddwcac' !== $prefix ) {
						$should_load = false;
					}
				}
			}

			if ( $should_load ) {
				require DDWCAC_PLUGIN_FILE . 'devdiggers-framework/init.php';
			}
		}
	},
	10
);

register_activation_hook(
	__FILE__,
	function () {
		require_once __DIR__ . '/includes/install.php';
		DDWCAC_Install::ddwcac_on_plugin_activation();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		require_once __DIR__ . '/includes/install.php';
		DDWCAC_Install::ddwcac_on_plugin_deactivation();
	}
);

// HPOS and Cart/Checkout Blocks compatibility.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);
