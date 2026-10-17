<?php
/**
 * Verification logs list table.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Templates\Admin\Logs;

use DDWCAdvancedCAPTCHA\Helper\Logs\DDWCAC_Logs_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'DDWCAC_Logs_List_Template' ) ) {
	/**
	 * Logs list table class.
	 */
	class DDWCAC_Logs_List_Template extends \WP_List_Table {
		/**
		 * Logs helper.
		 *
		 * @var DDWCAC_Logs_Helper
		 */
		protected $helper;

		/**
		 * Trusted IP rules.
		 *
		 * @var string
		 */
		protected $trusted_ips;

		/**
		 * Construct.
		 */
		public function __construct() {
			parent::__construct(
				[
					'singular' => 'log',
					'plural'   => 'logs',
					'ajax'     => false,
				]
			);

			$this->helper      = new DDWCAC_Logs_Helper();
			$this->trusted_ips = (string) get_option( '_ddwcac_whitelisted_ips', '' );
		}

		/**
		 * Current request filters.
		 *
		 * @return array
		 */
		protected function ddwcac_get_filters() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list filters.
			return [
				'status' => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
				'form'   => isset( $_GET['form'] ) ? sanitize_key( wp_unslash( $_GET['form'] ) ) : '',
				'search' => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			];
			// phpcs:enable
		}

		/**
		 * Prepare items.
		 *
		 * @return void
		 */
		public function prepare_items() {
			$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

			$per_page = $this->get_items_per_page( 'ddwcac_logs_per_page', 20 );
			$filters  = $this->ddwcac_get_filters();
			$total    = $this->helper->ddwcac_get_logs_count( $filters );

			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only sorting.
			$this->items = $this->helper->ddwcac_get_logs(
				$filters + [
					'per_page' => $per_page,
					'offset'   => ( $this->get_pagenum() - 1 ) * $per_page,
					'orderby'  => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'id',
					'order'    => isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc',
				]
			);
			// phpcs:enable

			$this->set_pagination_args(
				[
					'total_items' => $total,
					'per_page'    => $per_page,
					'total_pages' => (int) ceil( $total / $per_page ),
				]
			);
		}

		/**
		 * Columns.
		 *
		 * @return array
		 */
		public function get_columns() {
			return [
				'cb'         => '<input type="checkbox" />',
				'status'     => esc_html__( 'Result', 'advanced-captcha' ),
				'form'       => esc_html__( 'Form & Page', 'advanced-captcha' ),
				'ip'         => esc_html__( 'Visitor', 'advanced-captcha' ),
				'provider'   => esc_html__( 'Provider', 'advanced-captcha' ),
				'created_at' => esc_html__( 'Date', 'advanced-captcha' ),
			];
		}

		/**
		 * Sortable columns.
		 *
		 * @return array
		 */
		protected function get_sortable_columns() {
			return [
				'status'     => [ 'status', false ],
				'form'       => [ 'form', false ],
				'ip'         => [ 'ip', false ],
				'created_at' => [ 'id', true ],
			];
		}

		/**
		 * Form filter dropdown.
		 *
		 * @param string $which top|bottom.
		 * @return void
		 */
		protected function extra_tablenav( $which ) {
			if ( 'top' !== $which ) {
				return;
			}

			$filters = $this->ddwcac_get_filters();
			$current = $filters['form'];
			$counts  = $this->helper->ddwcac_get_status_counts();
			?>
			<div class="alignleft actions">
				<label class="screen-reader-text" for="ddwcac-status-filter"><?php esc_html_e( 'Filter by result', 'advanced-captcha' ); ?></label>
				<select name="status" id="ddwcac-status-filter" data-placeholder="<?php esc_attr_e( 'All results', 'advanced-captcha' ); ?>">
					<option value=""><?php esc_html_e( 'All results', 'advanced-captcha' ); ?></option>
					<?php foreach ( ddwcac_get_status_labels() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['status'], $key ); ?>><?php echo esc_html( $label . ' (' . number_format_i18n( $counts[ $key ] ?? 0 ) . ')' ); ?></option>
					<?php endforeach; ?>
				</select>
				<label class="screen-reader-text" for="ddwcac-form-filter"><?php esc_html_e( 'Filter by form', 'advanced-captcha' ); ?></label>
				<select name="form" id="ddwcac-form-filter" data-placeholder="<?php esc_attr_e( 'All forms', 'advanced-captcha' ); ?>">
					<option value=""><?php esc_html_e( 'All forms', 'advanced-captcha' ); ?></option>
					<?php foreach ( ddwcac_get_form_labels() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( esc_html__( 'Filter', 'advanced-captcha' ), '', 'filter_action', false ); ?>
			</div>
			<?php
		}

		/**
		 * Bulk actions.
		 *
		 * @return array
		 */
		protected function get_bulk_actions() {
			return [ 'delete' => esc_html__( 'Delete', 'advanced-captcha' ) ];
		}

		/**
		 * Checkbox column.
		 *
		 * @param array $item Row.
		 * @return string
		 */
		protected function column_cb( $item ) {
			return '<input type="checkbox" name="log[]" value="' . absint( $item['id'] ) . '" />';
		}

		/**
		 * Status column.
		 *
		 * @param array $item Row.
		 * @return string
		 */
		protected function column_status( $item ) {
			$labels = ddwcac_get_status_labels();

			return '<mark class="ddwcac-status ddwcac-status-' . esc_attr( $item['status'] ) . '">' . esc_html( $labels[ $item['status'] ] ?? $item['status'] ) . '</mark><span class="ddwcac-cell-meta">' . esc_html( ddwcac_get_reason( $item['reason'] ) ) . '</span>';
		}

		/**
		 * Visitor column: IP, trust state and quick actions on one line.
		 *
		 * @param array $item Row.
		 * @return string
		 */
		protected function column_ip( $item ) {
			if ( ! $item['ip'] ) {
				return '&ndash;';
			}

			$trusted = ddwcac_ip_in_list( $item['ip'], $this->trusted_ips );
			$actions = [
				'<a href="' . esc_url(
					ddwcac_get_dashboard_url(
						[
							'menu' => 'logs',
							's'    => $item['ip'],
						]
					)
				) . '">' . esc_html__( 'Show all', 'advanced-captcha' ) . '</a>',
				'<a href="' . esc_url( $this->ddwcac_action_url( $trusted ? 'disallow' : 'allow', $item['ip'] ) ) . '">' . ( $trusted ? esc_html__( 'Untrust', 'advanced-captcha' ) : esc_html__( 'Trust IP', 'advanced-captcha' ) ) . '</a>',
			];

			return '<div class="ddwcac-ip-line"><code>' . esc_html( $item['ip'] ) . '</code>' . ( $trusted ? '<span class="ddwcac-tag">' . esc_html__( 'Trusted', 'advanced-captcha' ) . '</span>' : '' ) . '</div><div class="ddwcac-ip-actions">' . implode( '', $actions ) . '</div>';
		}

		/**
		 * Signed action URL.
		 *
		 * @param string $task Task.
		 * @param string $ip   IP.
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

		/**
		 * Default columns.
		 *
		 * @param array  $item        Row.
		 * @param string $column_name Column.
		 * @return string
		 */
		protected function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'form':
					$page = $item['url'] ? '<span class="ddwcac-cell-meta ddwcac-url" title="' . esc_attr( $item['user_agent'] ) . '">' . esc_html( $item['url'] ) . '</span>' : '';
					return '<strong>' . esc_html( ddwcac_get_form_labels()[ $item['form'] ] ?? $item['form'] ) . '</strong>' . $page;
				case 'provider':
					$providers = [
						'builtin'    => esc_html__( 'Built-in', 'advanced-captcha' ),
						'google'     => 'reCAPTCHA',
						'cloudflare' => 'Turnstile',
						'h'          => 'hCaptcha',
					];
					return esc_html( $providers[ $item['provider'] ] ?? $item['provider'] );
				case 'created_at':
					return esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item['created_at'] ) );
			}

			return '';
		}

		/**
		 * Empty message.
		 *
		 * @return void
		 */
		public function no_items() {
			esc_html_e( 'No CAPTCHA verifications logged yet. Entries appear here as visitors submit protected forms.', 'advanced-captcha' );
		}
	}
}
