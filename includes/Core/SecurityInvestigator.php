<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds investigation reports for IP addresses and usernames.
 */
class SecurityInvestigator {

	/**
	 * Investigate an IP address.
	 *
	 * @param string $ip IP address.
	 * @param int    $hours Number of hours to inspect.
	 * @return array
	 */
	public static function by_ip( $ip, $hours = 168 ) {
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );
		if ( ! $ip ) {
			return array();
		}

		$hours = max( 1, min( 720, (int) $hours ) );
		return self::build_report( 'ip_address', $ip, $hours );
	}

	/**
	 * Investigate a username.
	 *
	 * @param string $username Username.
	 * @param int    $hours Number of hours to inspect.
	 * @return array
	 */
	public static function by_username( $username, $hours = 168 ) {
		$username = sanitize_user( $username, true );
		if ( '' === $username ) {
			return array();
		}

		$hours = max( 1, min( 720, (int) $hours ) );
		return self::build_report( 'username', $username, $hours );
	}

	private static function build_report( $field, $value, $hours ) {
		global $wpdb;
		$table = esc_sql( Database::table() );
		$since = wp_date( 'Y-m-d H:i:s', time() - ( $hours * HOUR_IN_SECONDS ) );

		$where = 'created_at >= %s AND ' . $field . ' = %s';
		$sql   = 'SELECT * FROM ' . $table . ' WHERE ' . $where . ' ORDER BY created_at DESC, id DESC LIMIT 200';
		$logs  = $wpdb->get_results( $wpdb->prepare( $sql, $since, $value ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$summary_sql = 'SELECT COUNT(*) AS total, MIN(created_at) AS first_seen, MAX(created_at) AS last_seen FROM ' . $table . ' WHERE ' . $where;
		$summary_row = $wpdb->get_row( $wpdb->prepare( $summary_sql, $since, $value ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$failed_sql = 'SELECT COUNT(*) FROM ' . $table . " WHERE action = 'login_failed' AND " . $where;
		$failed     = (int) $wpdb->get_var( $wpdb->prepare( $failed_sql, $since, $value ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$login_sql = 'SELECT COUNT(*) FROM ' . $table . " WHERE action = 'login' AND " . $where;
		$logins    = (int) $wpdb->get_var( $wpdb->prepare( $login_sql, $since, $value ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$group_field = 'ip_address' === $field ? 'username' : 'ip_address';
		$related_sql = 'SELECT ' . $group_field . ', COUNT(*) AS attempts, MAX(created_at) AS last_seen FROM ' . $table . ' WHERE ' . $where . ' GROUP BY ' . $group_field . ' ORDER BY attempts DESC LIMIT 50';
		$related     = $wpdb->get_results( $wpdb->prepare( $related_sql, $since, $value ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$incidents = self::related_incidents( $field, $value );
		$score     = self::risk_score( $failed, count( $incidents ) );

		return array(
			'type'       => $field,
			'value'      => $value,
			'hours'      => $hours,
			'total'      => $summary_row ? (int) $summary_row->total : 0,
			'failed'     => $failed,
			'logins'     => $logins,
			'first_seen' => $summary_row ? $summary_row->first_seen : '',
			'last_seen'  => $summary_row ? $summary_row->last_seen : '',
			'score'      => $score,
			'level'      => RiskEngine::level( $score ),
			'logs'       => $logs,
			'related'    => $related,
			'incidents'  => $incidents,
		);
	}

	private static function related_incidents( $field, $value ) {
		global $wpdb;
		$table = esc_sql( IncidentManager::table() );
		$sql   = 'SELECT * FROM ' . $table . ' WHERE ' . $field . ' = %s ORDER BY last_seen DESC LIMIT 50';
		return $wpdb->get_results( $wpdb->prepare( $sql, $value ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	private static function risk_score( $failed, $incident_count ) {
		$score = min( 60, absint( $failed ) * 5 );
		$score += min( 40, absint( $incident_count ) * 20 );
		return min( 100, $score );
	}
}
