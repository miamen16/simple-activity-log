<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns the Security page from something you have to remember to check
 * into something that emails you. Checked right after each failed-login
 * event is logged (see AuthLogger) — if the IP or username has crossed
 * the suspicious threshold within the last hour, send one alert email,
 * then stay quiet for a cooldown period so a sustained attack doesn't
 * flood the inbox with one email per attempt.
 */
class AlertManager {

	const OPTION_ENABLED = 'sal_alerts_enabled';
	const OPTION_EMAIL   = 'sal_alert_email';
	const COOLDOWN       = HOUR_IN_SECONDS;
	const CHECK_WINDOW_HOURS = 1;

	public static function is_enabled() {
		return '1' === get_option( self::OPTION_ENABLED, '0' );
	}

	public static function get_alert_email() {
		$email = get_option( self::OPTION_EMAIL, '' );
		return $email ? $email : get_option( 'admin_email' );
	}

	/**
	 * Called after a failed login is logged. Checks both clustering
	 * shapes (by IP, by username) and alerts on whichever crossed the
	 * threshold, independently.
	 */
	public static function check_and_alert( $ip, $username ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		$threshold = SecurityAnalyzer::threshold();

		if ( $ip ) {
			$count = self::count_since( 'ip_address', $ip );
			if ( $count >= $threshold ) {
				self::maybe_send(
					'ip:' . $ip,
					sprintf(
						/* translators: 1: number of attempts, 2: IP address */
						__( '%1$d failed login attempts detected from IP %2$s in the last hour.', 'simple-activity-log' ),
						$count,
						$ip
					)
				);
			}
		}

		if ( $username ) {
			$count = self::count_since( 'username', $username );
			if ( $count >= $threshold ) {
				self::maybe_send(
					'user:' . $username,
					sprintf(
						/* translators: 1: number of attempts, 2: username */
						__( '%1$d failed login attempts detected for username "%2$s" in the last hour.', 'simple-activity-log' ),
						$count,
						$username
					)
				);
			}
		}
	}

	private static function count_since( $column, $value ) {
		global $wpdb;
		$table = Database::table();
		$since = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( self::CHECK_WINDOW_HOURS * HOUR_IN_SECONDS ) );

		return (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COUNT(*) FROM {$table} WHERE action = 'login_failed' AND {$column} = %s AND created_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
			$value,
			$since
		) );
	}

	private static function maybe_send( $key, $message ) {
		$transient_key = 'sal_alert_' . md5( $key );

		if ( get_transient( $transient_key ) ) {
			return; // Already alerted on this within the cooldown window.
		}

		set_transient( $transient_key, 1, self::COOLDOWN );

		$to      = self::get_alert_email();
		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Suspicious login activity detected', 'simple-activity-log' ),
			get_bloginfo( 'name' )
		);

		wp_mail( $to, $subject, $message );
	}
}
