<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The only class that writes to wp_sal_logs. Every Logger (AuthLogger,
 * and future ProductLogger/OrderLogger/SettingsLogger/SecurityLogger)
 * calls Logger::log() instead of touching the DB directly, so the log
 * format stays consistent no matter how many logger types get added.
 */
class Logger {

	/**
	 * @param string $action      Machine-readable event type, e.g. 'login', 'login_failed'.
	 * @param string $message     Human-readable summary, e.g. "Ahmed logged in".
	 * @param array  $args {
	 *     @type int    $user_id     Defaults to the current logged-in user (0 if none/unknown).
	 *     @type string $username    Snapshot of the username at log time (survives account deletion).
	 *     @type string $object_type e.g. 'product', 'order', 'option' — optional.
	 *     @type int    $object_id   ID of the affected object — optional.
	 *     @type array  $meta        Extra structured data for this event — optional.
	 *     @type string $ip_address  Overrides the auto-detected IP — optional.
	 * }
	 */
	public static function log( $action, $message, array $args = array() ) {
		global $wpdb;

		$defaults = array(
			'user_id'     => get_current_user_id(),
			'username'    => null,
			'object_type' => null,
			'object_id'   => null,
			'meta'        => null,
			'ip_address'  => null,
		);
		$args = wp_parse_args( $args, $defaults );

		if ( null === $args['username'] && $args['user_id'] ) {
			$user             = get_userdata( $args['user_id'] );
			$args['username'] = $user ? $user->user_login : null;
		}

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			Database::table(),
			array(
				'user_id'     => (int) $args['user_id'],
				'username'    => $args['username'],
				'action'      => $action,
				'object_type' => $args['object_type'],
				'object_id'   => $args['object_id'],
				'message'     => $message,
				'ip_address'  => $args['ip_address'] ?: self::get_client_ip(),
				'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : null, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				'meta'        => $args['meta'] ? wp_json_encode( $args['meta'] ) : null,
				'created_at'  => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Best-effort client IP, checking common proxy headers before falling
	 * back to REMOTE_ADDR. Not spoof-proof (no header is, without a
	 * trusted-proxy allowlist) — good enough for an activity log, not a
	 * security control.
	 */
	public static function get_client_ip() {
		$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				// X-Forwarded-For can be a comma-separated chain; use the first entry.
				$ip = trim( explode( ',', $ip )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return null;
	}
}
