<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrates activity-log records with WordPress personal-data tools.
 */
class Privacy {

	const PAGE_SIZE = 100;

	/**
	 * Register the plugin's personal-data exporter and eraser.
	 */
	public static function register() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	/**
	 * Register the activity-log personal-data exporter.
	 *
	 * @param array $exporters Existing exporter callbacks.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['simple-activity-log'] = array(
			'exporter_friendly_name' => __( 'Simple Activity Log', 'simple-activity-log' ),
			'callback'              => array( __CLASS__, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * Register the activity-log personal-data eraser.
	 *
	 * @param array $erasers Existing eraser callbacks.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['simple-activity-log'] = array(
			'eraser_friendly_name' => __( 'Simple Activity Log', 'simple-activity-log' ),
			'callback'              => array( __CLASS__, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * Export log records associated with a user's email address.
	 *
	 * @param string $email_address User email address.
	 * @param int    $page          Export page number.
	 * @return array
	 */
	public static function export_personal_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$logs = self::get_user_logs( $user->ID, $user->user_login, $page );
		$data = array();

		foreach ( $logs as $log ) {
			$data[] = array(
				'name'  => __( 'Activity Log', 'simple-activity-log' ),
				'value' => array(
					array(
						'name'  => __( 'Date', 'simple-activity-log' ),
						'value' => $log->created_at,
					),
					array(
						'name'  => __( 'Action', 'simple-activity-log' ),
						'value' => $log->action,
					),
					array(
						'name'  => __( 'Message', 'simple-activity-log' ),
						'value' => $log->message,
					),
					array(
						'name'  => __( 'IP Address', 'simple-activity-log' ),
						'value' => $log->ip_address,
					),
					array(
						'name'  => __( 'User Agent', 'simple-activity-log' ),
						'value' => $log->user_agent,
					),
				),
			);
		}

		return array(
			'data' => $data,
			'done' => count( $logs ) < self::PAGE_SIZE,
		);
	}

	/**
	 * Erase log records associated with a user's email address.
	 *
	 * The table is reduced after each page, so every request reads the
	 * first remaining page rather than using an offset that could skip rows.
	 *
	 * @param string $email_address User email address.
	 * @param int    $page          Erasure page number (unused; required by the API).
	 * @return array
	 */
	public static function erase_personal_data( $email_address, $page = 1 ) {
		unset( $page );

		$user = get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return array(
				'messages'       => array( __( 'No Simple Activity Log records were found for this email address.', 'simple-activity-log' ) ),
				'done'           => true,
				'items_removed'  => false,
				'items_retained' => false,
			);
		}

		global $wpdb;
		$table = esc_sql( Database::table() );
		$ids   = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d OR username = %s ORDER BY id ASC LIMIT %d",
			$user->ID,
			$user->user_login,
			self::PAGE_SIZE
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders

		if ( empty( $ids ) ) {
			return array(
				'messages'       => array( __( 'Simple Activity Log records were erased.', 'simple-activity-log' ) ),
				'done'           => true,
				'items_removed'  => false,
				'items_retained' => false,
			);
		}

		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders

		return array(
			'messages'       => array( __( 'Simple Activity Log records were erased.', 'simple-activity-log' ) ),
			'done'           => count( $ids ) < self::PAGE_SIZE,
			'items_removed'  => true,
			'items_retained' => false,
		);
	}

	/**
	 * Get a page of log records belonging to a user.
	 *
	 * @param int    $user_id  WordPress user ID.
	 * @param string $username WordPress username snapshot.
	 * @param int    $page     Page number.
	 * @return array
	 */
	private static function get_user_logs( $user_id, $username, $page ) {
		global $wpdb;
		$table  = esc_sql( Database::table() );
		$offset = ( max( 1, (int) $page ) - 1 ) * self::PAGE_SIZE;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT created_at, action, message, ip_address, user_agent FROM {$table} WHERE user_id = %d OR username = %s ORDER BY id ASC LIMIT %d OFFSET %d",
			$user_id,
			$username,
			self::PAGE_SIZE,
			$offset
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
	}
}
