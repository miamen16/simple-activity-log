<?php

namespace SAL\Admin;

use SAL\Core\Retention;
use SAL\Core\AlertManager;
use SAL\Core\AlertPolicy;
use SAL\Core\SecurityAnalyzer;
use SAL\Core\IPBlocklist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings page for retention, alerts, and security response.
 */
class SettingsPage {

	const CAPABILITY = 'manage_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_sal_save_settings', array( $this, 'handle_save' ) );
	}

	public function register_menu() {
		add_submenu_page( 'sal-logs', __( 'Settings', 'simple-activity-log' ), __( 'Settings', 'simple-activity-log' ), self::CAPABILITY, 'sal-settings', array( $this, 'render' ) );
	}

	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'simple-activity-log' ) );
		}

		$retention_days = Retention::get_retention_days();
		$alerts_enabled = AlertManager::is_enabled();
		$alert_email    = get_option( AlertManager::OPTION_EMAIL, '' );
		$threshold      = SecurityAnalyzer::threshold();
		$alert_settings = AlertPolicy::settings();
		$auto_block     = IPBlocklist::auto_block_enabled();
		$auto_threshold = IPBlocklist::auto_block_threshold();

		include SAL_PATH . 'templates/settings.php';
	}

	public function handle_save() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'simple-activity-log' ) );
		}

		check_admin_referer( 'sal_save_settings' );

		$retention_days = isset( $_POST['sal_retention_days'] ) ? absint( wp_unslash( $_POST['sal_retention_days'] ) ) : Retention::DEFAULT_DAYS;
		update_option( Retention::OPTION_DAYS, $retention_days );

		$alerts_enabled = isset( $_POST['sal_alerts_enabled'] ) ? ( '1' === sanitize_text_field( wp_unslash( $_POST['sal_alerts_enabled'] ) ) ? '1' : '0' ) : '0';
		update_option( AlertManager::OPTION_ENABLED, $alerts_enabled );

		$alert_email = isset( $_POST['sal_alert_email'] ) ? sanitize_email( wp_unslash( $_POST['sal_alert_email'] ) ) : '';
		update_option( AlertManager::OPTION_EMAIL, $alert_email );

		$allowed_severities = array( 'medium', 'high', 'critical' );
		$min_severity = isset( $_POST['sal_alert_min_severity'] ) ? sanitize_key( wp_unslash( $_POST['sal_alert_min_severity'] ) ) : 'high';
		if ( ! in_array( $min_severity, $allowed_severities, true ) ) {
			$min_severity = 'high';
		}
		update_option( 'sal_alert_min_severity', $min_severity );

		$cooldown = isset( $_POST['sal_alert_cooldown_minutes'] ) ? absint( wp_unslash( $_POST['sal_alert_cooldown_minutes'] ) ) : 60;
		$cooldown = max( 5, min( 1440, $cooldown ) );
		update_option( 'sal_alert_cooldown_minutes', $cooldown );

		$auto_block = isset( $_POST['sal_auto_block_enabled'] ) ? '1' : '0';
		update_option( IPBlocklist::OPTION_AUTO_BLOCK, $auto_block );

		$auto_threshold = isset( $_POST['sal_auto_block_threshold'] ) ? absint( wp_unslash( $_POST['sal_auto_block_threshold'] ) ) : 20;
		$auto_threshold = max( 5, min( 1000, $auto_threshold ) );
		update_option( IPBlocklist::OPTION_AUTO_THRESHOLD, $auto_threshold );

		wp_safe_redirect( add_query_arg( array( 'page' => 'sal-settings', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
