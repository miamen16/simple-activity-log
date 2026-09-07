<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A logging plugin that runs forever needs a retention policy, or the
 * table grows unbounded. Runs a daily cron that deletes rows older than
 * the configured retention window. 0 days means "keep forever" (cleanup
 * disabled) — the default is a non-zero window so a fresh install
 * doesn't silently grow forever if nobody visits the settings page.
 */
class Retention {

	const CRON_HOOK    = 'sal_cleanup_logs';
	const DEFAULT_DAYS = 90;
	const OPTION_DAYS  = 'sal_retention_days';

	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	public static function get_retention_days() {
		return (int) get_option( self::OPTION_DAYS, self::DEFAULT_DAYS );
	}

	public static function run_cleanup() {
		$days = self::get_retention_days();

		if ( $days <= 0 ) {
			return; // Keep forever.
		}

		global $wpdb;
		$table  = Database::table();
		$cutoff = wp_date( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders
	}
}
