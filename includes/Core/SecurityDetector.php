<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects suspicious authentication patterns from recorded activity.
 */
class SecurityDetector {

	const DEFAULT_WINDOW_HOURS = 1;
	const DEFAULT_THRESHOLD    = 5;

	public static function register() {
		add_action( 'sal_logged', array( __CLASS__, 'on_logged' ), 20, 4 );
		add_filter( 'authenticate', array( __CLASS__, 'block_authentication' ), 1, 3 );
	}

	/**
	 * Stop authentication from an explicitly blocked IP before credential checks.
	 *
	 * @param mixed  $user     User or WP_Error from earlier authentication handlers.
	 * @param string $username Submitted username.
	 * @param string $password Submitted password.
	 * @return mixed
	 */
	public static function block_authentication( $user, $username, $password ) {
		$ip = Logger::get_client_ip();
		if ( ! $ip || ! IPBlocklist::is_blocked( $ip ) ) {
			return $user;
		}

		return new \WP_Error(
			'sal_ip_blocked',
			__( 'Authentication from this IP address has been blocked.', 'simple-activity-log' )
		);
	}

	public static function on_logged( $log_id, $action, $message, $args ) {
		if ( 'login_failed' !== $action ) {
			return;
		}

		$ip        = ! empty( $args['ip_address'] ) ? $args['ip_address'] : Logger::get_client_ip();
		$username  = ! empty( $args['username'] ) ? sanitize_user( $args['username'], true ) : '';
		$window    = self::window_hours();
		$threshold = self::threshold();
		$signals   = array();

		if ( $ip ) {
			$by_ip = self::find_by_ip( $ip, $window );
			IPBlocklist::maybe_auto_block( $ip, $by_ip['attempts'] );
			if ( $by_ip['attempts'] >= $threshold ) {
				$signals[] = array(
					'type'               => 'ip_bruteforce',
					'attempts'           => $by_ip['attempts'],
					'distinct_usernames' => $by_ip['distinct_usernames'],
				);
			}
		}

		if ( $username ) {
			$by_username = self::find_by_username( $username, $window );
			if ( $by_username['attempts'] >= $threshold ) {
				$signals[] = array(
					'type'         => 'account_targeting',
					'attempts'     => $by_username['attempts'],
					'distinct_ips' => $by_username['distinct_ips'],
				);
			}
		}

		if ( empty( $signals ) ) {
			return;
		}

		$score = self::score( $signals );
		$level = self::risk_level( $score );

		IncidentManager::create_or_increment( 'login_attack', $level, $score, $username, $ip, $signals, $log_id );

		Logger::log(
			'suspicious_activity',
			sprintf(
				/* translators: 1: number of signals, 2: risk score */
				__( 'Suspicious login activity detected: %1$d signal(s), risk score %2$d.', 'simple-activity-log' ),
				count( $signals ),
				$score
			),
			array(
				'object_type' => 'security',
				'object_id'   => absint( $log_id ),
				'ip_address'  => $ip,
				'meta'        => array(
					'source_log_id' => absint( $log_id ),
					'username'      => $username,
					'risk_score'    => $score,
					'risk_level'    => $level,
					'window_hours'  => $window,
					'signals'       => $signals,
				),
			)
		);
	}

	private static function find_by_ip( $ip, $hours ) {
		global $wpdb;
		$table = esc_sql( Database::table() );
		$since = wp_date( 'Y-m-d H:i:s', time() - ( $hours * HOUR_IN_SECONDS ) );
		$sql   = 'SELECT COUNT(*) AS attempts, COUNT(DISTINCT username) AS distinct_usernames FROM ' . $table . " WHERE action = 'login_failed' AND ip_address = %s AND created_at >= %s";
		$row   = $wpdb->get_row( $wpdb->prepare( $sql, $ip, $since ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array(
			'attempts'           => $row ? (int) $row->attempts : 0,
			'distinct_usernames' => $row ? (int) $row->distinct_usernames : 0,
		);
	}

	private static function find_by_username( $username, $hours ) {
		global $wpdb;
		$table = esc_sql( Database::table() );
		$since = wp_date( 'Y-m-d H:i:s', time() - ( $hours * HOUR_IN_SECONDS ) );
		$sql   = 'SELECT COUNT(*) AS attempts, COUNT(DISTINCT ip_address) AS distinct_ips FROM ' . $table . " WHERE action = 'login_failed' AND username = %s AND created_at >= %s";
		$row   = $wpdb->get_row( $wpdb->prepare( $sql, $username, $since ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array(
			'attempts'     => $row ? (int) $row->attempts : 0,
			'distinct_ips' => $row ? (int) $row->distinct_ips : 0,
		);
	}

	private static function score( $signals ) {
		$score = 0;
		foreach ( $signals as $signal ) {
			$score += 'ip_bruteforce' === $signal['type'] ? 45 : 50;
			if ( isset( $signal['distinct_usernames'] ) && $signal['distinct_usernames'] >= self::threshold() ) {
				$score += 15;
			}
			if ( isset( $signal['distinct_ips'] ) && $signal['distinct_ips'] >= self::threshold() ) {
				$score += 15;
			}
		}
		return min( 100, $score );
	}

	private static function risk_level( $score ) {
		if ( $score >= 80 ) {
			return 'critical';
		}
		if ( $score >= 60 ) {
			return 'high';
		}
		if ( $score >= 30 ) {
			return 'medium';
		}
		return 'low';
	}

	private static function threshold() {
		return max( 1, (int) apply_filters( 'sal_security_detection_threshold', self::DEFAULT_THRESHOLD ) );
	}

	private static function window_hours() {
		return max( 1, (int) apply_filters( 'sal_security_detection_window_hours', self::DEFAULT_WINDOW_HOURS ) );
	}
}
