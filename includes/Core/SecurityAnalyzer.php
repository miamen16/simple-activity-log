<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns the raw 'login_failed' events AuthLogger already records into two
 * security-relevant views:
 *
 * 1. By IP address — an IP trying many DIFFERENT usernames in a short
 *    window looks like brute-force / credential-stuffing scanning.
 * 2. By username — one username getting hit from many DIFFERENT IPs
 *    looks like a targeted/distributed attack on that specific account.
 *
 * Deliberately reuses the existing log data rather than a separate
 * security table — same principle as VendorHealth reusing Store
 * Doctor's scan data instead of re-scanning per vendor.
 */
class SecurityAnalyzer {

	const DEFAULT_THRESHOLD = 5;

	/**
	 * How many failed attempts in the window before a row is flagged
	 * "Suspicious". Filterable so a site can tune it without editing
	 * plugin files.
	 */
	public static function threshold() {
		return (int) apply_filters( 'sal_suspicious_login_threshold', self::DEFAULT_THRESHOLD );
	}

	/**
	 * Failed logins grouped by IP, with how many distinct usernames each
	 * IP tried — a high distinct-username count from one IP is the
	 * clearest brute-force signal.
	 *
	 * @param int $hours Lookback window.
	 */
	public static function get_failed_logins_by_ip( $hours = 24 ) {
		global $wpdb;
		$table = Database::table();
		$since = self::since( $hours );

		return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT ip_address, COUNT(*) as attempts, COUNT(DISTINCT username) as distinct_usernames,
				GROUP_CONCAT(DISTINCT username ORDER BY username SEPARATOR ', ') as usernames_tried,
				MAX(created_at) as last_attempt
			 FROM {$table}
			 WHERE action = 'login_failed' AND created_at >= %s AND ip_address IS NOT NULL AND ip_address != ''
			 GROUP BY ip_address
			 ORDER BY attempts DESC
			 LIMIT 100",
			$since
		) );
	}

	/**
	 * Failed logins grouped by attempted username, with how many
	 * distinct IPs tried it — a high distinct-IP count for one username
	 * suggests a targeted or distributed attack on that account.
	 *
	 * @param int $hours Lookback window.
	 */
	public static function get_failed_logins_by_username( $hours = 24 ) {
		global $wpdb;
		$table = Database::table();
		$since = self::since( $hours );

		return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT username, COUNT(*) as attempts, COUNT(DISTINCT ip_address) as distinct_ips,
				MAX(created_at) as last_attempt
			 FROM {$table}
			 WHERE action = 'login_failed' AND created_at >= %s AND username IS NOT NULL AND username != ''
			 GROUP BY username
			 ORDER BY attempts DESC
			 LIMIT 100",
			$since
		) );
	}

	/**
	 * Summary counters for the top of the Security page: total failed
	 * attempts, distinct IPs, and distinct usernames within the window.
	 */
	public static function get_summary( $hours = 24 ) {
		global $wpdb;
		$table = Database::table();
		$since = self::since( $hours );

		$row = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COUNT(*) as total_attempts,
				COUNT(DISTINCT ip_address) as distinct_ips,
				COUNT(DISTINCT username) as distinct_usernames
			 FROM {$table}
			 WHERE action = 'login_failed' AND created_at >= %s",
			$since
		) );

		return array(
			'total_attempts'    => $row ? (int) $row->total_attempts : 0,
			'distinct_ips'      => $row ? (int) $row->distinct_ips : 0,
			'distinct_usernames' => $row ? (int) $row->distinct_usernames : 0,
		);
	}

	public static function is_suspicious( $attempts ) {
		return (int) $attempts >= self::threshold();
	}

	/**
	 * created_at is stored via current_time('mysql') (site-local time),
	 * so the cutoff must be computed the same way to compare correctly.
	 */
	private static function since( $hours ) {
		return gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( (int) $hours * HOUR_IN_SECONDS ) );
	}
}
