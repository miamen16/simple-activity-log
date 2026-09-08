<?php
/**
 * Fires only when the user deletes the plugin from wp-admin (not on deactivate).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table           = esc_sql( $wpdb->prefix . 'sal_logs' );
$incidents_table = esc_sql( $wpdb->prefix . 'sal_incidents' );
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$incidents_table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

delete_option( 'sal_db_version' );
delete_option( 'sal_retention_days' );
delete_option( 'sal_alerts_enabled' );
delete_option( 'sal_alert_email' );
delete_option( 'sal_alert_min_severity' );
delete_option( 'sal_alert_cooldown_minutes' );
delete_option( 'sal_ip_blocklist' );
delete_option( 'sal_ip_allowlist' );
delete_option( 'sal_auto_block_enabled' );
delete_option( 'sal_auto_block_threshold' );

wp_clear_scheduled_hook( 'sal_cleanup_logs' );
