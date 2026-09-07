<?php

namespace SAL\Admin;

use SAL\Core\SecurityDashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main security monitoring dashboard.
 */
class SecurityDashboardPage {

	const CAPABILITY = 'manage_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menu() {
		add_submenu_page( 'sal-logs', __( 'Security Dashboard', 'simple-activity-log' ), __( 'Dashboard', 'simple-activity-log' ), self::CAPABILITY, 'sal-security-dashboard', array( $this, 'render' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'sal-security-dashboard' ) === false ) {
			return;
		}
		wp_enqueue_style( 'sal-admin', SAL_URL . 'assets/css/admin.css', array(), SAL_VERSION );
	}

	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'simple-activity-log' ) );
		}

		$window = isset( $_GET['sal_window'] ) ? absint( $_GET['sal_window'] ) : 24; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $window, array( 24, 168, 720 ), true ) ) {
			$window = 24;
		}

		$data = SecurityDashboard::get_data( $window );
		include SAL_PATH . 'templates/security-dashboard.php';
	}
}
