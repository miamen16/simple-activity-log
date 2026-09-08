<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculates a normalized risk score for activity events.
 */
class RiskEngine {

	public static function score( $action, array $context = array() ) {
		$score = EventRegistry::risk( $action );

		if ( 'login_failed' === $action ) {
			$score += min( 20, max( 0, absint( $context['recent_failures'] ?? 0 ) * 5 ) );
		}

		if ( ! empty( $context['privileged'] ) ) {
			$score += 20;
		}

		if ( ! empty( $context['suspicious_ip'] ) ) {
			$score += 20;
		}

		return min( 100, max( 0, $score ) );
	}

	public static function level( $score ) {
		$score = min( 100, max( 0, absint( $score ) ) );
		if ( $score >= 80 ) {
			return 'critical';
		}
		if ( $score >= 60 ) {
			return 'high';
		}
		if ( $score >= 30 ) {
			return 'medium';
		}
		return 'low';
	}
}
