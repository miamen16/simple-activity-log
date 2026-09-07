<?php

namespace SAL\Admin;

use SAL\Core\SecurityAnalyzer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Security" submenu — surfaces failed-login patterns (by IP and by
 * username) that plain log browsing makes easy to miss, using the data
 * AuthLogger already records.
 */
class SecurityPage {

	const CAPABILITY = 'manage_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menu() {
		add_submenu_page(
			'sal-logs',
			__( 'Security', 'simple-activity-log' ),
			__( 'Security', 'simple-activity-log' ),
			self::CAPABILITY,
			'sal-security',
			array( $this, 'render' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'sal-security' ) === false ) {
			return;
		}

		wp_enqueue_style( 'sal-admin', SAL_URL . 'assets/css/admin.css', array(), SAL_VERSION );
	}

	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'simple-activity-log' ) );
		}

		$window = isset( $_GET['sal_window'] ) ? (int) $_GET['sal_window'] : 24; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $window, array( 24, 168, 720 ), true ) ) {
			$window = 24;
		}

		$summary        = SecurityAnalyzer::get_summary( $window );
		$by_ip          = SecurityAnalyzer::get_failed_logins_by_ip( $window );
		$by_username    = SecurityAnalyzer::get_failed_logins_by_username( $window );
		$threshold      = SecurityAnalyzer::threshold();

		include SAL_PATH . 'templates/security.php';
	}
}
