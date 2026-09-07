<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The central event writer for Simple Activity Log.
 *
 * Loggers and third-party integrations should use this class (or the
 * public sal_log() helper) instead of writing to the database directly.
 */
class Logger {

	/**
	 * Record an activity event.
	 *
	 * @param string $action Machine-readable event type.
	 * @param string $message Human-readable event summary.
	 * @param array  $args {
	 *     Optional event context.
	 *
	 *     @type int    $user_id     User responsible for the event.
	 *     @type string $username    Username snapshot at log time.
	 *     @type string $object_type Affected object type.
	 *     @type int    $object_id   Affected object ID.
	 *     @type array  $meta        Structured event metadata.
	 *     @type string $ip_address  IP address override.
	 * }
	 * @return int|false Inserted log ID on success, false on failure.
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

		$action  = substr( sanitize_key( $action ), 0, 50 );
		$message = sanitize_text_field( $message );

		if ( '' === $action || '' === $message ) {
			return false;
		}

		$user_id = absint( $args['user_id'] );

		if ( null === $args['username'] && $user_id ) {
			$user             = get_userdata( $user_id );
			$args['username'] = $user ? $user->user_login : null;
		}

		$ip_address = $args['ip_address'] ?: self::get_client_ip();
		if ( $ip_address && ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
			$ip_address = null;
		}

		$meta = is_array( $args['meta'] ) ? $args['meta'] : array();
		$risk_score = RiskEngine::score( $action, $meta );
		$meta['risk_score'] = $risk_score;
		$meta['risk_level'] = RiskEngine::level( $risk_score );
		$meta = wp_json_encode( $meta );

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			Database::table(),
			array(
				'user_id'     => $user_id,
				'username'    => $args['username'] ? sanitize_user( $args['username'], true ) : null,
				'action'      => $action,
				'object_type' => $args['object_type'] ? sanitize_key( $args['object_type'] ) : null,
				'object_id'   => null === $args['object_id'] ? null : absint( $args['object_id'] ),
				'message'     => $message,
				'ip_address'  => $ip_address,
				'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : null, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				'meta'        => $meta,
				'created_at'  => current_time( 'mysql' ),
			)
		);

		if ( false === $inserted ) {
			return false;
		}

		$log_id = (int) $wpdb->insert_id;

		/**
		 * Fires after an activity event has been stored successfully.
		 *
		 * @param int    $log_id   Inserted log ID.
		 * @param string $action   Machine-readable event type.
		 * @param string $message  Human-readable event summary.
		 * @param array  $args     Event context.
		 */
		do_action( 'sal_logged', $log_id, $action, $message, $args );

		return $log_id;
	}

	/**
	 * Best-effort client IP, checking common proxy headers before falling
	 * back to REMOTE_ADDR. Not spoof-proof — do not use this as a security
	 * control without a trusted-proxy allowlist.
	 *
	 * @return string|null Valid IP address or null when unavailable.
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
