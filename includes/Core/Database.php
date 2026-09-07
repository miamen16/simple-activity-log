<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database schema manager for logs and security incidents.
 */
class Database {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sal_logs';
	}

	public static function incidents_table() {
		global $wpdb;
		return $wpdb->prefix . 'sal_incidents';
	}

	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = esc_sql( self::table() );
		$incidents_table = esc_sql( self::incidents_table() );
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			username VARCHAR(100) DEFAULT NULL,
			action VARCHAR(50) NOT NULL,
			object_type VARCHAR(30) DEFAULT NULL,
			object_id BIGINT UNSIGNED DEFAULT NULL,
			message TEXT NOT NULL,
			ip_address VARCHAR(45) DEFAULT NULL,
			user_agent VARCHAR(255) DEFAULT NULL,
			meta LONGTEXT DEFAULT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY created_at (created_at),
			KEY action_created_at (action, created_at),
			KEY ip_created_at (ip_address, created_at),
			KEY username_created_at (username, created_at)
		) {$charset_collate};

		CREATE TABLE {$incidents_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			fingerprint CHAR(32) NOT NULL,
			type VARCHAR(50) NOT NULL,
			title VARCHAR(255) NOT NULL,
			severity VARCHAR(20) NOT NULL,
			score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			username VARCHAR(100) DEFAULT NULL,
			ip_address VARCHAR(45) DEFAULT NULL,
			occurrences INT UNSIGNED NOT NULL DEFAULT 1,
			first_seen DATETIME NOT NULL,
			last_seen DATETIME NOT NULL,
			signals LONGTEXT DEFAULT NULL,
			source_log_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY fingerprint (fingerprint),
			KEY status (status),
			KEY severity (severity),
			KEY last_seen (last_seen),
			KEY ip_address (ip_address)
		) {$charset_collate};";

		dbDelta( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		update_option( 'sal_db_version', SAL_DB_VERSION );
	}

	public static function maybe_upgrade() {
		global $wpdb;
		$incidents_table = esc_sql( self::incidents_table() );
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $incidents_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared

		if ( get_option( 'sal_db_version' ) !== SAL_DB_VERSION || $exists !== $incidents_table ) {
			self::install();
		}
	}
}
