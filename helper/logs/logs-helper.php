<?php
/**
 * Verification log data access.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Helper\Logs;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Logs_Helper' ) ) {
	/**
	 * Logs helper class
	 */
	class DDWCAC_Logs_Helper {
		/**
		 * Statuses a log row can carry.
		 */
		const STATUSES = [ 'passed', 'failed', 'error' ];

		/**
		 * Logs table name.
		 *
		 * @var string
		 */
		protected $table;

		/**
		 * Construct
		 */
		public function __construct() {
			global $wpdb;
			$this->table = $wpdb->prefix . 'ddwcac_logs';
		}

		/**
		 * Insert one verification attempt.
		 *
		 * @param array $data Row data: form, provider, status, reason, ip.
		 * @return void
		 */
		public function ddwcac_insert_log( $data ) {
			global $wpdb;

			// phpcs:disable WordPress.Security.ValidatedSanitizedInput -- Stored as plain truncated text, escaped on output.
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
			$url        = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			// phpcs:enable

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom log table.
			$wpdb->insert(
				$this->table,
				[
					'form'       => substr( sanitize_key( $data['form'] ), 0, 50 ),
					'provider'   => substr( sanitize_key( $data['provider'] ), 0, 20 ),
					'status'     => in_array( $data['status'], self::STATUSES, true ) ? $data['status'] : 'failed',
					'reason'     => substr( sanitize_text_field( $data['reason'] ), 0, 100 ),
					'ip'         => substr( (string) $data['ip'], 0, 45 ),
					'user_agent' => substr( $user_agent, 0, 255 ),
					'url'        => substr( $url, 0, 255 ),
					'created_at' => current_time( 'mysql' ),
				],
				[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
			);
		}

		/**
		 * Build the WHERE clause shared by the list and its count.
		 *
		 * @param array $args Filters: status, form, search.
		 * @return string Prepared SQL fragment starting with WHERE.
		 */
		protected function ddwcac_get_where( $args ) {
			global $wpdb;

			$where = [ '1=1' ];

			if ( ! empty( $args['status'] ) && in_array( $args['status'], self::STATUSES, true ) ) {
				$where[] = $wpdb->prepare( 'status = %s', $args['status'] );
			}

			if ( ! empty( $args['form'] ) ) {
				$where[] = $wpdb->prepare( 'form = %s', $args['form'] );
			}

			if ( ! empty( $args['search'] ) ) {
				$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
				$where[] = $wpdb->prepare( '( ip LIKE %s OR reason LIKE %s OR url LIKE %s )', $like, $like, $like );
			}

			return 'WHERE ' . implode( ' AND ', $where );
		}

		/**
		 * Get a page of logs.
		 *
		 * @param array $args Filters plus per_page, offset, orderby, order.
		 * @return array
		 */
		public function ddwcac_get_logs( $args ) {
			global $wpdb;

			$orderby = in_array( $args['orderby'] ?? '', [ 'id', 'created_at', 'ip', 'form', 'status' ], true ) ? $args['orderby'] : 'id';
			$order   = 'ASC' === strtoupper( $args['order'] ?? '' ) ? 'ASC' : 'DESC';
			$where   = $this->ddwcac_get_where( $args );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table, WHERE and ORDER BY are built from prepared/whitelisted parts.
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", absint( $args['per_page'] ), absint( $args['offset'] ) ), ARRAY_A );
		}

		/**
		 * Count logs matching filters.
		 *
		 * @param array $args Filters.
		 * @return int
		 */
		public function ddwcac_get_logs_count( $args ) {
			global $wpdb;

			$where = $this->ddwcac_get_where( $args );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- WHERE is built from prepared parts.
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} {$where}" );
		}

		/**
		 * Count rows per status, used by the list views.
		 *
		 * @return array status => count.
		 */
		public function ddwcac_get_status_counts() {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Fixed query on the plugin table.
			$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$this->table} GROUP BY status", ARRAY_A );

			return array_map( 'intval', array_column( (array) $rows, 'total', 'status' ) );
		}

		/**
		 * Aggregate stats for a date range in one pass.
		 *
		 * @param string $from Start date Y-m-d.
		 * @param string $to   End date Y-m-d.
		 * @return array
		 */
		public function ddwcac_get_range_stats( $from, $to ) {
			global $wpdb;

			$start = $from . ' 00:00:00';
			$end   = $to . ' 23:59:59';

			// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin table, values prepared.
			$by_day = $wpdb->get_results( $wpdb->prepare( "SELECT DATE(created_at) AS day, status, COUNT(*) AS total FROM {$this->table} WHERE created_at BETWEEN %s AND %s GROUP BY day, status", $start, $end ), ARRAY_A );
			$forms  = $wpdb->get_results( $wpdb->prepare( "SELECT form, COUNT(*) AS total FROM {$this->table} WHERE status <> 'passed' AND created_at BETWEEN %s AND %s GROUP BY form ORDER BY total DESC LIMIT 6", $start, $end ), ARRAY_A );
			$ips    = $wpdb->get_results( $wpdb->prepare( "SELECT ip, COUNT(*) AS total, MAX(created_at) AS last_seen FROM {$this->table} WHERE status <> 'passed' AND ip <> '' AND created_at BETWEEN %s AND %s GROUP BY ip ORDER BY total DESC LIMIT 6", $start, $end ), ARRAY_A );
			$types  = $wpdb->get_results( $wpdb->prepare( "SELECT reason, COUNT(*) AS total FROM {$this->table} WHERE status <> 'passed' AND created_at BETWEEN %s AND %s GROUP BY reason ORDER BY total DESC LIMIT 8", $start, $end ), ARRAY_A );
			$unique = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT ip) FROM {$this->table} WHERE status <> 'passed' AND created_at BETWEEN %s AND %s", $start, $end ) );
			// phpcs:enable

			$stats = [
				'passed'     => 0,
				'stopped'    => 0,
				'unique_ips' => $unique,
				'by_day'     => [
					'passed'  => [],
					'stopped' => [],
				],
				'forms'      => (array) $forms,
				'types'      => (array) $types,
				'ips'        => (array) $ips,
			];

			foreach ( (array) $by_day as $row ) {
				$key   = 'passed' === $row['status'] ? 'passed' : 'stopped';
				$total = (int) $row['total'];

				$stats[ $key ]                         += $total;
				$stats['by_day'][ $key ][ $row['day'] ] = ( $stats['by_day'][ $key ][ $row['day'] ] ?? 0 ) + $total;
			}

			return $stats;
		}

		/**
		 * Latest stopped attempts.
		 *
		 * @param int $limit Rows.
		 * @return array
		 */
		public function ddwcac_get_recent_stopped( $limit = 6 ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin table, value prepared.
			return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE status <> 'passed' ORDER BY id DESC LIMIT %d", $limit ), ARRAY_A );
		}

		/**
		 * Delete logs by id.
		 *
		 * @param int[] $ids Row ids.
		 * @return int Rows deleted.
		 */
		public function ddwcac_delete_logs( $ids ) {
			global $wpdb;

			$ids = array_filter( array_map( 'absint', (array) $ids ) );
			if ( empty( $ids ) ) {
				return 0;
			}

			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders built from the id count.
			return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table} WHERE id IN ($placeholders)", $ids ) );
		}

		/**
		 * Delete every log row.
		 *
		 * @return void
		 */
		public function ddwcac_clear_logs() {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin table.
			$wpdb->query( "TRUNCATE TABLE {$this->table}" );
		}

		/**
		 * Delete rows older than the retention period.
		 *
		 * @param int $days Days to keep.
		 * @return void
		 */
		public function ddwcac_purge_old_logs( $days ) {
			global $wpdb;

			$days = absint( $days );
			if ( ! $days ) {
				return;
			}

			$cutoff = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $days * DAY_IN_SECONDS ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Compared against current_time( 'mysql' ) values.

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin table, value prepared.
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table} WHERE created_at < %s", $cutoff ) );
		}
	}
}
