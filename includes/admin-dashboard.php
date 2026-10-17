<?php
/**
 * Admin dashboard.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Includes;

use DDWCAdvancedCAPTCHA\Templates\Admin;
use DevDiggers\Framework\Includes\DDFW_Assets;
use DevDiggers\Framework\Includes\DDFW_Plugin_Dashboard;
use DevDiggers\Framework\Includes\DDFW_SVG;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Admin_Dashboard' ) ) {
	/**
	 * Admin dashboard class.
	 */
	class DDWCAC_Admin_Dashboard {
		/**
		 * Configuration.
		 *
		 * @var array
		 */
		protected $ddwcac_configuration;

		/**
		 * Dashboard renderer.
		 *
		 * @var DDFW_Plugin_Dashboard
		 */
		protected $dashboard;

		/**
		 * Construct.
		 *
		 * @param array $ddwcac_configuration Configuration.
		 */
		public function __construct( $ddwcac_configuration ) {
			$this->ddwcac_configuration = $ddwcac_configuration;

			add_action( 'admin_enqueue_scripts', [ $this, 'ddwcac_enqueue_admin_scripts' ] );
			add_filter( 'admin_footer_text', [ $this, 'ddwcac_set_admin_footer_text' ], 99 );
			add_filter( 'set-screen-option', [ $this, 'ddwcac_set_screen_option' ], 10, 3 );
			add_filter( 'ddfw_modify_svg_icons', [ $this, 'ddwcac_add_svg_icons' ], 10, 2 );

			$this->ddwcac_add_dashboard_menu();
		}

		/**
		 * Register framework pages.
		 *
		 * @return void
		 */
		public function ddwcac_add_dashboard_menu() {
			ob_start();
			echo wp_kses( ddwcac_get_brand_mark(), ddfw_kses_allowed_svg_tags() );
			esc_html_e( 'Advanced CAPTCHA', 'advanced-captcha' );
			$plugin_name = ob_get_clean();

			$settings = new Admin\Configuration\DDWCAC_Configuration_Template( $this->ddwcac_configuration );
			$tabs     = [];
			foreach (
				[
					'general'       => [ __( 'General', 'advanced-captcha' ), 'general' ],
					'forms'         => [ __( 'Placement', 'advanced-captcha' ), 'ddwcac-forms' ],
					'defense'       => [ __( 'Bot Defense', 'advanced-captcha' ), 'ddwcac-shield' ],
					'fraud'         => [ __( 'Fraud Shield', 'advanced-captcha' ), 'ddwcac-card' ],
					'lockout'       => [ __( 'Lockout & IPs', 'advanced-captcha' ), 'ddwcac-lock' ],
					'notifications' => [ __( 'Notifications', 'advanced-captcha' ), 'ddwcac-bell' ],
					'builtin'       => [ __( 'Built-in CAPTCHA', 'advanced-captcha' ), 'ddwcac-puzzle' ],
					'google'        => [ __( 'Google reCAPTCHA', 'advanced-captcha' ), 'ddwcac-recaptcha' ],
					'cloudflare'    => [ __( 'Cloudflare Turnstile', 'advanced-captcha' ), 'ddwcac-cloudflare' ],
					'hcaptcha'      => [ __( 'hCaptcha', 'advanced-captcha' ), 'ddwcac-hcaptcha' ],
					'advanced'      => [ __( 'Advanced', 'advanced-captcha' ), 'ddwcac-sliders' ],
				] as $tab => $meta
			) {
				$tabs[ $tab ] = [
					'label'    => $meta[0],
					'icon'     => DDFW_SVG::get_svg_icon( $meta[1], true, [ 'size' => 18 ] ),
					'callback' => [ $settings, $tab ],
				];
			}

			$this->dashboard = new DDFW_Plugin_Dashboard(
				[
					'page_title'              => esc_html__( 'Advanced CAPTCHA', 'advanced-captcha' ),
					'menu_title'              => esc_html__( 'Advanced CAPTCHA', 'advanced-captcha' ),
					'plugin_name'             => $plugin_name,
					'slug'                    => DDWCAC_DASHBOARD_PAGE,
					'capability'              => ddwcac_get_capability(),
					'upgrade_url'             => 'https://devdiggers.com/product/woocommerce-advanced-captcha/',
					'screen_options_callback' => [ $this, 'ddwcac_add_screen_options' ],
					'menus'                   => [
						'dashboard'     => [
							'label'    => esc_html__( 'Dashboard', 'advanced-captcha' ),
							'layout'   => 'full-width',
							'callback' => [ $this, 'ddwcac_get_dashboard_template' ],
						],
						'integrations'  => [
							'label'    => esc_html__( 'Integrations', 'advanced-captcha' ),
							'layout'   => 'full-width',
							'callback' => [ $this, 'ddwcac_get_integrations_template' ],
						],
						'firewall'      => [
							'label'    => esc_html__( 'Firewall', 'advanced-captcha' ),
							'layout'   => 'full-width',
							'callback' => [ $this, 'ddwcac_get_firewall_template' ],
						],
						'logs'          => [
							'label'    => esc_html__( 'Logs', 'advanced-captcha' ),
							'layout'   => 'full-width',
							'callback' => [ $this, 'ddwcac_get_logs_template' ],
						],
						'configuration' => [
							'label'  => esc_html__( 'Configuration', 'advanced-captcha' ),
							'layout' => 'sidebar',
							'tabs'   => $tabs,
						],
					],
				]
			);
		}

		/**
		 * Register the logs list columns and per-page option.
		 *
		 * @return void
		 */
		public function ddwcac_add_screen_options() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
			$menu = ! empty( $_GET['menu'] ) ? sanitize_key( wp_unslash( $_GET['menu'] ) ) : 'dashboard';

			if ( 'logs' !== $menu ) {
				return;
			}

			new Admin\Logs\DDWCAC_Logs_List_Template();

			add_screen_option(
				'per_page',
				[
					'label'   => esc_html__( 'Logs Per Page', 'advanced-captcha' ),
					'default' => 20,
					'option'  => 'ddwcac_logs_per_page',
				]
			);
		}

		/**
		 * Save the per-page screen option.
		 *
		 * @param mixed  $status Status.
		 * @param string $option Option.
		 * @param mixed  $value  Value.
		 * @return mixed
		 */
		public function ddwcac_set_screen_option( $status, $option, $value ) {
			return 'ddwcac_logs_per_page' === $option ? absint( $value ) : $status;
		}

		/**
		 * Dashboard template.
		 *
		 * @return void
		 */
		public function ddwcac_get_dashboard_template() {
			new Admin\Dashboard\DDWCAC_Dashboard_Template( $this->ddwcac_configuration );
		}

		/**
		 * Logs template.
		 *
		 * @return void
		 */
		public function ddwcac_get_logs_template() {
			new Admin\Logs\DDWCAC_Logs_Template( $this->ddwcac_configuration );
		}

		/**
		 * Integrations template.
		 *
		 * @return void
		 */
		public function ddwcac_get_integrations_template() {
			new Admin\Integrations\DDWCAC_Integrations_Template();
		}

		/**
		 * Firewall template.
		 *
		 * @return void
		 */
		public function ddwcac_get_firewall_template() {
			new Admin\Firewall\DDWCAC_Firewall_Template( $this->ddwcac_configuration );
		}


		/**
		 * Load plugin assets on its own pages.
		 *
		 * @return void
		 */
		public function ddwcac_enqueue_admin_scripts() {
			if ( ! $this->dashboard->is_a_plugin_page() ) {
				return;
			}

			wp_enqueue_style( 'ddwcac-admin-style', DDWCAC_PLUGIN_URL . 'assets/css/admin.css', [ DDFW_Assets::$framework_css_handle ], filemtime( DDWCAC_PLUGIN_FILE . 'assets/css/admin.css' ) );
			wp_enqueue_script( 'ddwcac-admin-script', DDWCAC_PLUGIN_URL . 'assets/js/admin.js', [ DDFW_Assets::$framework_js_handle ], filemtime( DDWCAC_PLUGIN_FILE . 'assets/js/admin.js' ), true );
			wp_localize_script(
				'ddwcac-admin-script',
				'ddwcacAdminObj',
				[
					'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
					'nonce'            => wp_create_nonce( 'ddwcac_test_captcha' ),
					'integrationNonce' => wp_create_nonce( 'ddwcac_save_integration' ),
					'i18n'             => [
						'saveFailed'   => esc_html__( 'Could not save. Reload the page and try again.', 'advanced-captcha' ),
						'confirmClear' => esc_html__( 'Delete every log entry? This cannot be undone.', 'advanced-captcha' ),
						'testing'      => esc_html__( 'Checking with the provider...', 'advanced-captcha' ),
					],
				]
			);

			// Provider tabs show a live widget, so they need the storefront scripts for that provider.
			$providers = [
				'builtin'    => 'builtin',
				'google'     => 'google',
				'cloudflare' => 'cloudflare',
				'hcaptcha'   => 'h',
			];
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing.
			$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
			if ( isset( $providers[ $tab ] ) ) {
				$config                 = $this->ddwcac_configuration;
				$config['captcha_type'] = $providers[ $tab ];
				ddwcac_register_front_scripts( $config );
			}
		}

		/**
		 * Add plugin icons to the framework icon set.
		 *
		 * @param array $icons Icons.
		 * @param array $args  Icon args.
		 * @return array
		 */
		public function ddwcac_add_svg_icons( $icons, $args ) {
			$size  = ! empty( $args['size'] ) ? absint( $args['size'] ) : 24;
			$open  = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
			$icons = array_merge(
				$icons,
				[
					'ddwcac-shield'     => $open . '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>',
					'ddwcac-forms'      => $open . '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h10M7 13h6M7 17h4"/></svg>',
					'ddwcac-card'       => $open . '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg>',
					'ddwcac-lock'       => $open . '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>',
					'ddwcac-bell'       => $open . '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>',
					'ddwcac-puzzle'     => $open . '<path d="M4 7h4a2 2 0 1 1 4 0h4v4a2 2 0 1 1 0 4v4H4v-4a2 2 0 1 0 0-4z"/></svg>',
					'ddwcac-sliders'    => $open . '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/></svg>',
					// Provider brand marks, in their own colours.
					'ddwcac-recaptcha'  => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="36 -4 180 180" aria-hidden="true" focusable="false"><path fill="#1c3aa9" d="M211.3 84.966a87 87 0 0 0-.087-3.653V12.251l-19.092 19.093C176.494 12.217 152.723 0 126.096 0C98.387 0 73.77 13.226 58.21 33.71l31.295 31.624a41.46 41.46 0 0 1 12.677-14.224c5.464-4.263 13.205-7.75 23.914-7.75c1.294 0 2.293.152 3.026.436c13.269 1.048 24.77 8.37 31.541 18.998L138.51 84.946c28.059-.11 59.756-.174 72.788.015"/><path fill="#4285f4" d="M125.599.003a87 87 0 0 0-3.653.087H52.884l19.093 19.093C52.85 34.809 40.633 58.581 40.633 85.207c0 27.71 13.226 52.327 33.71 67.888l31.624-31.295a41.46 41.46 0 0 1-14.224-12.678c-4.263-5.463-7.75-13.205-7.75-23.914c0-1.293.152-2.292.436-3.026c1.048-13.268 8.37-24.769 18.998-31.54l22.152 22.152c-.11-28.06-.175-59.757.015-72.789"/><path fill="#ababab" d="M40.636 85.205q.005 1.835.087 3.653v69.062l19.093-19.093c15.626 19.127 39.398 31.344 66.024 31.344c27.71 0 52.327-13.226 67.888-33.71l-31.295-31.624a41.46 41.46 0 0 1-12.678 14.224c-5.463 4.263-13.205 7.75-23.914 7.75c-1.293 0-2.292-.152-3.026-.437c-13.268-1.047-24.769-8.37-31.54-18.997l22.152-22.153c-28.06.11-59.757.175-72.789-.014"/></svg>',
					'ddwcac-cloudflare' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 -70 256 256" aria-hidden="true" focusable="false"><path fill="#fbad41" d="M205.52 50.813c-.858 0-1.705.03-2.551.058q-.207.012-.398.094a1.420 1.420 0 0 0-.92.994l-3.628 12.672c-1.565 5.449-.983 10.48 1.646 14.174c2.41 3.416 6.42 5.421 11.289 5.655l19.679 1.194c.585.03 1.092.312 1.4.776a1.920 1.920 0 0 1 .2 1.692a2.500 2.500 0 0 1-2.134 1.662l-20.448 1.193c-11.11.515-23.062 9.58-27.255 20.633l-1.474 3.9a1.092 1.092 0 0 0 .967 1.49h70.425a1.870 1.870 0 0 0 1.81-1.365A51.200 51.200 0 0 0 256 101.828c0-28.160-22.582-50.984-50.449-50.984"/><path fill="#f6821f" d="m174.782 115.362l1.303-4.583c1.568-5.449.987-10.48-1.639-14.173c-2.418-3.417-6.424-5.422-11.296-5.656l-92.312-1.193a1.820 1.820 0 0 1-1.459-.776a1.920 1.920 0 0 1-.203-1.693a2.500 2.500 0 0 1 2.154-1.662l93.173-1.193c11.063-.511 23.015-9.58 27.208-20.633l5.313-14.040c.214-.596.27-1.238.156-1.860C191.126 20.51 166.91 0 137.96 0C111.269 0 88.626 17.403 80.5 41.596a27 27 0 0 0-19.156-5.359C48.549 37.524 38.250 47.946 36.979 60.880a27.900 27.900 0 0 0 .702 9.642C16.773 71.145 0 88.454 0 109.726c0 1.923.137 3.818.413 5.667c.115.897.879 1.570 1.783 1.568h170.480a2.220 2.220 0 0 0 2.106-1.630"/></svg>',
					'ddwcac-hcaptcha'   => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 256 256" aria-hidden="true" focusable="false"><path fill="#0074bf" d="M160.007 223.993h32.007V256h-32.007z" opacity=".5"/><path fill="#0074bf" d="M128 223.993h32.007V256H128z" opacity=".7"/><path fill="#0074bf" d="M96.007 223.993h32.007V256H96.007z" opacity=".7"/><path fill="#0074bf" d="M64 223.993h32.007V256H64z" opacity=".5"/><path fill="#0074bf" d="M192 192h31.993v32.007H192z" opacity=".7"/><path fill="#0074bf" d="M160.007 192h32.007v32.007h-32.007z" opacity=".8"/><path fill="#0074bf" d="M128 192h32.007v32.007H128z"/><path fill="#0074bf" d="M96.007 192h32.007v32.007H96.007z"/><path fill="#0074bf" d="M64 192h32.007v32.007H64z" opacity=".8"/><path fill="#0074bf" d="M32.007 192h32.007v32.007H32.007z" opacity=".7"/><path fill="#0074bf" d="M223.993 160.007H256V192h-32.007z" opacity=".5"/><path fill="#0074bf" d="M192 160.007h31.993V192H192z" opacity=".8"/><path fill="#0074bf" d="M160.007 160.007h32.007V192h-32.007z"/><path fill="#0074bf" d="M128 160.007h32.007V192H128z"/><path fill="#0074bf" d="M96.007 160.007h32.007V192H96.007z"/><path fill="#0074bf" d="M64 160.007h32.007V192H64z"/><path fill="#0074bf" d="M32.007 160.007h32.007V192H32.007z" opacity=".8"/><path fill="#0074bf" d="M0 160.007h32.007V192H0z" opacity=".5"/><path fill="#0074bf" d="M223.993 128H256v32.007h-32.007z" opacity=".7"/><path fill="#0074bf" d="M192 128h31.993v32.007H192z"/><path fill="#0074bf" d="M160.007 128h32.007v32.007h-32.007z"/><path fill="#0074bf" d="M128 128h32.007v32.007H128z"/><path fill="#0074bf" d="M96.007 128h32.007v32.007H96.007z"/><path fill="#0074bf" d="M64 128h32.007v32.007H64z"/><path fill="#0074bf" d="M32.007 128h32.007v32.007H32.007z"/><path fill="#0074bf" d="M0 128h32.007v32.007H0z" opacity=".7"/><path fill="#0074bf" d="M223.993 96.007H256v32.007h-32.007z" opacity=".7"/><path fill="#0074bf" d="M192 96.007h31.993v32.007H192z"/><path fill="#0074bf" d="M160.007 96.007h32.007v32.007h-32.007z"/><path fill="#0074bf" d="M128 96.007h32.007v32.007H128z"/><path fill="#0074bf" d="M96.007 96.007h32.007v32.007H96.007z"/><path fill="#0074bf" d="M64 96.007h32.007v32.007H64z"/><path fill="#0074bf" d="M32.007 96.007h32.007v32.007H32.007z"/><path fill="#0074bf" d="M0 96.007h32.007v32.007H0z" opacity=".7"/><path fill="#0074bf" d="M223.993 64H256v32.007h-32.007z" opacity=".5"/><path fill="#0074bf" d="M192 64h31.993v32.007H192z" opacity=".8"/><path fill="#0074bf" d="M160.007 64h32.007v32.007h-32.007z"/><path fill="#0074bf" d="M128 64h32.007v32.007H128z"/><path fill="#0074bf" d="M96.007 64h32.007v32.007H96.007z"/><path fill="#0074bf" d="M64 64h32.007v32.007H64z"/><path fill="#0074bf" d="M32.007 64h32.007v32.007H32.007z" opacity=".8"/><path fill="#0074bf" d="M0 64h32.007v32.007H0z" opacity=".5"/><path fill="#0074bf" d="M192 32.007h31.993v32.007H192z" opacity=".7"/><path fill="#0074bf" d="M160.007 32.007h32.007v32.007h-32.007z" opacity=".8"/><path fill="#0074bf" d="M128 32.007h32.007v32.007H128z"/><path fill="#0074bf" d="M96.007 32.007h32.007v32.007H96.007z"/><path fill="#0074bf" d="M64 32.007h32.007v32.007H64z" opacity=".8"/><path fill="#0074bf" d="M32.007 32.007h32.007v32.007H32.007z" opacity=".7"/><path fill="#0074bf" d="M160.007 0h32.007v32.007h-32.007z" opacity=".5"/><path fill="#0074bf" d="M128 0h32.007v32.007H128z" opacity=".7"/><path fill="#0074bf" d="M96.007 0h32.007v32.007H96.007z" opacity=".7"/><path fill="#0074bf" d="M64 0h32.007v32.007H64z" opacity=".5"/><path fill="#fff" d="m84.25 117.841l8.912-19.951c3.251-5.12 2.818-11.378-.745-14.9a9 9 0 0 0-1.57-1.26a9.85 9.85 0 0 0-8.223-.934a13.44 13.44 0 0 0-7.409 5.756s-12.19 28.445-16.755 41.285c-4.565 12.841-2.709 36.274 14.778 53.828c18.556 18.557 45.47 22.837 62.605 9.942a10.8 10.8 0 0 0 2.018-1.354l52.825-44.143c2.574-2.154 6.38-6.502 2.953-11.487c-3.427-4.984-9.63-1.557-12.19.095l-30.423 22.12a1.354 1.354 0 0 1-1.964-.231a3.74 3.74 0 0 1 .298-4.47l46.636-39.578c4.063-3.63 4.592-8.9 1.354-12.516s-8.249-3.44-12.299.23l-41.989 32.833a1.87 1.87 0 0 1-2.709-.352a2.587 2.587 0 0 1-.217-3.44l47.557-46.148a9.305 9.305 0 0 0 .474-13.125a8.9 8.9 0 0 0-6.461-2.709a9.48 9.48 0 0 0-6.678 2.71l-48.531 45.578c-1.165 1.165-3.44 0-3.712-1.355c-.1-.493.058-1.004.42-1.354L160.4 70.556a9.17 9.17 0 1 0-12.936-12.976l-56.428 62.307c-2.018 2.018-4.998 2.113-6.42.948a2.113 2.113 0 0 1-.366-2.994"/></svg>',
				]
			);

			return $icons;
		}

		/**
		 * Change the admin footer text on plugin pages.
		 *
		 * @param string $footer_text Footer text.
		 * @return string
		 */
		public function ddwcac_set_admin_footer_text( $footer_text ) {
			if ( ! $this->dashboard->is_a_plugin_page() ) {
				return $footer_text;
			}

			$review_link = '<a href="' . esc_url( 'https://wordpress.org/support/plugin/advanced-captcha/reviews/#new-post' ) . '" target="_blank" title="' . esc_attr__( 'Review', 'advanced-captcha' ) . '" aria-label="' . esc_attr__( 'Review', 'advanced-captcha' ) . '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 32" height="10"><path d="M16 26.534L6.111 32 8 20.422l-8-8.2 11.056-1.688L16 0l4.944 10.534L32 12.223l-8 8.2L25.889 32zm40 0L46.111 32 48 20.422l-8-8.2 11.056-1.688L56 0l4.944 10.534L72 12.223l-8 8.2L65.889 32zm40 0L86.111 32 88 20.422l-8-8.2 11.056-1.688L96 0l4.944 10.534L112 12.223l-8 8.2L105.889 32zm40 0L126.111 32 128 20.422l-8-8.2 11.056-1.688L136 0l4.944 10.534L152 12.223l-8 8.2L145.889 32zm40 0L166.111 32 168 20.422l-8-8.2 11.056-1.688L176 0l4.944 10.534L192 12.223l-8 8.2L185.889 32z" fill="#F5A623" fill-rule="evenodd"/></svg></a>';

			/* translators: %s: star rating link. */
			return sprintf( esc_html__( 'If Advanced CAPTCHA keeps the bots out for you, please leave us a %s rating. It really helps.', 'advanced-captcha' ), $review_link );
		}
	}
}
