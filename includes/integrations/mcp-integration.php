<?php
/**
 * MCP Integration
 *
 * Puts Advanced CAPTCHA in front of AI assistants (Claude, Cursor, VS Code and any MCP client). Each tool is
 * a WordPress Ability (WordPress 6.9+) announced to the DevDiggers AI & MCP hub (DevDiggers Plugins > AI & MCP),
 * which lists the tools on the shared DevDiggers MCP server, turns them on or off, logs calls and offers a
 * playground.
 *
 * Text here goes to AI clients as JSON, so it is translated but not HTML-escaped. Provider secret keys are never
 * returned: the settings tool only says whether the keys are saved.
 *
 * @package Advanced CAPTCHA
 * @version 1.1.0
 */

namespace DDWCAdvancedCAPTCHA\Includes\Integrations;

use DDWCAdvancedCAPTCHA\Helper\Dashboard\DDWCAC_Dashboard_Helper;
use DDWCAdvancedCAPTCHA\Helper\Integrations\DDWCAC_Integrations_Helper;
use DDWCAdvancedCAPTCHA\Helper\Logs\DDWCAC_Logs_Helper;
use DDWCAdvancedCAPTCHA\Includes\Admin\DDWCAC_Admin_Functions;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_MCP_Integration' ) ) {
	/**
	 * MCP Integration class
	 */
	class DDWCAC_MCP_Integration {
		/**
		 * Ability category and name prefix.
		 */
		const CATEGORY = 'captcha';

		/**
		 * IP lists an assistant may change: list => option key (without the `_ddwcac_` prefix).
		 */
		const IP_LISTS = [
			'trusted' => 'whitelisted_ips',
		];

		/**
		 * Settings an assistant may read, by Configuration tab. Secret keys are never listed.
		 */
		const SETTINGS = [
			'general'     => [ 'enabled', 'captcha_type', 'language', 'error_message', 'excluded_user_roles' ],
			'placement'   => [ 'woocommerce_checkout_form_position', 'shortcode' ],
			'bot_defense' => [ 'honeypot_enabled', 'honeypot_min_time' ],
			'visitor_ip'  => [ 'ip_header' ],
			'advanced'    => [ 'fail_open', 'logging_enabled' ],
			'builtin'     => [ 'builtin_mode', 'builtin_label' ],
			'google'      => [ 'google_captcha_version', 'google_domain', 'google_captcha_size', 'google_captcha_theme', 'google_disable_submit', 'google_v3_score', 'google_hide_badge' ],
			'cloudflare'  => [ 'cloudflare_captcha_theme', 'cloudflare_captcha_size', 'cloudflare_disable_submit' ],
			'hcaptcha'    => [ 'h_captcha_theme', 'h_captcha_size', 'h_disable_submit' ],
		];

		/**
		 * On / off options (stored as 'yes' or ''), as the settings screen reads them.
		 */
		const SWITCHES = '/(enabled|_submit|_badge)$/';

		/**
		 * Configuration.
		 *
		 * @var array
		 */
		protected $ddwcac_configuration;

		/**
		 * Construct.
		 *
		 * @param array $ddwcac_configuration Configuration.
		 */
		public function __construct( $ddwcac_configuration ) {
			$this->ddwcac_configuration = $ddwcac_configuration;

			// The Abilities API arrived in WordPress 6.9.
			if ( ! function_exists( 'wp_register_ability' ) ) {
				return;
			}

			add_action( 'wp_abilities_api_categories_init', [ $this, 'ddwcac_register_category' ] );
			add_action( 'wp_abilities_api_init', [ $this, 'ddwcac_register_abilities' ] );
			add_filter( 'ddfw_mcp_plugins', [ $this, 'ddwcac_add_to_hub' ] );
			add_filter( 'ddfw_mcp_can_connect', [ $this, 'ddwcac_allow_managers' ] );
		}

		/**
		 * Announce Advanced CAPTCHA to the DevDiggers AI & MCP hub.
		 *
		 * @param array $plugins Hub plugins.
		 * @return array
		 */
		public function ddwcac_add_to_hub( $plugins ) {
			$plugins[ self::CATEGORY ] = [
				'name'        => __( 'Advanced CAPTCHA', 'advanced-captcha' ),
				'description' => __( 'Ask which forms are protected and by which CAPTCHA, how many bots were stopped, who is attacking, what the logs say and which IPs are trusted.', 'advanced-captcha' ),
				'category'    => self::CATEGORY,
				'icon'        => ddwcac_get_brand_mark(),
				'url'         => ddwcac_get_dashboard_url(),
			];

			return $plugins;
		}

		/**
		 * People who may manage Advanced CAPTCHA may connect.
		 *
		 * @param bool $allowed Allowed so far.
		 * @return bool
		 */
		public function ddwcac_allow_managers( $allowed ) {
			return $allowed || $this->ddwcac_can_read();
		}

		/**
		 * Category for every Advanced CAPTCHA ability.
		 *
		 * @return void
		 */
		public function ddwcac_register_category() {
			if ( wp_has_ability_category( self::CATEGORY ) ) {
				return;
			}

			wp_register_ability_category(
				self::CATEGORY,
				[
					'label'       => __( 'Advanced CAPTCHA', 'advanced-captcha' ),
					'description' => __( 'Spam and bot protection: protected forms, CAPTCHA provider, verification logs, attack statistics and IP lists.', 'advanced-captcha' ),
				]
			);
		}

		/**
		 * Register every ability.
		 *
		 * @return void
		 */
		public function ddwcac_register_abilities() {
			foreach ( $this->ddwcac_get_abilities() as $name => $args ) {
				$name = self::CATEGORY . '/' . $name;

				if ( wp_has_ability( $name ) ) {
					continue;
				}

				$readonly = ! empty( $args['readonly'] );

				wp_register_ability(
					$name,
					[
						'label'               => $args['label'],
						'description'         => $args['description'],
						'category'            => self::CATEGORY,
						'input_schema'        => $args['input'],
						'output_schema'       => $args['output'],
						'execute_callback'    => $args['callback'],
						'permission_callback' => isset( $args['permission'] ) ? $args['permission'] : [ $this, 'ddwcac_can_read' ],
						'meta'                => [
							'show_in_rest' => true,
							'mcp'          => [
								'public' => true,
								'type'   => isset( $args['type'] ) ? $args['type'] : 'tool',
							],
							'annotations'  => [
								'readonly'        => $readonly,
								'destructive'     => ! $readonly,
								'idempotent'      => $readonly,
								'title'           => $args['label'],
								'readOnlyHint'    => $readonly,
								'destructiveHint' => ! $readonly,
								'idempotentHint'  => $readonly,
								'openWorldHint'   => false,
							],
						],
					]
				);
			}
		}

		/**
		 * Who may read: whoever may open the Advanced CAPTCHA screens.
		 *
		 * @return bool
		 */
		public function ddwcac_can_read() {
			return is_user_logged_in() && current_user_can( ddwcac_get_capability() ); // phpcs:ignore WordPress.WP.Capabilities.Undetermined -- Filtered plugin capability.
		}

		/**
		 * Who may change the IP lists: they apply to every form on the site, wp-login included, so administrators only.
		 *
		 * @return bool
		 */
		public function ddwcac_can_write() {
			return $this->ddwcac_can_read() && current_user_can( 'manage_options' );
		}

		/**
		 * Ability definitions.
		 *
		 * @return array
		 */
		protected function ddwcac_get_abilities() {
			$date   = [
				'type'    => 'string',
				'pattern' => '^\d{4}-\d{2}-\d{2}$',
			];
			$lists  = array_keys( static::IP_LISTS );
			$status = array_merge( [ 'any', 'stopped' ], DDWCAC_Logs_Helper::STATUSES );

			$abilities = [
				'protection-status' => [
					'label'       => __( 'Protection status', 'advanced-captcha' ),
					'description' => __( 'Whether protection is on, which CAPTCHA provider is in use (built-in, Google reCAPTCHA, Cloudflare Turnstile or hCaptcha) and whether it is ready, and every form set (WordPress, WooCommerce, Contact Form 7, form builders) with whether its plugin is installed and which forms are protected, plus the dashboard protection checklist. Start here for "is my site protected".', 'advanced-captcha' ),
					'readonly'    => true,
					'input'       => $this->ddwcac_input_schema( [] ),
					'output'      => $this->ddwcac_object_schema(
						[
							'enabled'      => 'boolean',
							'provider'     => 'object',
							'integrations' => 'array',
							'counts'       => 'object',
							'checklist'    => 'array',
						]
					),
					'callback'    => [ $this, 'ddwcac_protection_status' ],
				],
				'security-report'   => [
					'label'       => __( 'Security report', 'advanced-captcha' ),
					'description' => __( 'Verification numbers for a date range, the same as the Advanced CAPTCHA dashboard: checks, bots stopped, humans passed, block rate and attacking IPs, compared with the previous period of the same length, plus stops per day, the forms attacked most, why attempts were stopped and the top attacking IPs.', 'advanced-captcha' ),
					'readonly'    => true,
					'input'       => $this->ddwcac_input_schema(
						[
							'after'  => $date + [ 'description' => __( 'First day, Y-m-d in the site timezone. Defaults to 29 days before "before".', 'advanced-captcha' ) ],
							'before' => $date + [ 'description' => __( 'Last day, Y-m-d. Defaults to today.', 'advanced-captcha' ) ],
						]
					),
					'output'      => $this->ddwcac_object_schema(
						[
							'period'   => 'object',
							'totals'   => 'object',
							'previous' => 'object',
						]
					),
					'callback'    => [ $this, 'ddwcac_security_report' ],
				],
				'list-logs'         => [
					'label'       => __( 'List verification logs', 'advanced-captcha' ),
					'description' => __( 'The verification log, newest first, as on Advanced CAPTCHA > Logs: each check with its date, form, provider, result, reason, IP, page and browser. Filter by result ("stopped" means every result except passed), by form key (from captcha-protection-status or the "form" values here) or by part of an IP, reason or page URL.', 'advanced-captcha' ),
					'readonly'    => true,
					'input'       => $this->ddwcac_input_schema(
						[
							'status'   => [
								'type'    => 'string',
								'enum'    => $status,
								'default' => 'any',
							],
							'form'     => [
								'type'        => 'string',
								'description' => __( 'Form key, e.g. login, registration, checkout, comment.', 'advanced-captcha' ),
							],
							'search'   => [
								'type'        => 'string',
								'description' => __( 'Part of an IP address, reason code or page URL.', 'advanced-captcha' ),
							],
							'page'     => [
								'type'    => 'integer',
								'minimum' => 1,
								'default' => 1,
							],
							'per_page' => [
								'type'    => 'integer',
								'minimum' => 1,
								'maximum' => 100,
								'default' => 20,
							],
						]
					),
					'output'      => $this->ddwcac_object_schema(
						[
							'logs'        => 'array',
							'total'       => 'integer',
							'total_pages' => 'integer',
							'page'        => 'integer',
						]
					),
					'callback'    => [ $this, 'ddwcac_list_logs' ],
				],
				'firewall-status'   => [
					'label'       => __( 'Firewall status', 'advanced-captcha' ),
					'description' => __( 'The Firewall screen: the IP lists (trusted IPs are never challenged; exact IPs, wildcards and CIDR ranges), the visitor IP source, and the repeat offenders of the last 7 days with how often each was stopped and whether it is on a list.', 'advanced-captcha' ),
					'readonly'    => true,
					'input'       => $this->ddwcac_input_schema( [] ),
					'output'      => $this->ddwcac_object_schema(
						[
							'lists'     => 'object',
							'offenders' => 'array',
						]
					),
					'callback'    => [ $this, 'ddwcac_firewall_status' ],
				],
				'get-settings'      => [
					'label'       => __( 'Settings summary', 'advanced-captcha' ),
					'description' => __( 'The Advanced CAPTCHA configuration by tab: provider and language, error message, trusted roles, checkout placement, honeypot, visitor IP source, fail-open and logging, built-in CAPTCHA mode and each hosted provider\'s widget options. Secret keys are never returned; "keys_saved" says whether each hosted provider has both keys.', 'advanced-captcha' ),
					'readonly'    => true,
					'input'       => $this->ddwcac_input_schema( [] ),
					'output'      => $this->ddwcac_object_schema(
						[
							'settings'   => 'object',
							'keys_saved' => 'object',
						]
					),
					'callback'    => [ $this, 'ddwcac_get_settings' ],
				],
				'security-review'   => [
					'type'        => 'prompt',
					'label'       => __( 'Weekly security review', 'advanced-captcha' ),
					'description' => __( 'Review the site\'s bot protection over a period: gaps in which forms are protected, attack trends, the forms and IPs under attack, and what to change.', 'advanced-captcha' ),
					'readonly'    => true,
					'input'       => $this->ddwcac_input_schema(
						[
							'days' => [
								'type'        => 'string',
								'description' => __( 'Days to review, ending today. Defaults to 7.', 'advanced-captcha' ),
							],
						]
					),
					'output'      => $this->ddwcac_object_schema( [ 'messages' => 'array' ] ),
					'callback'    => [ $this, 'ddwcac_prompt_security_review' ],
				],
			];

			// The hub keeps write tools off until the owner turns them on; without the hub there is no switch, so no write tool.
			if ( class_exists( 'DDFW_MCP' ) ) {
				$abilities['update-ip-list'] = [
					'label'       => __( 'Update an IP list', 'advanced-captcha' ),
					'description' => __( 'Add IPs to, or remove them from, an IP list on the Firewall screen, like its Trust IP / Untrust links. Trusted IPs never see a CAPTCHA on any form. Accepts exact IPv4 / IPv6 addresses, IPv4 wildcards (192.168.1.*) and CIDR ranges; anything else is refused. Returns the list after the change.', 'advanced-captcha' ),
					'readonly'    => false,
					'input'       => $this->ddwcac_input_schema(
						[
							'list'   => [
								'type' => 'string',
								'enum' => $lists,
							],
							'action' => [
								'type' => 'string',
								'enum' => [ 'add', 'remove' ],
							],
							'ips'    => [
								'type'        => 'array',
								'items'       => [ 'type' => 'string' ],
								'minItems'    => 1,
								'maxItems'    => 50,
								'description' => __( 'IPs, wildcards or CIDR ranges.', 'advanced-captcha' ),
							],
						],
						[ 'list', 'action', 'ips' ]
					),
					'output'      => $this->ddwcac_object_schema(
						[
							'list'    => 'string',
							'changed' => 'array',
							'rules'   => 'array',
						]
					),
					'callback'    => [ $this, 'ddwcac_update_ip_list' ],
					'permission'  => [ $this, 'ddwcac_can_write' ],
				];
			}

			/**
			 * Advanced CAPTCHA abilities for AI assistants (MCP). Add, change or remove one; names get the "captcha/" prefix.
			 *
			 * @param array $abilities Ability definitions keyed by name.
			 */
			return apply_filters( 'ddwcac_mcp_abilities', $abilities );
		}

		/**
		 * Input schema of an object; an empty call is allowed.
		 *
		 * @param array $properties Properties.
		 * @param array $required   Required properties.
		 * @return array
		 */
		protected function ddwcac_input_schema( $properties, $required = [] ) {
			$schema = [
				'type'                 => 'object',
				'additionalProperties' => false,
				// An object in JSON ( {} ), so MCP clients read it as "no arguments needed".
				'default'              => (object) [],
			];

			if ( $properties ) {
				$schema['properties'] = $properties;
			}

			if ( $required ) {
				$schema['required'] = $required;
				unset( $schema['default'] );
			}

			return $schema;
		}

		/**
		 * Output schema: an object with the given top-level property types.
		 *
		 * @param array $types Property => JSON type.
		 * @return array
		 */
		protected function ddwcac_object_schema( $types ) {
			$properties = [];

			foreach ( $types as $key => $type ) {
				$properties[ $key ] = [ 'type' => $type ];
			}

			return [
				'type'       => 'object',
				'properties' => $properties,
			];
		}

		/*
		 * ---------------------------------------------------------------------------------------------
		 * Shared helpers.
		 * ---------------------------------------------------------------------------------------------
		 */

		/**
		 * Plain text of a label the plugin escapes for HTML.
		 *
		 * @param string $text Text.
		 * @return string
		 */
		protected function ddwcac_plain( $text ) {
			return wp_specialchars_decode( (string) $text, ENT_QUOTES );
		}

		/**
		 * A Y-m-d date from input, or the fallback.
		 *
		 * @param array  $input    Input.
		 * @param string $key      Key.
		 * @param string $fallback Fallback.
		 * @return string
		 */
		protected function ddwcac_date( $input, $key, $fallback ) {
			$value = isset( $input[ $key ] ) ? (string) $input[ $key ] : '';
			$date  = \DateTime::createFromFormat( '!Y-m-d', $value );

			return $date && $date->format( 'Y-m-d' ) === $value ? $value : $fallback;
		}

		/**
		 * Rules of an IP list option, read fresh so a change in this request shows.
		 *
		 * @param string $key Option key without prefix.
		 * @return string[]
		 */
		protected function ddwcac_rules( $key ) {
			return preg_split( '/\s+/', (string) get_option( '_ddwcac_' . $key, '' ), -1, PREG_SPLIT_NO_EMPTY );
		}

		/**
		 * Lists an IP is on.
		 *
		 * @param string $ip IP.
		 * @return string[]
		 */
		protected function ddwcac_ip_lists( $ip ) {
			$on = [];

			foreach ( static::IP_LISTS as $list => $key ) {
				if ( ddwcac_ip_in_list( $ip, $this->ddwcac_rules( $key ) ) ) {
					$on[] = $list;
				}
			}

			return $on;
		}

		/**
		 * Stats of a date range from the logs helper, shaped for output.
		 *
		 * @param array $stats Range stats.
		 * @return array
		 */
		protected function ddwcac_totals( $stats ) {
			$total  = $stats['passed'] + $stats['stopped'];
			$totals = [
				'verifications' => (int) $total,
				'stopped'       => (int) $stats['stopped'],
				'passed'        => (int) $stats['passed'],
				'block_rate'    => $total ? round( $stats['stopped'] / $total * 100, 1 ) : 0,
				'attacking_ips' => (int) $stats['unique_ips'],
			];

			if ( isset( $stats['locked'] ) ) {
				$totals['lockouts'] = (int) $stats['locked'];
			}

			return $totals;
		}

		/*
		 * ---------------------------------------------------------------------------------------------
		 * Abilities.
		 * ---------------------------------------------------------------------------------------------
		 */

		/**
		 * Protection status.
		 *
		 * @return array
		 */
		public function ddwcac_protection_status() {
			$config    = $this->ddwcac_configuration;
			$type      = $config['captcha_type'];
			$helper    = new DDWCAC_Integrations_Helper();
			$catalog   = $helper->ddwcac_get_catalog();
			$counts    = $helper->ddwcac_get_counts( $catalog );
			$dashboard = ( new DDWCAC_Dashboard_Helper( $config ) )->ddwcac_get_dashboard_data();
			$providers = [
				'builtin'    => __( 'Built-in CAPTCHA', 'advanced-captcha' ),
				'google'     => __( 'Google reCAPTCHA', 'advanced-captcha' ),
				'cloudflare' => __( 'Cloudflare Turnstile', 'advanced-captcha' ),
				'h'          => __( 'hCaptcha', 'advanced-captcha' ),
			];
			$provider  = [
				'type'  => $type,
				'name'  => isset( $providers[ $type ] ) ? $providers[ $type ] : $type,
				'ready' => ddwcac_is_provider_ready( $type, $config ),
			];

			if ( 'google' === $type ) {
				$provider['version'] = $config['google_captcha_version'];
			} elseif ( 'builtin' === $type ) {
				$provider['mode'] = $config['builtin_mode'];
			}

			$integrations = [];
			foreach ( $catalog as $key => $card ) {
				$row = [
					'key'       => $key,
					'name'      => $this->ddwcac_plain( $card['name'] ),
					'installed' => (bool) $card['detected'],
					'enabled'   => (bool) $card['enabled'],
					'status'    => $this->ddwcac_plain( $card['status'][1] ),
				];

				if ( 'choices' === $card['type'] ) {
					$row['forms'] = [];
					foreach ( $card['choices'] as $form => $label ) {
						$row['forms'][] = [
							'key'       => $form,
							'name'      => $this->ddwcac_plain( $label ),
							'protected' => in_array( $form, $card['selected'], true ),
						];
					}
				}

				$integrations[] = $row;
			}

			$checklist = [];
			foreach ( $dashboard['health'] as $item ) {
				// Items another edition provides are not this site's checklist.
				if ( ! empty( $item['pro'] ) ) {
					continue;
				}

				$checklist[] = [
					'ok'     => (bool) $item['ok'],
					'title'  => $this->ddwcac_plain( $item['title'] ),
					'detail' => $this->ddwcac_plain( $item['desc'] ),
				];
			}

			return [
				'enabled'      => 'yes' === $config['enabled'],
				'provider'     => $provider,
				'honeypot'     => 'yes' === $config['honeypot_enabled'],
				'fail_open'    => 'yes' === $config['fail_open'],
				'logging'      => 'yes' === $config['logging_enabled'],
				'integrations' => $integrations,
				'counts'       => [
					'plugins_protected'   => (int) $counts['protected'],
					'plugins_unprotected' => (int) $counts['waiting'],
					'forms_protected'     => (int) $counts['forms'],
				],
				'checklist'    => $checklist,
				'url'          => ddwcac_get_dashboard_url( [ 'menu' => 'integrations' ] ),
			];
		}

		/**
		 * Security report for a date range.
		 *
		 * @param array $input Input.
		 * @return array|\WP_Error
		 */
		public function ddwcac_security_report( $input = [] ) {
			$input  = (array) $input;
			$before = $this->ddwcac_date( $input, 'before', current_time( 'Y-m-d' ) );
			$after  = $this->ddwcac_date( $input, 'after', gmdate( 'Y-m-d', strtotime( $before . ' -29 days' ) ) );

			if ( $after > $before ) {
				return new \WP_Error( 'ddwcac_invalid_range', __( 'The start date must be before the end date.', 'advanced-captcha' ), [ 'status' => 400 ] );
			}

			$days      = (int) round( ( strtotime( $before ) - strtotime( $after ) ) / DAY_IN_SECONDS ) + 1;
			$prev_to   = gmdate( 'Y-m-d', strtotime( $after . ' -1 day' ) );
			$prev_from = gmdate( 'Y-m-d', strtotime( $after . ' -' . $days . ' days' ) );
			$logs      = new DDWCAC_Logs_Helper();
			$current   = $logs->ddwcac_get_range_stats( $after, $before );
			$previous  = $logs->ddwcac_get_range_stats( $prev_from, $prev_to );
			$totals    = $this->ddwcac_totals( $current );
			$before_t  = $this->ddwcac_totals( $previous );
			$forms     = ddwcac_get_form_labels();
			$change    = [];

			foreach ( $totals as $key => $value ) {
				if ( 'block_rate' !== $key ) {
					$change[ $key ] = $before_t[ $key ] ? round( ( $value - $before_t[ $key ] ) / $before_t[ $key ] * 100, 1 ) : null;
				}
			}

			$by_day = [];
			foreach ( [ 'passed', 'stopped' ] as $key ) {
				foreach ( $current['by_day'][ $key ] as $day => $count ) {
					$by_day[ $day ]         = isset( $by_day[ $day ] ) ? $by_day[ $day ] : [
						'date'    => $day,
						'passed'  => 0,
						'stopped' => 0,
					];
					$by_day[ $day ][ $key ] = (int) $count;
				}
			}
			ksort( $by_day );

			return [
				'period'      => [
					'after'  => $after,
					'before' => $before,
					'days'   => $days,
				],
				'totals'      => $totals,
				'previous'    => [ 'after' => $prev_from, 'before' => $prev_to ] + $before_t, // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound -- Short pair.
				'change_pct'  => $change,
				'by_day'      => array_values( $by_day ),
				'top_forms'   => array_map(
					function ( $row ) use ( $forms ) {
						return [
							'form'    => $row['form'],
							'name'    => $this->ddwcac_plain( isset( $forms[ $row['form'] ] ) ? $forms[ $row['form'] ] : $row['form'] ),
							'stopped' => (int) $row['total'],
						];
					},
					$current['forms']
				),
				'top_reasons' => array_map(
					function ( $row ) {
						return [
							'reason'  => $row['reason'],
							'name'    => $this->ddwcac_plain( ddwcac_get_reason( $row['reason'] ) ),
							'stopped' => (int) $row['total'],
						];
					},
					$current['types']
				),
				'top_ips'     => array_map(
					function ( $row ) {
						return [
							'ip'        => $row['ip'],
							'stopped'   => (int) $row['total'],
							'last_seen' => $row['last_seen'],
							'lists'     => $this->ddwcac_ip_lists( $row['ip'] ),
						];
					},
					$current['ips']
				),
				'logging'     => 'yes' === $this->ddwcac_configuration['logging_enabled'],
			];
		}

		/**
		 * Page of the verification log.
		 *
		 * @param array $input Input.
		 * @return array
		 */
		public function ddwcac_list_logs( $input = [] ) {
			$input    = (array) $input;
			$page     = max( 1, isset( $input['page'] ) ? absint( $input['page'] ) : 1 );
			$per_page = min( 100, max( 1, isset( $input['per_page'] ) ? absint( $input['per_page'] ) : 20 ) );
			$status   = isset( $input['status'] ) ? sanitize_key( $input['status'] ) : 'any';
			$args     = [
				'status'   => in_array( $status, DDWCAC_Logs_Helper::STATUSES, true ) ? $status : '',
				'form'     => isset( $input['form'] ) ? sanitize_key( $input['form'] ) : '',
				'search'   => isset( $input['search'] ) ? sanitize_text_field( $input['search'] ) : '',
				'per_page' => $per_page,
				'offset'   => ( $page - 1 ) * $per_page,
			];
			$helper   = new DDWCAC_Logs_Helper();
			$forms    = ddwcac_get_form_labels();
			$statuses = ddwcac_get_status_labels();

			if ( 'stopped' === $status ) {
				// The helper filters one status at a time; "stopped" is every status but passed, so read them together.
				$rows  = [];
				$total = 0;
				foreach ( array_diff( DDWCAC_Logs_Helper::STATUSES, [ 'passed' ] ) as $one ) {
					$total += $helper->ddwcac_get_logs_count( [ 'status' => $one ] + $args );
					$rows   = array_merge( $rows, $helper->ddwcac_get_logs( [ 'status' => $one, 'offset' => 0, 'per_page' => $page * $per_page ] + $args ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound -- Overrides.
				}
				// ponytail: merges one page-deep slice per status, fine for the 30-day log; a status IN () filter in the helper if it grows.
				usort( $rows, fn( $a, $b ) => (int) $b['id'] - (int) $a['id'] );
				$rows = array_slice( $rows, ( $page - 1 ) * $per_page, $per_page );
			} else {
				$total = $helper->ddwcac_get_logs_count( $args );
				$rows  = $helper->ddwcac_get_logs( $args );
			}

			$logs = array_map(
				function ( $row ) use ( $forms, $statuses ) {
					return [
						'id'         => (int) $row['id'],
						'date'       => $row['created_at'],
						'form'       => $row['form'],
						'form_name'  => $this->ddwcac_plain( isset( $forms[ $row['form'] ] ) ? $forms[ $row['form'] ] : $row['form'] ),
						'provider'   => $row['provider'],
						'status'     => $row['status'],
						'result'     => $this->ddwcac_plain( isset( $statuses[ $row['status'] ] ) ? $statuses[ $row['status'] ] : $row['status'] ),
						'reason'     => $this->ddwcac_plain( ddwcac_get_reason( $row['reason'] ) ),
						'ip'         => $row['ip'],
						// Path only: order keys and reset keys can ride in the query string.
						'page_url'   => (string) preg_replace( '/[?#].*$/', '', (string) $row['url'] ),
						'user_agent' => $row['user_agent'],
					];
				},
				(array) $rows
			);

			return [
				'logs'        => $logs,
				'total'       => (int) $total,
				'total_pages' => (int) ceil( $total / $per_page ),
				'page'        => $page,
				'logging'     => 'yes' === $this->ddwcac_configuration['logging_enabled'],
			];
		}

		/**
		 * Firewall status.
		 *
		 * @return array
		 */
		public function ddwcac_firewall_status() {
			$week  = ( new DDWCAC_Logs_Helper() )->ddwcac_get_range_stats( gmdate( 'Y-m-d', strtotime( '-6 days', current_time( 'timestamp' ) ) ), current_time( 'Y-m-d' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Site-local dates, as stored.
			$lists = [];

			foreach ( static::IP_LISTS as $list => $key ) {
				$lists[ $list ] = $this->ddwcac_rules( $key );
			}

			return [
				'lists'     => $lists,
				'ip_header' => $this->ddwcac_configuration['ip_header'],
				'week'      => $this->ddwcac_totals( $week ),
				'offenders' => array_map(
					function ( $row ) {
						return [
							'ip'        => $row['ip'],
							'stopped'   => (int) $row['total'],
							'last_seen' => $row['last_seen'],
							'lists'     => $this->ddwcac_ip_lists( $row['ip'] ),
						];
					},
					$week['ips']
				),
				'url'       => ddwcac_get_dashboard_url( [ 'menu' => 'firewall' ] ),
			];
		}

		/**
		 * Settings summary from the allow-list.
		 *
		 * @return array
		 */
		public function ddwcac_get_settings() {
			$config   = $this->ddwcac_configuration;
			$defaults = ddwcac_get_option_defaults();
			$settings = [];

			foreach ( static::SETTINGS as $tab => $keys ) {
				foreach ( $keys as $key ) {
					if ( ! array_key_exists( $key, $config ) ) {
						continue;
					}

					$value = $config[ $key ];

					if ( 'yes' === $defaults[ $key ] || ( '' === $defaults[ $key ] && preg_match( static::SWITCHES, $key ) ) ) {
						$value = 'yes' === $value;
					} elseif ( is_int( $defaults[ $key ] ) ) {
						$value = (int) $value;
					}

					$settings[ $tab ][ $key ] = is_string( $value ) ? $this->ddwcac_plain( $value ) : $value;
				}
			}

			$keys_saved = [];
			foreach ( [ 'google', 'cloudflare', 'h' ] as $type ) {
				$keys_saved[ $type ] = ddwcac_is_provider_ready( $type, $config );
			}

			return [
				'settings'   => $settings,
				'keys_saved' => $keys_saved,
				'url'        => ddwcac_get_dashboard_url( [ 'menu' => 'configuration' ] ),
			];
		}

		/**
		 * Add IPs to, or remove them from, an IP list.
		 *
		 * @param array $input Input.
		 * @return array|\WP_Error
		 */
		public function ddwcac_update_ip_list( $input = [] ) {
			$input  = (array) $input;
			$list   = isset( $input['list'] ) ? (string) $input['list'] : '';
			$action = isset( $input['action'] ) ? (string) $input['action'] : '';

			if ( ! isset( static::IP_LISTS[ $list ] ) || ! in_array( $action, [ 'add', 'remove' ], true ) ) {
				return new \WP_Error( 'ddwcac_invalid_list', __( 'Give "list" (one of the lists in captcha-firewall-status) and "action" (add or remove).', 'advanced-captcha' ), [ 'status' => 400 ] );
			}

			$raw   = implode( "\n", array_map( 'strval', (array) ( isset( $input['ips'] ) ? $input['ips'] : [] ) ) );
			$valid = preg_split( '/\s+/', ( new DDWCAC_Admin_Functions( $this->ddwcac_configuration ) )->ddwcac_sanitize_ip_rules( $raw ), -1, PREG_SPLIT_NO_EMPTY );

			if ( ! $valid ) {
				return new \WP_Error( 'ddwcac_invalid_ip', __( 'No valid IP given. Use exact IPv4 / IPv6 addresses, IPv4 wildcards (192.168.1.*) or CIDR ranges (10.0.0.0/8).', 'advanced-captcha' ), [ 'status' => 400 ] );
			}

			// Same storage and merge as the Firewall and Logs screens.
			$key     = static::IP_LISTS[ $list ];
			$rules   = $this->ddwcac_rules( $key );
			$after   = 'add' === $action ? array_values( array_unique( array_merge( $rules, $valid ) ) ) : array_values( array_diff( $rules, $valid ) );
			$changed = 'add' === $action ? array_values( array_diff( $after, $rules ) ) : array_values( array_diff( $rules, $after ) );

			update_option( '_ddwcac_' . $key, implode( "\n", $after ) );

			if ( $changed && function_exists( 'wc_get_logger' ) ) {
				// AI made changes leave a trail under WooCommerce > Status > Logs, next to the hub's activity log.
				wc_get_logger()->info(
					sprintf( 'IP list "%1$s": %2$s %3$s by user #%4$d through MCP.', $list, 'add' === $action ? 'added' : 'removed', implode( ', ', $changed ), get_current_user_id() ),
					[ 'source' => 'ddwcac-mcp' ]
				);
			}

			return [
				'list'    => $list,
				'action'  => $action,
				'changed' => $changed,
				'ignored' => array_values( array_diff( array_map( 'trim', preg_split( '/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY ) ), $changed ) ),
				'rules'   => $after,
			];
		}

		/**
		 * A prompt: one user message.
		 *
		 * @param string $description Description.
		 * @param string $text        Message.
		 * @return array
		 */
		protected function ddwcac_prompt( $description, $text ) {
			return [
				'description' => $description,
				'messages'    => [
					[
						'role'    => 'user',
						'content' => [
							'type' => 'text',
							'text' => $text,
						],
					],
				],
			];
		}

		/**
		 * Weekly security review prompt.
		 *
		 * @param array $input Input.
		 * @return array
		 */
		public function ddwcac_prompt_security_review( $input = [] ) {
			$input  = (array) $input;
			$days   = isset( $input['days'] ) ? min( 365, max( 1, absint( $input['days'] ) ) ) : 7;
			$before = current_time( 'Y-m-d' );
			$after  = gmdate( 'Y-m-d', strtotime( $before . ' -' . ( $days - 1 ) . ' days' ) );

			$text = sprintf(
				/* translators: 1: first day, 2: last day. */
				__(
					'Review my site\'s bot protection from %1$s to %2$s.

Use the Advanced CAPTCHA tools:
1. captcha-protection-status: is protection on, is the provider ready, which installed forms are not protected, and the checklist.
2. captcha-security-report with after %1$s and before %2$s: stops, block rate and attacking IPs against the previous period, the forms attacked and why attempts were stopped.
3. captcha-firewall-status: repeat offenders and the IP lists.
4. captcha-list-logs with status stopped for the busiest form, to see what the attacks look like.

Then write a short review for the site owner:
- Headline: checks, bots stopped and block rate, and the change against the previous period in percent.
- Protection gaps: installed forms or plugins that are not protected, a provider that is not ready, anything off on the checklist.
- Under attack: the forms and IPs hit most, and whether any of them are trusted.
- Up to three changes to make, most important first.
Stick to the numbers the tools return and keep it under 250 words. Say plainly when nothing was stopped or logging is off.',
					'advanced-captcha'
				),
				$after,
				$before
			);

			return $this->ddwcac_prompt( __( 'Weekly security review', 'advanced-captcha' ), $text );
		}
	}
}
