<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggregates security metrics for the admin dashboard.
 */
class SecurityDashboard {

	public static function get_data( $hours = 24 ) {
		$hours   = max( 1, min( 720, absint( $hours ) ) );
		$summary = SecurityAnalyzer::get_summary( $hours );
		$incidents = IncidentManager::get_stats( $hours );

		return array(
			'window'          => $hours,
			'failed_attempts' => $summary['total_attempts'],
			'distinct_ips'    => $summary['distinct_ips'],
			'targeted_users'  => $summary['distinct_usernames'],
			'incidents'       => $incidents,
			'top_ips'         => self::top_ips( $hours ),
			'top_users'       => self::top_users( $hours ),
			'trend'           => self::trend( $hours ),
		);
	}

	private static function top_ips( $hours ) {
		$rows = SecurityAnalyzer::get_failed_logins_by_ip( $hours );
		usort(
			$rows,
			function ( $a, $b ) {
				return (int) $b->attempts - (int) $a->attempts;
			}
		);
		return array_slice( $rows, 0, 5 );
	}

	private static function top_users( $hours ) {
		$rows = SecurityAnalyzer::get_failed_logins_by_username( $hours );
		usort(
			$rows,
			function ( $a, $b ) {
				return (int) $b->attempts - (int) $a->attempts;
			}
		);
		return array_slice( $rows, 0, 5 );
	}

	private static function trend( $hours ) {
		$rows        = LogQuery::get_security_trend( $hours );
		$bucket_size = $hours <= 24 ? HOUR_IN_SECONDS : DAY_IN_SECONDS;
		$buckets     = array();
		$start       = time() - ( $hours * HOUR_IN_SECONDS );
		$end         = time();

		for ( $timestamp = $start - ( $start % $bucket_size ); $timestamp <= $end; $timestamp += $bucket_size ) {
			$key            = wp_date( $hours <= 24 ? 'Y-m-d H:00:00' : 'Y-m-d 00:00:00', $timestamp );
			$buckets[ $key ] = 0;
		}

		foreach ( $rows as $key => $total ) {
			if ( isset( $buckets[ $key ] ) ) {
				$buckets[ $key ] = (int) $total;
			}
		}

		return $buckets;
	}
}
