<?php
/**
 * Dashboard data.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Helper\Dashboard;

use DDWCAdvancedCAPTCHA\Helper\Integrations\DDWCAC_Integrations_Helper;
use DDWCAdvancedCAPTCHA\Helper\Logs\DDWCAC_Logs_Helper;
use DevDiggers\Framework\Includes\DDFW_Dashboard_Data;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Dashboard_Helper' ) ) {
	/**
	 * Dashboard helper class
	 */
	class DDWCAC_Dashboard_Helper {
		/**
		 * Configuration
		 *
		 * @var array
		 */
		protected $configuration;

		/**
		 * Construct
		 *
		 * @param array $configuration Configuration.
		 */
		public function __construct( $configuration ) {
			$this->configuration = $configuration;
		}

		/**
		 * All data the dashboard renders.
		 *
		 * @return array
		 */
		public function ddwcac_get_dashboard_data() {
			$logs     = new DDWCAC_Logs_Helper();
			$range    = DDFW_Dashboard_Data::get_date_range();
			$previous = $this->ddwcac_get_previous_range( $range );
			$current  = $logs->ddwcac_get_range_stats( $range['from'], $range['to'] );
			$before   = $logs->ddwcac_get_range_stats( $previous['from'], $previous['to'] );
			$labels   = ddwcac_get_form_labels();

			$total        = $current['passed'] + $current['stopped'];
			$total_before = $before['passed'] + $before['stopped'];
			$rate         = $total ? round( $current['stopped'] / $total * 100, 1 ) : 0;
			$rate_before  = $total_before ? round( $before['stopped'] / $total_before * 100, 1 ) : 0;

			$forms = array_map(
				function ( $row ) use ( $labels ) {
					return [
						'label' => $labels[ $row['form'] ] ?? $row['form'],
						'value' => (int) $row['total'],
					];
				},
				$current['forms']
			);

			$types = array_map(
				fn( $row ) => [
					'label' => ddwcac_get_reason( $row['reason'] ),
					'value' => (int) $row['total'],
				],
				$current['types']
			);

			return [
				'date_range' => $range,
				'summary'    => [
					'total'   => $this->ddwcac_make_summary( $total, $total_before ),
					'stopped' => $this->ddwcac_make_summary( $current['stopped'], $before['stopped'] ),
					'passed'  => $this->ddwcac_make_summary( $current['passed'], $before['passed'] ),
					'rate'    => $this->ddwcac_make_summary( $rate, $rate_before ),
					'ips'     => $this->ddwcac_make_summary( $current['unique_ips'], $before['unique_ips'] ),
				],
				'charts'     => [
					'trend' => DDFW_Dashboard_Data::build_time_series( $range['from'], $range['to'], $current['by_day'] ),
					'forms' => $forms,
					'types' => $types,
				],
				'tables'     => [
					'ips'    => $current['ips'],
					'recent' => $logs->ddwcac_get_recent_stopped( 6 ),
				],
				'health'     => $this->ddwcac_get_health(),
			];
		}

		/**
		 * Protection checklist shown on the dashboard.
		 *
		 * @return array List of [ ok, title, desc, url ].
		 */
		protected function ddwcac_get_health() {
			$config    = $this->configuration;
			$type      = $config['captcha_type'];
			$providers = [
				'google'     => __( 'Google reCAPTCHA', 'advanced-captcha' ),
				'cloudflare' => __( 'Cloudflare Turnstile', 'advanced-captcha' ),
				'h'          => __( 'hCaptcha', 'advanced-captcha' ),
			];
			$tabs      = [
				'builtin'    => 'builtin',
				'google'     => 'google',
				'cloudflare' => 'cloudflare',
				'h'          => 'hcaptcha',
			];
			$helper    = new DDWCAC_Integrations_Helper();
			$counts    = $helper->ddwcac_get_counts( $helper->ddwcac_get_catalog() );
			$forms     = $counts['forms'];
			$int_url   = ddwcac_get_dashboard_url( [ 'menu' => 'integrations' ] );
			$checkout  = array_intersect( [ 'users_checkout', 'guest_checkout' ], $config['woocommerce_forms'] );
			$tab_url   = function ( $tab ) {
				return ddwcac_get_dashboard_url(
					[
						'menu' => 'configuration',
						'tab'  => $tab,
					]
				);
			};

			return [
				[
					'ok'    => 'yes' === $config['enabled'],
					'title' => __( 'Protection switched on', 'advanced-captcha' ),
					'desc'  => __( 'Advanced CAPTCHA is enabled in General settings.', 'advanced-captcha' ),
					'url'   => $tab_url( 'general' ),
				],
				[
					'ok'    => ddwcac_is_provider_ready( $type, $config ),
					/* translators: %s: provider name. */
					'title' => 'builtin' === $type ? __( 'Built-in CAPTCHA active', 'advanced-captcha' ) : sprintf( __( '%s keys saved', 'advanced-captcha' ), $providers[ $type ] ?? $type ),
					'desc'  => 'builtin' === $type ? __( 'Self-hosted, so it needs no keys. Switch to a hosted provider any time.', 'advanced-captcha' ) : __( 'Both the site key and secret are required before any widget shows.', 'advanced-captcha' ),
					'url'   => $tab_url( $tabs[ $type ] ?? 'general' ),
				],
				[
					'ok'    => $forms > 0,
					/* translators: %d: number of protected forms. */
					'title' => sprintf( _n( '%d form protected', '%d forms protected', $forms, 'advanced-captcha' ), $forms ),
					'desc'  => __( 'Login, registration and store forms bots target.', 'advanced-captcha' ),
					'url'   => $tab_url( 'forms' ),
				],
				[
					'ok'    => ! empty( $checkout ),
					'title' => __( 'Checkout protected', 'advanced-captcha' ),
					'desc'  => __( 'Blocks card-testing bots on classic and block checkout.', 'advanced-captcha' ),
					'url'   => $tab_url( 'forms' ),
				],
				[
					'ok'    => 'yes' === $config['honeypot_enabled'],
					'title' => __( 'Invisible honeypot on', 'advanced-captcha' ),
					'desc'  => __( 'Catches form-filling bots before the CAPTCHA runs.', 'advanced-captcha' ),
					'url'   => $tab_url( 'defense' ),
				],
				[
					'ok'    => false,
					'pro'   => true,
					'title' => __( 'Card-testing shield', 'advanced-captcha' ),
					'desc'  => __( 'Repeated failed payments lock the IP and email out of checkout.', 'advanced-captcha' ),
					'url'   => $tab_url( 'fraud' ),
				],
				[
					'ok'    => false,
					'pro'   => true,
					'title' => __( 'Brute-force lockout', 'advanced-captcha' ),
					'desc'  => __( 'Repeat offenders are locked out instead of retrying forever.', 'advanced-captcha' ),
					'url'   => $tab_url( 'lockout' ),
				],
			];
		}

		/**
		 * Summary value with change against the previous period.
		 *
		 * @param float $value    Current.
		 * @param float $previous Previous.
		 * @return array
		 */
		protected function ddwcac_make_summary( $value, $previous ) {
			$change = $previous > 0 ? ( $value - $previous ) / $previous * 100 : ( $value > 0 ? 100 : 0 );

			return [
				'value'       => $value,
				'change'      => round( $change, 1 ),
				'is_positive' => $value >= $previous,
			];
		}

		/**
		 * Same-length range just before the active one.
		 *
		 * @param array $range Range with from/to.
		 * @return array
		 */
		protected function ddwcac_get_previous_range( $range ) {
			$from = strtotime( $range['from'] );
			$days = max( 1, (int) round( ( strtotime( $range['to'] ) - $from ) / DAY_IN_SECONDS ) + 1 );

			return [
				'from' => gmdate( 'Y-m-d', $from - $days * DAY_IN_SECONDS ),
				'to'   => gmdate( 'Y-m-d', $from - DAY_IN_SECONDS ),
			];
		}
	}
}
