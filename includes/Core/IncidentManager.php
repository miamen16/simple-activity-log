<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages security incidents independently from raw activity logs.
 */
class IncidentManager {

	const STATUS_OPEN          = 'open';
	const STATUS_INVESTIGATING = 'investigating';
	const STATUS_RESOLVED      = 'resolved';
	const STATUS_IGNORED       = 'ignored';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sal_incidents';
	}

	public static function create_or_increment( $type, $severity, $score, $username, $ip, array $signals = array(), $source_log_id = 0 ) {
		global $wpdb;

		$type        = substr( sanitize_key( $type ), 0, 50 );
		$severity    = substr( sanitize_key( $severity ), 0, 20 );
		$username    = sanitize_user( $username, true );
		$ip          = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
		$score       = min( 100, max( 0, absint( $score ) ) );
		$fingerprint = md5( $type . '|' . $username . '|' . $ip );
		$table       = esc_sql( self::table() );
		$now         = current_time( 'mysql' );

		$existing = $wpdb->get_row( $wpdb->prepare( 'SELECT id FROM ' . $table . ' WHERE fingerprint = %s LIMIT 1', $fingerprint ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter

		if ( $existing ) {
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . $table . ' SET severity = %s, score = %d, occurrences = occurrences + 1, last_seen = %s, status = %s, signals = %s, source_log_id = %d, updated_at = %s WHERE id = %d', $severity, $score, $now, self::STATUS_OPEN, wp_json_encode( $signals ), absint( $source_log_id ), $now, absint( $existing->id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$incident_id = (int) $existing->id;
		} else {
			$title = $username
				? sprintf( __( 'Suspicious activity for user %s', 'simple-activity-log' ), $username )
				: __( 'Suspicious activity detected', 'simple-activity-log' );

			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table,
				array(
					'fingerprint'   => $fingerprint,
					'type'          => $type,
					'title'         => $title,
					'severity'      => $severity,
					'score'         => $score,
					'status'        => self::STATUS_OPEN,
					'username'      => $username,
					'ip_address'    => $ip,
					'occurrences'   => 1,
					'first_seen'    => $now,
					'last_seen'     => $now,
					'signals'       => wp_json_encode( $signals ),
					'source_log_id' => absint( $source_log_id ),
					'created_at'    => $now,
					'updated_at'    => $now,
				)
			);
			$incident_id = $wpdb->insert_id ? (int) $wpdb->insert_id : 0;
		}

		if ( $incident_id ) {
			$incident = self::get( $incident_id );
			if ( $incident ) {
				do_action( 'sal_incident_created', $incident_id, $incident );
			}
		}

		return $incident_id ? $incident_id : false;
	}

	public static function get( $id ) {
		global $wpdb;
		$table = esc_sql( self::table() );
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $table . ' WHERE id = %d', absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	public static function all( $status = '', $limit = 100 ) {
		global $wpdb;
		$table = esc_sql( self::table() );
		$limit = min( 200, max( 1, absint( $limit ) ) );
		if ( $status ) {
			return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $table . ' WHERE status = %s ORDER BY last_seen DESC, id DESC LIMIT %d', sanitize_key( $status ), $limit ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter
		}
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $table . ' ORDER BY last_seen DESC, id DESC LIMIT %d', $limit ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get activity surrounding an incident for investigation.
	 *
	 * @param object $incident Incident row.
	 * @param int    $limit Maximum number of logs.
	 * @return array
	 */
	public static function get_timeline( $incident, $limit = 100 ) {
		global $wpdb;

		if ( empty( $incident->first_seen ) || empty( $incident->last_seen ) ) {
			return array();
		}

		$table  = esc_sql( Database::table() );
		$limit  = min( 200, max( 1, absint( $limit ) ) );
		$where  = array( 'created_at BETWEEN %s AND %s' );
		$params = array( $incident->first_seen, $incident->last_seen );

		if ( ! empty( $incident->ip_address ) && ! empty( $incident->username ) ) {
			$where[]  = '( ip_address = %s OR username = %s )';
			$params[] = $incident->ip_address;
			$params[] = $incident->username;
		} elseif ( ! empty( $incident->ip_address ) ) {
			$where[]  = 'ip_address = %s';
			$params[] = $incident->ip_address;
		} elseif ( ! empty( $incident->username ) ) {
			$where[]  = 'username = %s';
			$params[] = $incident->username;
		}

		$params[] = $limit;
		$sql      = 'SELECT * FROM ' . $table . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY created_at DESC, id DESC LIMIT %d';

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	public static function set_status( $id, $status ) {
		global $wpdb;
		$allowed = array( self::STATUS_OPEN, self::STATUS_INVESTIGATING, self::STATUS_RESOLVED, self::STATUS_IGNORED );
		$status  = sanitize_key( $status );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}
		$table = esc_sql( self::table() );
		return false !== $wpdb->update( $table, array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => absint( $id ) ), array( '%s', '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
