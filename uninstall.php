<?php
/**
 * Fires only when the user deletes the plugin from wp-admin (not on deactivate).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sal_logs" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

delete_option( 'sal_db_version' );
delete_option( 'sal_retention_days' );
delete_option( 'sal_alerts_enabled' );
delete_option( 'sal_alert_email' );

wp_clear_scheduled_hook( 'sal_cleanup_logs' );
