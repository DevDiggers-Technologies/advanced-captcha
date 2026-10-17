<?php
/**
 * Dashboard template.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Templates\Admin\Dashboard;

use DDWCAdvancedCAPTCHA\Helper\Dashboard\DDWCAC_Dashboard_Helper;
use DevDiggers\Framework\Includes\DDFW_Dashboard;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Dashboard_Template' ) ) {
	/**
	 * Dashboard template class.
	 */
	class DDWCAC_Dashboard_Template {
		/**
		 * Dashboard data.
		 *
		 * @var array
		 */
		protected $data;

		/**
		 * Construct.
		 *
		 * @param array $configuration Configuration.
		 */
		public function __construct( $configuration ) {
			if ( ! class_exists( '\\DevDiggers\\Framework\\Includes\\DDFW_Dashboard' ) ) {
				return;
			}

			$this->data = ( new DDWCAC_Dashboard_Helper( $configuration ) )->ddwcac_get_dashboard_data();

			$this->ddwcac_render();
		}

		/**
		 * Render the dashboard through the framework builder.
		 *
		 * @return void
		 */
		protected function ddwcac_render() {
			$summary = $this->data['summary'];

			new DDFW_Dashboard(
				[
					'columns'       => 5,
					'header'        => [
						/* translators: %s: admin display name. */
						'welcome'  => esc_html__( 'Welcome back, %s! 👋🏻', 'advanced-captcha' ),
						'subtitle' => esc_html__( 'Every bot and spam submission stopped on your site, and where it came from.', 'advanced-captcha' ),
					],
					'summary_cards' => [
						$this->ddwcac_card( esc_html__( 'Verifications', 'advanced-captcha' ), $summary['total'], 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z', true ),
						$this->ddwcac_card( esc_html__( 'Bots Stopped', 'advanced-captcha' ), $summary['stopped'], 'M4.9 4.9l14.2 14.2M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z', false ),
						$this->ddwcac_card( esc_html__( 'Humans Passed', 'advanced-captcha' ), $summary['passed'], 'M20 6 9 17l-5-5', true ),
						$this->ddwcac_card( esc_html__( 'Block Rate', 'advanced-captcha' ), $summary['rate'], 'M3 3v18h18M7 15l4-4 3 3 5-6', false, '%' ),
						$this->ddwcac_card( esc_html__( 'Attacking IPs', 'advanced-captcha' ), $summary['ips'], 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20', false ),
					],
					'charts'        => [
						[
							'id'         => 'verification-trend',
							'title'      => esc_html__( 'Verification Activity', 'advanced-captcha' ),
							'date_label' => $this->data['date_range']['label'],
							'full_width' => true,
							'type'       => 'line',
							'data'       => $this->data['charts']['trend'],
							'x_key'      => 'date',
							'series'     => [
								[
									'key'   => 'stopped',
									'label' => esc_html__( 'Stopped', 'advanced-captcha' ),
									'color' => '#ef4444',
								],
								[
									'key'   => 'passed',
									'label' => esc_html__( 'Passed', 'advanced-captcha' ),
									'color' => '#0256ff',
								],
							],
							'empty'      => [
								'title' => esc_html__( 'No verifications yet', 'advanced-captcha' ),
								'desc'  => esc_html__( 'Once visitors submit a protected form, passed and stopped attempts will be charted here.', 'advanced-captcha' ),
							],
						],
					],
					'widgets'       => [
						[
							'title' => esc_html__( 'Attacks By Form', 'advanced-captcha' ),
							'width' => 'half',
							'chart' => [
								'id'    => 'attacks-by-form',
								'type'  => 'doughnut',
								'data'  => $this->data['charts']['forms'],
								'empty' => [
									'title' => esc_html__( 'Nothing stopped yet', 'advanced-captcha' ),
									'desc'  => esc_html__( 'See which form bots hit hardest, so you know where protection matters.', 'advanced-captcha' ),
								],
							],
						],
						[
							'title' => esc_html__( 'Threats By Type', 'advanced-captcha' ),
							'width' => 'half',
							'chart' => [
								'id'    => 'threats-by-type',
								'type'  => 'doughnut',
								'data'  => $this->data['charts']['types'],
								'empty' => [
									'title' => esc_html__( 'Nothing stopped yet', 'advanced-captcha' ),
									'desc'  => esc_html__( 'Honeypot catches, failed CAPTCHAs and expired challenges, side by side.', 'advanced-captcha' ),
								],
							],
						],
						[
							'title'  => esc_html__( 'Protection Checklist', 'advanced-captcha' ),
							'width'  => 'half',
							'render' => [ $this, 'ddwcac_render_health' ],
						],
						[
							'title'  => esc_html__( 'Top Offending IPs', 'advanced-captcha' ),
							'width'  => 'half',
							'render' => [ $this, 'ddwcac_render_top_ips' ],
						],
						[
							'title'  => esc_html__( 'Recent Blocked Attempts', 'advanced-captcha' ),
							'width'  => 'full',
							'render' => [ $this, 'ddwcac_render_recent' ],
						],
					],
				]
			);
		}

		/**
		 * Summary card config.
		 *
		 * @param string $title      Title.
		 * @param array  $summary    Summary row.
		 * @param string $path       Icon path.
		 * @param bool   $up_is_good Whether a rise is good news (more attacks is not).
		 * @param string $suffix     Value suffix.
		 * @return array
		 */
		protected function ddwcac_card( $title, $summary, $path, $up_is_good, $suffix = '' ) {
			return [
				'title'       => $title,
				'value'       => $suffix ? esc_html( number_format_i18n( $summary['value'], 1 ) . $suffix ) : $summary['value'],
				'value_type'  => $suffix ? 'html' : 'number',
				'change'      => $summary['change'],
				'is_positive' => $up_is_good ? $summary['is_positive'] : ! $summary['is_positive'],
				'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . esc_attr( $path ) . '"/></svg>',
			];
		}


		/**
		 * Protection checklist widget.
		 *
		 * @return void
		 */
		public function ddwcac_render_health() {
			?>
			<div class="ddwcac-dash-list">
				<?php foreach ( $this->data['health'] as $item ) : ?>
					<a class="ddwcac-dash-list-item" href="<?php echo esc_url( $item['url'] ); ?>">
						<span class="ddwcac-health-dot <?php echo esc_attr( ! empty( $item['pro'] ) ? 'is-pro' : ( $item['ok'] ? 'is-ok' : 'is-bad' ) ); ?>" aria-hidden="true"></span>
						<div class="ddwcac-dash-list-info">
							<strong><?php echo esc_html( $item['title'] ); ?></strong>
							<span><?php echo esc_html( $item['desc'] ); ?></span>
						</div>
						<?php if ( ! empty( $item['pro'] ) ) : ?>
							<span class="ddwcac-inline-pro"><?php echo wp_kses_post( ddfw_get_pro_tag() ); ?></span>
						<?php else : ?>
							<mark class="ddwcac-status ddwcac-status-<?php echo $item['ok'] ? 'passed' : 'failed'; ?>"><?php echo $item['ok'] ? esc_html__( 'Done', 'advanced-captcha' ) : esc_html__( 'Fix', 'advanced-captcha' ); ?></mark>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
			<?php
		}

		/**
		 * Top IPs widget.
		 *
		 * @return void
		 */
		public function ddwcac_render_top_ips() {
			$ips = $this->data['tables']['ips'];

			if ( empty( $ips ) ) {
				$this->ddwcac_render_no_data( esc_html__( 'No offenders in this period', 'advanced-captcha' ), esc_html__( 'IPs that fail CAPTCHA most often will be listed here.', 'advanced-captcha' ) );
				return;
			}
			?>
			<div class="ddwcac-dash-list">
				<?php foreach ( $ips as $row ) : ?>
					<div class="ddwcac-dash-list-item">
						<div class="ddwcac-dash-list-info">
							<strong><a href="
							<?php
							echo esc_url(
								ddwcac_get_dashboard_url(
									[
										'menu' => 'logs',
										's'    => $row['ip'],
									]
								)
							);
							?>
												"><?php echo esc_html( $row['ip'] ); ?></a></strong>
							<span>
								<?php
								/* translators: 1: number of failed attempts, 2: human time difference. */
								printf( esc_html__( '%1$s stopped attempts, last %2$s ago', 'advanced-captcha' ), esc_html( number_format_i18n( $row['total'] ) ), esc_html( human_time_diff( strtotime( $row['last_seen'] ), current_time( 'timestamp' ) ) ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Compared with a local-time column.
								?>
							</span>
						</div>
						<a class="button button-small ddwcac-inline-pro" href="https://devdiggers.com/product/woocommerce-advanced-captcha/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Block', 'advanced-captcha' ); ?> <?php echo wp_kses_post( ddfw_get_pro_tag() ); ?></a>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
			// Repeat offenders kept retrying because nothing locks them out in this plugin; say so with their own numbers.
			$repeat = count( array_filter( $ips, fn( $row ) => (int) $row['total'] >= 5 ) );
			if ( $repeat ) {
				?>
				<p class="ddwcac-dash-list-more">
					<?php
					/* translators: %s: number of IP addresses. */
					echo esc_html( sprintf( _n( '%s IP kept trying after 5 or more failures.', '%s IPs kept trying after 5 or more failures.', $repeat, 'advanced-captcha' ), number_format_i18n( $repeat ) ) );
					?>
					<a href="https://devdiggers.com/product/woocommerce-advanced-captcha/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Lock them out automatically with Pro', 'advanced-captcha' ); ?></a>
				</p>
				<?php
			}
		}

		/**
		 * Recent stopped attempts widget.
		 *
		 * @return void
		 */
		public function ddwcac_render_recent() {
			$rows = $this->data['tables']['recent'];

			if ( empty( $rows ) ) {
				$this->ddwcac_render_no_data( esc_html__( 'All quiet', 'advanced-captcha' ), esc_html__( 'Failed attempts will appear here as they happen.', 'advanced-captcha' ) );
				return;
			}

			$labels = ddwcac_get_form_labels();
			?>
			<div class="ddwcac-dash-list">
				<?php foreach ( $rows as $row ) : ?>
					<div class="ddwcac-dash-list-item">
						<div class="ddwcac-dash-list-info">
							<strong><?php echo esc_html( ( $labels[ $row['form'] ] ?? $row['form'] ) . ' · ' . $row['ip'] ); ?></strong>
							<span><?php echo esc_html( ddwcac_get_reason( $row['reason'] ) . ' · ' . mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['created_at'] ) ); ?></span>
						</div>
						<mark class="ddwcac-status ddwcac-status-<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( ddwcac_get_status_labels()[ $row['status'] ] ?? $row['status'] ); ?></mark>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="ddwcac-dash-list-more"><a href="<?php echo esc_url( ddwcac_get_dashboard_url( [ 'menu' => 'logs' ] ) ); ?>"><?php esc_html_e( 'View all logs', 'advanced-captcha' ); ?></a></p>
			<?php
		}

		/**
		 * Empty widget state.
		 *
		 * @param string $title Title.
		 * @param string $desc  Description.
		 * @return void
		 */
		protected function ddwcac_render_no_data( $title, $desc ) {
			?>
			<div class="ddwcac-dash-empty">
				<strong><?php echo esc_html( $title ); ?></strong>
				<p><?php echo esc_html( $desc ); ?></p>
			</div>
			<?php
		}
	}
}
