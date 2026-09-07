<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized policy for deciding whether a security incident should alert.
 */
class AlertPolicy {

	/**
	 * Get the configured alert settings.
	 *
	 * @return array
	 */
	public static function settings() {
		return array(
			'enabled'          => (bool) get_option( 'sal_alerts_enabled', false ),
			'email'            => sanitize_email( get_option( 'sal_alert_email', get_option( 'admin_email' ) ) ),
			'min_severity'     => sanitize_key( get_option( 'sal_alert_min_severity', 'high' ) ),
			'cooldown_minutes' => max( 5, min( 1440, absint( get_option( 'sal_alert_cooldown_minutes', 60 ) ) ) ),
		);
	}

	/**
	 * Determine whether a severity is alert-worthy.
	 *
	 * @param string $severity Incident severity.
	 * @return bool
	 */
	public static function should_alert( $severity ) {
		$levels = array( 'low' => 10, 'medium' => 20, 'high' => 30, 'critical' => 40 );
		$settings = self::settings();
		$severity = sanitize_key( $severity );
		$minimum  = isset( $levels[ $settings['min_severity'] ] ) ? $settings['min_severity'] : 'high';

		return isset( $levels[ $severity ], $levels[ $minimum ] ) && $levels[ $severity ] >= $levels[ $minimum ];
	}
}
