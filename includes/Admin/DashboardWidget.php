<?php

namespace SAL\Admin;

use SAL\Core\LogQuery;
use SAL\Core\SecurityAnalyzer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A glanceable widget on the main WP Dashboard — recent activity plus a
 * quick failed-login count, so you don't have to visit the plugin's own
 * pages just to notice something's off.
 */
class DashboardWidget {

	const CAPABILITY = 'manage_options';

	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( 'index.php' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'sal-admin', SAL_URL . 'assets/css/admin.css', array(), SAL_VERSION );
	}

	public function register() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'sal_dashboard_widget',
			__( 'Recent Activity', 'simple-activity-log' ),
			array( $this, 'render' )
		);
	}

	public function render() {
		$logs           = LogQuery::get_logs( array( 'per_page' => 8, 'page' => 1 ) );
		$failed_today   = SecurityAnalyzer::get_summary( 24 )['total_attempts'];
		$suspicious_ips = array_filter(
			SecurityAnalyzer::get_failed_logins_by_ip( 24 ),
			function ( $row ) {
				return SecurityAnalyzer::is_suspicious( $row->attempts );
			}
		);

		include SAL_PATH . 'templates/dashboard-widget.php';
	}
}
