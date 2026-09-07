<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One flexible table for every logger (auth now; products/orders/settings/
 * security later) so adding a new logger never needs a schema migration —
 * object_type/object_id/meta are generic enough to cover all of them.
 */
class Database {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sal_logs';
	}

	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = self::table();
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
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( 'sal_db_version', SAL_DB_VERSION );
	}

	public static function maybe_upgrade() {
		if ( get_option( 'sal_db_version' ) !== SAL_DB_VERSION ) {
			self::install();
		}
	}
}
