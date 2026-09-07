<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends throttled alerts for security incidents.
 */
class IncidentAlertManager {

	/**
	 * Register the incident alert listener.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'sal_incident_created', array( __CLASS__, 'handle' ), 10, 2 );
	}

	/**
	 * Handle a newly created or updated incident.
	 *
	 * @param int    $incident_id Incident ID.
	 * @param object $incident Incident object.
	 * @return void
	 */
	public static function handle( $incident_id, $incident ) {
		if ( ! $incident || ! AlertPolicy::settings()['enabled'] || ! AlertPolicy::should_alert( $incident->severity ) ) {
			return;
		}

		$settings = AlertPolicy::settings();
		$key = 'sal_incident_alert_' . absint( $incident_id );

		if ( get_transient( $key ) ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Security incident: %s', 'simple-activity-log' ),
			get_bloginfo( 'name' ),
			$incident->title
		);

		$message = sprintf(
			/* translators: 1: severity, 2: score, 3: username, 4: IP address, 5: occurrences */
			__( "A security incident requires attention.\n\nSeverity: %1$s\nRisk score: %2$d/100\nUsername: %3$s\nIP address: %4$s\nOccurrences: %5$d\n\nReview the incident in the WordPress admin Security > Incidents page.", 'simple-activity-log' ),
			ucfirst( sanitize_key( $incident->severity ) ),
			absint( $incident->score ),
			$incident->username ? $incident->username : __( 'Unknown', 'simple-activity-log' ),
			$incident->ip_address ? $incident->ip_address : __( 'Unknown', 'simple-activity-log' ),
			absint( $incident->occurrences )
		);

		if ( wp_mail( $settings['email'], $subject, $message ) ) {
			set_transient( $key, 1, $settings['cooldown_minutes'] * MINUTE_IN_SECONDS );
		}
	}
}
