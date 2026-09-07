<?php

namespace SAL\Admin;

use SAL\Core\LogQuery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "Activity Log" admin page: a filterable table of every
 * logged event, plus a CSV export of the current filter.
 */
class LogsPage {

	const CAPABILITY = 'manage_options'; // Activity logs are sensitive — admins only.

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_sal_export_csv', array( $this, 'handle_csv_export' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'Activity Log', 'simple-activity-log' ),
			__( 'Activity Log', 'simple-activity-log' ),
			self::CAPABILITY,
			'sal-logs',
			array( $this, 'render' ),
			'dashicons-list-view',
			57
		);
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'sal-logs' ) === false ) {
			return;
		}

		wp_enqueue_style( 'sal-admin', SAL_URL . 'assets/css/admin.css', array(), SAL_VERSION );
	}

	private function get_filters() {
		return array(
			'user_id'   => isset( $_GET['sal_user'] ) ? (int) $_GET['sal_user'] : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'action'    => isset( $_GET['sal_action'] ) ? sanitize_key( wp_unslash( $_GET['sal_action'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_from' => isset( $_GET['sal_from'] ) ? sanitize_text_field( wp_unslash( $_GET['sal_from'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_to'   => isset( $_GET['sal_to'] ) ? sanitize_text_field( wp_unslash( $_GET['sal_to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'page'      => isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'per_page'  => 50,
		);
	}

	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'simple-activity-log' ) );
		}

		$filters      = $this->get_filters();
		$logs         = LogQuery::get_logs( $filters );
		$total        = LogQuery::count_logs( $filters );
		$total_pages  = (int) ceil( $total / $filters['per_page'] );
		$users        = LogQuery::get_logged_users();
		$action_types = LogQuery::get_distinct_actions();

		include SAL_PATH . 'templates/logs.php';
	}

	/**
	 * Streams a CSV of every log row matching the current filters (not
	 * just the current page) without loading the entire export into memory.
	 */
	public function handle_csv_export() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'simple-activity-log' ) );
		}

		check_admin_referer( 'sal_export_csv' );

		$filters = $this->get_filters();
		$page    = 1;
		$chunk   = LogQuery::MAX_PER_PAGE;

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="activity-log-' . gmdate( 'Y-m-d' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'Date', 'User', 'Action', 'Message', 'IP Address' ) );

		do {
			$filters['page']     = $page;
			$filters['per_page'] = $chunk;
			$logs               = LogQuery::get_logs( $filters );

			foreach ( $logs as $log ) {
				fputcsv( $out, array(
					$log->created_at,
					$log->username ?: __( 'Guest', 'simple-activity-log' ),
					$log->action,
					$log->message,
					$log->ip_address,
				) );
			}

			$page++;
		} while ( count( $logs ) === $chunk );

		// The request exits immediately, so PHP will release the output stream.
		exit;
	}
}
