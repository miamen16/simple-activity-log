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

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders
	}

	public static function count_logs( array $args = array() ) {
		global $wpdb;
		$table = esc_sql( Database::table() );

		list( $where, $params ) = self::build_where( $args );
		$where[]  = '1 = %d';
		$params[] = 1;

		$sql = 'SELECT COUNT(*) FROM ' . $table . ' WHERE ' . implode( ' AND ', $where );

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders
	}

	/**
	 * Distinct users who have at least one log entry, for the filter dropdown.
	 */
	public static function get_logged_users() {
		global $wpdb;
		$table = esc_sql( Database::table() );
		$sql   = 'SELECT DISTINCT user_id, username FROM ' . $table . ' WHERE user_id > 0 ORDER BY username ASC';

		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Distinct action types recorded so far, for the filter dropdown.
	 */
	public static function get_distinct_actions() {
		global $wpdb;
		$table = esc_sql( Database::table() );
		$sql   = 'SELECT DISTINCT action FROM ' . $table . ' ORDER BY action ASC';

		return $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
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
