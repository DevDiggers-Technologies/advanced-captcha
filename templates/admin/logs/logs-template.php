<?php
/**
 * Logs screen.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Templates\Admin\Logs;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Logs_Template' ) ) {
	/**
	 * Logs screen class.
	 */
	class DDWCAC_Logs_Template {
		/**
		 * Construct.
		 *
		 * @param array $configuration Configuration.
		 */
		public function __construct( $configuration ) {
			$this->ddwcac_print_notice( $configuration );

			$list_table = new DDWCAC_Logs_List_Template();
			$list_table->prepare_items();

			$clear_url = wp_nonce_url(
				add_query_arg(
					[
						'action' => 'ddwcac_log_action',
						'task'   => 'clear',
					],
					admin_url( 'admin-post.php' )
				),
				'ddwcac_log_action',
				'ddwcac_nonce'
			);
			?>
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Verification Logs', 'advanced-captcha' ); ?></h1>
			<a href="<?php echo esc_url( $clear_url ); ?>" class="page-title-action button ddwcac-clear-logs"><?php esc_html_e( 'Clear All Logs', 'advanced-captcha' ); ?></a>
			<hr class="wp-header-end" />
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( DDWCAC_DASHBOARD_PAGE ); ?>" />
				<input type="hidden" name="menu" value="logs" />
				<?php
				$list_table->search_box( esc_html__( 'Search IP, reason or page', 'advanced-captcha' ), 'ddwcac-log-search' );
				$list_table->display();
				?>
			</form>
			<?php
		}

		/**
		 * Show the outcome of the last log action, and a hint when logging is off.
		 *
		 * @param array $configuration Configuration.
		 * @return void
		 */
		protected function ddwcac_print_notice( $configuration ) {
			if ( 'yes' !== $configuration['logging_enabled'] ) {
				ddfw_print_notification( esc_html__( 'Logging is switched off in Configuration > Advanced, so new attempts are not recorded.', 'advanced-captcha' ), 'warning', false );
			}

			self::ddwcac_print_action_notice();
		}

		/**
		 * Outcome of the last log or trusted list action.
		 *
		 * @return void
		 */
		public static function ddwcac_print_action_notice() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of the finished action.
			$notice   = isset( $_GET['ddwcac-notice'] ) ? sanitize_key( wp_unslash( $_GET['ddwcac-notice'] ) ) : '';
			$messages = [
				'delete-done'   => esc_html__( 'Selected log entries deleted.', 'advanced-captcha' ),
				'clear-done'    => esc_html__( 'All log entries deleted.', 'advanced-captcha' ),
				'allow-done'    => esc_html__( 'Added to the trusted list.', 'advanced-captcha' ),
				'disallow-done' => esc_html__( 'Removed from the trusted list.', 'advanced-captcha' ),
			];

			if ( isset( $messages[ $notice ] ) ) {
				ddfw_print_notification( $messages[ $notice ], 'success' );
			} elseif ( '-invalid-ip' === substr( $notice, -11 ) ) {
				ddfw_print_notification( esc_html__( 'Enter a valid IP address, wildcard (203.0.113.*) or CIDR range (203.0.113.0/24).', 'advanced-captcha' ), 'error' );
			}
		}
	}
}
