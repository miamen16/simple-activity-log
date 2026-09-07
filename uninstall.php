<?php
/**
 * Fires only when the user deletes the plugin from wp-admin (not on deactivate).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table = esc_sql( $wpdb->prefix . 'sal_logs' );
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

delete_option( 'sal_db_version' );
delete_option( 'sal_retention_days' );
delete_option( 'sal_alerts_enabled' );
delete_option( 'sal_alert_email' );

wp_clear_scheduled_hook( 'sal_cleanup_logs' );
