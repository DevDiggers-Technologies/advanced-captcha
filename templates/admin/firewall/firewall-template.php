<?php
/**
 * Firewall screen: the trusted list and repeat offenders, with the Pro block tools described.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Templates\Admin\Firewall;

use DDWCAdvancedCAPTCHA\Helper\Logs\DDWCAC_Logs_Helper;
use DDWCAdvancedCAPTCHA\Templates\Admin\Logs\DDWCAC_Logs_Template;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Firewall_Template' ) ) {
	/**
	 * Firewall template class.
	 */
	class DDWCAC_Firewall_Template {
		/**
		 * Configuration.
		 *
		 * @var array
		 */
		protected $configuration;

		/**
		 * Construct.
		 *
		 * @param array $configuration Configuration.
		 */
		public function __construct( $configuration ) {
			$this->configuration = $configuration;

			$logs      = new DDWCAC_Logs_Helper();
			$trusted   = $this->ddwcac_rules( 'whitelisted_ips' );
			$week      = $logs->ddwcac_get_range_stats( gmdate( 'Y-m-d', strtotime( '-6 days', current_time( 'timestamp' ) ) ), current_time( 'Y-m-d' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Site-local dates, as stored.
			$offenders = $week['ips'];

			DDWCAC_Logs_Template::ddwcac_print_action_notice();         ?>
			<div class="ddwcac-page ddwcac-fw">
				<div class="ddwcac-page-header">
					<div>
						<h1><?php esc_html_e( 'Firewall', 'advanced-captcha' ); ?></h1>
						<p><?php esc_html_e( 'Decide who gets in. Trusted visitors never see a challenge, and the IPs that attack you most are listed so you can act on them.', 'advanced-captcha' ); ?></p>
					</div>
					<a class="button" href="
					<?php
					echo esc_url(
						ddwcac_get_dashboard_url(
							[
								'menu' => 'configuration',
								'tab'  => 'lockout',
							]
						)
					);
					?>
											"><?php esc_html_e( 'Visitor IP Settings', 'advanced-captcha' ); ?></a>
				</div>

				<div class="ddwcac-stats">
					<?php
					ddwcac_admin_stat( __( 'Stopped This Week', 'advanced-captcha' ), $week['stopped'], '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>' );
					ddwcac_admin_stat( __( 'Attacking IPs This Week', 'advanced-captcha' ), $week['unique_ips'], '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/>' );
					ddwcac_admin_stat( __( 'Trusted Rules', 'advanced-captcha' ), count( $trusted ), '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>' );
					?>
				</div>

				<div class="ddwcac-fw-grid">

					<div class="ddwcac-panel">
						<div class="ddwcac-panel-header">
							<h2><?php esc_html_e( 'Repeat Offenders', 'advanced-captcha' ); ?></h2>
							<a class="ddwcac-panel-link" href="<?php echo esc_url( ddwcac_get_dashboard_url( [ 'menu' => 'logs' ] ) ); ?>"><?php esc_html_e( 'View Logs', 'advanced-captcha' ); ?></a>
							<p><?php esc_html_e( 'IPs stopped most often in the last 7 days.', 'advanced-captcha' ); ?></p>
						</div>
						<?php if ( $offenders ) : ?>
							<?php foreach ( $offenders as $row ) : ?>
								<div class="ddwcac-list-row">
									<span class="ddwcac-list-icon" aria-hidden="true"><?php echo esc_html( number_format_i18n( (int) $row['total'] ) ); ?></span>
									<div class="ddwcac-list-main">
										<a href="
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
													"><code><?php echo esc_html( $row['ip'] ); ?></code></a>
										<span>
											<?php
											/* translators: 1: attempts, 2: time ago. */
											echo esc_html( sprintf( _n( '%1$s attempt, last %2$s ago', '%1$s attempts, last %2$s ago', (int) $row['total'], 'advanced-captcha' ), number_format_i18n( (int) $row['total'] ), human_time_diff( strtotime( $row['last_seen'] ), current_time( 'timestamp' ) ) ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Site-local, as stored.
											?>
										</span>
									</div>
									<div class="ddwcac-list-actions">
										<?php $this->ddwcac_pro_action( __( 'Block IP', 'advanced-captcha' ) ); ?>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<?php $this->ddwcac_empty( __( 'No attacks in the last 7 days.', 'advanced-captcha' ) ); ?>
						<?php endif; ?>
					</div>

					<?php
					$this->ddwcac_rule_card( 'allow', __( 'Trusted List', 'advanced-captcha' ), __( 'Never challenged: your office, staff and payment gateway callbacks.', 'advanced-captcha' ), $trusted, __( 'No trusted IPs yet.', 'advanced-captcha' ) );
					?>
				</div>

				<div class="ddwcac-section">
					<?php
					ddfw_upgrade_to_pro_section(
						[
							'image_url'     => ddwcac_get_pro_image( 'firewall' ),
							'heading'       => esc_html__( 'Turn attackers away for good with Pro', 'advanced-captcha' ),
							'description'   => esc_html__( 'Free challenges every bot. Pro remembers them: an IP that keeps failing is locked out of every protected form, and the worst offenders go on a permanent block list.', 'advanced-captcha' ),
							'list_features' => [
								esc_html__( 'Brute-force lockout after a few failures, with a live Locked Out Right Now list and one-click Lift Lockout', 'advanced-captcha' ),
								esc_html__( 'A permanent block list with single IPs, wildcards and ranges, and a Block button next to every repeat offender', 'advanced-captcha' ),
								esc_html__( 'Country rules: block the countries you never sell to, or allow only the ones you do', 'advanced-captcha' ),
								esc_html__( 'Card-testing shield and disposable email guard for checkout and signups', 'advanced-captcha' ),
							],
							'upgrade_url'   => 'https://devdiggers.com/product/woocommerce-advanced-captcha/',
						]
					);
					?>
				</div>

				<div class="ddwcac-note">
					<strong><?php esc_html_e( 'Your IP address:', 'advanced-captcha' ); ?></strong>
					<code><?php echo esc_html( ddwcac_get_ip_address() ); ?></code>
					<?php esc_html_e( 'Rules accept single IPs, wildcards (203.0.113.*) and ranges (203.0.113.0/24). Separate several with spaces to add them at once.', 'advanced-captcha' ); ?>
				</div>
			</div>
			<?php
		}

		/**
		 * The trust list with its add form.
		 *
		 * @param string $task       allow.
		 * @param string $title      Title.
		 * @param string $desc       Description.
		 * @param array  $rules      Rules.
		 * @param string $empty_text Empty text.
		 * @return void
		 */
		protected function ddwcac_rule_card( $task, $title, $desc, $rules, $empty_text ) {
			$remove = 'disallow';
			?>
			<div class="ddwcac-panel">
				<div class="ddwcac-panel-header">
					<h2><?php echo esc_html( $title ); ?></h2>
					<p><?php echo esc_html( $desc ); ?></p>
				</div>
				<form class="ddwcac-fw-add" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ddwcac_log_action" />
					<input type="hidden" name="task" value="<?php echo esc_attr( $task ); ?>" />
					<?php wp_nonce_field( 'ddwcac_log_action', 'ddwcac_nonce' ); ?>
					<label class="screen-reader-text" for="ddwcac-fw-<?php echo esc_attr( $task ); ?>"><?php esc_html_e( 'IP address or range', 'advanced-captcha' ); ?></label>
					<input type="text" id="ddwcac-fw-<?php echo esc_attr( $task ); ?>" name="ip" required autocomplete="off" spellcheck="false" placeholder="<?php esc_attr_e( 'IP address or range', 'advanced-captcha' ); ?>" />
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Trust', 'advanced-captcha' ); ?></button>
				</form>
				<?php
				$own_ip = ddwcac_get_ip_address();
				if ( $own_ip && ! ddwcac_ip_in_list( $own_ip, $this->configuration['whitelisted_ips'] ) ) :
					?>
					<p class="ddwcac-fw-suggest">
						<?php
						/* translators: %s: current visitor IP. */
						echo esc_html( sprintf( __( 'Your IP %s is not trusted yet.', 'advanced-captcha' ), $own_ip ) );
						?>
						<a href="<?php echo esc_url( $this->ddwcac_action_url( 'allow', $own_ip ) ); ?>"><?php esc_html_e( 'Trust my IP', 'advanced-captcha' ); ?></a>
					</p>
				<?php endif; ?>
				<?php if ( $rules ) : ?>
					<?php foreach ( $rules as $rule ) : ?>
						<div class="ddwcac-list-row">
							<div class="ddwcac-list-main"><code><?php echo esc_html( $rule ); ?></code></div>
							<div class="ddwcac-list-actions">
								<a class="ddwcac-action-delete" href="<?php echo esc_url( $this->ddwcac_action_url( $remove, $rule ) ); ?>"><?php esc_html_e( 'Remove', 'advanced-captcha' ); ?></a>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<?php $this->ddwcac_empty( $empty_text ); ?>
				<?php endif; ?>
			</div>
			<?php
		}


		/**
		 * A Pro-only row action: the action name with a PRO tag, linking to the upgrade page.
		 *
		 * @param string $label Action label.
		 * @return void
		 */
		protected function ddwcac_pro_action( $label ) {
			?>
			<a class="button button-small ddwcac-inline-pro" href="https://devdiggers.com/product/woocommerce-advanced-captcha/" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $label ); ?> <?php echo wp_kses_post( ddfw_get_pro_tag() ); ?></a>
			<?php
		}

		/**
		 * Saved rules of a list option.
		 *
		 * @param string $key Option key.
		 * @return array
		 */
		protected function ddwcac_rules( $key ) {
			return preg_split( '/\s+/', (string) $this->configuration[ $key ], -1, PREG_SPLIT_NO_EMPTY );
		}

		/**
		 * Empty panel text.
		 *
		 * @param string $text Text.
		 * @return void
		 */
		protected function ddwcac_empty( $text ) {
			?>
			<p class="ddwcac-empty-text"><?php echo esc_html( $text ); ?></p>
			<?php
		}

		/**
		 * Signed action URL.
		 *
		 * @param string $task Task.
		 * @param string $ip   IP or rule.
		 * @return string
		 */
		protected function ddwcac_action_url( $task, $ip ) {
			return wp_nonce_url(
				add_query_arg(
					[
						'action' => 'ddwcac_log_action',
						'task'   => $task,
						'ip'     => rawurlencode( $ip ),
					],
					admin_url( 'admin-post.php' )
				),
				'ddwcac_log_action',
				'ddwcac_nonce'
			);
		}
	}
}
