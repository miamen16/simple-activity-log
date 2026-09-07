<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read-side counterpart to Logger — filtering, pagination, and the data
 * CSV export needs. Every logger's events go through the same table, so
 * one query class serves all of them.
 */
class LogQuery {

	const MAX_PER_PAGE = 5000;

	/**
	 * @param array $args {
	 *     @type int    $user_id
	 *     @type string $action
	 *     @type string $date_from  Y-m-d
	 *     @type string $date_to    Y-m-d
	 *     @type int    $page
	 *     @type int    $per_page
	 * }
	 */
	public static function get_logs( array $args = array() ) {
		global $wpdb;
		$table = esc_sql( Database::table() );

		$args = wp_parse_args( $args, array(
			'user_id'   => 0,
			'action'    => '',
			'date_from' => '',
			'date_to'   => '',
			'page'      => 1,
			'per_page'  => 50,
		) );

		$page     = max( 1, (int) $args['page'] );
		$per_page = min( self::MAX_PER_PAGE, max( 1, (int) $args['per_page'] ) );

		list( $where, $params ) = self::build_where( $args );

		$offset = ( $page - 1 ) * $per_page;

		$sql      = 'SELECT * FROM ' . $table . ' WHERE ' . implode( ' AND ', $where )
			. ' ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d';
		$params[] = $per_page;
		$params[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	public static function count_logs( array $args = array() ) {
		global $wpdb;
		$table = esc_sql( Database::table() );

		list( $where, $params ) = self::build_where( $args );
		$where[]  = '1 = %d';
		$params[] = 1;

		$sql = 'SELECT COUNT(*) FROM ' . $table . ' WHERE ' . implode( ' AND ', $where );

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get recent security detection events.
	 *
	 * @param int $hours Number of hours to inspect.
	 * @param int $limit Maximum number of rows.
	 * @return array
	 */
	public static function get_security_events( $hours = 24, $limit = 50 ) {
		global $wpdb;
		$table = esc_sql( Database::table() );
		$hours = max( 1, min( 720, (int) $hours ) );
		$limit = max( 1, min( 200, (int) $limit ) );
		$since = wp_date( 'Y-m-d H:i:s', time() - ( $hours * HOUR_IN_SECONDS ) );

		$sql = 'SELECT * FROM ' . $table
			. ' WHERE action = %s AND created_at >= %s'
			. ' ORDER BY created_at DESC, id DESC LIMIT %d';

		return $wpdb->get_results(
			$wpdb->prepare( $sql, 'suspicious_activity', $since, $limit )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Aggregate suspicious activity by hour or day for dashboard charts.
	 *
	 * @param int $hours Number of hours to inspect.
	 * @return array
	 */
	public static function get_security_trend( $hours = 24 ) {
		global $wpdb;
		$table = esc_sql( Database::table() );
		$hours = max( 1, min( 720, (int) $hours ) );
		$since = wp_date( 'Y-m-d H:i:s', time() - ( $hours * HOUR_IN_SECONDS ) );

		if ( $hours <= 24 ) {
			$format = '%Y-%m-%d %H:00:00';
		} else {
			$format = '%Y-%m-%d 00:00:00';
		}

		$sql = 'SELECT DATE_FORMAT(created_at, %s) AS period, COUNT(*) AS total FROM ' . $table
			. ' WHERE action = %s AND created_at >= %s'
			. ' GROUP BY period ORDER BY period ASC';

		$rows = $wpdb->get_results(
			$wpdb->prepare( $sql, $format, 'suspicious_activity', $since )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$trend = array();
		foreach ( $rows as $row ) {
			$trend[ $row->period ] = (int) $row->total;
		}

		return $trend;
	}

	/**
	 * Get a compact security-event summary for a time window.
	 *
	 * @param int $hours Number of hours to inspect.
	 * @return array
	 */
	public static function get_security_summary( $hours = 24 ) {
		$events = self::get_security_events( $hours, 200 );
		$summary = array(
			'total'    => count( $events ),
			'critical' => 0,
			'high'     => 0,
			'medium'   => 0,
			'low'      => 0,
		);

		foreach ( $events as $event ) {
			$meta = json_decode( (string) $event->meta, true );
			$level = is_array( $meta ) && ! empty( $meta['risk_level'] )
				? sanitize_key( $meta['risk_level'] )
				: 'low';

			if ( isset( $summary[ $level ] ) ) {
				$summary[ $level ]++;
			}
		}

		return $summary;
	}

	/**
	 * Distinct users who have at least one log entry, for the filter dropdown.
	 */
	public static function get_logged_users() {
		global $wpdb;
		$table = esc_sql( Database::table() );
		$sql   = 'SELECT DISTINCT user_id, username FROM ' . $table . ' WHERE user_id > %d ORDER BY username ASC';

		return $wpdb->get_results( $wpdb->prepare( $sql, 0 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Distinct action types recorded so far, for the filter dropdown.
	 */
	public static function get_distinct_actions() {
		global $wpdb;
		$table = esc_sql( Database::table() );
		$sql   = 'SELECT DISTINCT action FROM ' . $table . ' WHERE 1 = %d ORDER BY action ASC';

		return $wpdb->get_col( $wpdb->prepare( $sql, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	private static function build_where( array $args ) {
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = (int) $args['user_id'];
		}
		if ( ! empty( $args['action'] ) ) {
			$where[]  = 'action = %s';
			$params[] = $args['action'];
		}
		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'created_at >= %s';
			$params[] = $args['date_from'] . ' 00:00:00';
		}
		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'created_at <= %s';
			$params[] = $args['date_to'] . ' 23:59:59';
		}

		return array( $where, $params );
	}
}
