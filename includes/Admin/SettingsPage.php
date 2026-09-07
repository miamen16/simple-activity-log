<?php

namespace SAL\Admin;

use SAL\Core\Retention;
use SAL\Core\AlertManager;
use SAL\Core\SecurityAnalyzer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Settings" submenu — retention window and email alert configuration.
 * A plain admin_post form handler, matching the style already used for
 * CSV export, rather than the full Settings API (simpler for a handful
 * of fields).
 */
class SettingsPage {

	const CAPABILITY = 'manage_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_sal_save_settings', array( $this, 'handle_save' ) );
	}

	public function register_menu() {
		add_submenu_page(
			'sal-logs',
			__( 'Settings', 'simple-activity-log' ),
			__( 'Settings', 'simple-activity-log' ),
			self::CAPABILITY,
			'sal-settings',
			array( $this, 'render' )
		);
	}

	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'simple-activity-log' ) );
		}

		$retention_days = Retention::get_retention_days();
		$alerts_enabled = AlertManager::is_enabled();
		$alert_email    = get_option( AlertManager::OPTION_EMAIL, '' );
		$threshold      = SecurityAnalyzer::threshold();

		include SAL_PATH . 'templates/settings.php';
	}

	public function handle_save() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'simple-activity-log' ) );
		}

		check_admin_referer( 'sal_save_settings' );

		$retention_days = isset( $_POST['sal_retention_days'] ) ? max( 0, (int) $_POST['sal_retention_days'] ) : Retention::DEFAULT_DAYS;
		update_option( Retention::OPTION_DAYS, $retention_days );

		$alerts_enabled = ! empty( $_POST['sal_alerts_enabled'] ) ? '1' : '0';
		update_option( AlertManager::OPTION_ENABLED, $alerts_enabled );

		$alert_email = isset( $_POST['sal_alert_email'] ) ? sanitize_email( wp_unslash( $_POST['sal_alert_email'] ) ) : '';
		update_option( AlertManager::OPTION_EMAIL, $alert_email );

		wp_safe_redirect( add_query_arg( array( 'page' => 'sal-settings', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
