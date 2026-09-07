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

		return array(
			'window'          => $hours,
			'failed_attempts' => $summary['total_attempts'],
			'distinct_ips'    => $summary['distinct_ips'],
			'targeted_users'  => $summary['distinct_usernames'],
			'incidents'       => self::incident_counts(),
			'top_ips'         => self::top_ips( $hours ),
			'top_users'       => self::top_users( $hours ),
			'trend'           => self::trend( $hours ),
		);
	}

	public static function incident_counts() {
		$rows   = IncidentManager::all( '', 200 );
		$counts = array( 'open' => 0, 'investigating' => 0, 'resolved' => 0, 'ignored' => 0, 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0 );
		foreach ( $rows as $row ) {
			if ( isset( $counts[ $row->status ] ) ) {
				$counts[ $row->status ]++;
			}
			if ( isset( $counts[ $row->severity ] ) ) {
				$counts[ $row->severity ]++;
			}
		}
		return $counts;
	}

	private static function top_ips( $hours ) {
		$rows = SecurityAnalyzer::get_failed_logins_by_ip( $hours );
		usort( $rows, function ( $a, $b ) { return (int) $b->attempts - (int) $a->attempts; } );
		return array_slice( $rows, 0, 5 );
	}

	private static function top_users( $hours ) {
		$rows = SecurityAnalyzer::get_failed_logins_by_username( $hours );
		usort( $rows, function ( $a, $b ) { return (int) $b->attempts - (int) $a->attempts; } );
		return array_slice( $rows, 0, 5 );
	}

	private static function trend( $hours ) {
		$events      = LogQuery::get_security_events( $hours, 200 );
		$bucket_size = $hours <= 24 ? HOUR_IN_SECONDS : DAY_IN_SECONDS;
		$buckets     = array();
		$start       = time() - ( $hours * HOUR_IN_SECONDS );
		$end         = time();

		for ( $timestamp = $start - ( $start % $bucket_size ); $timestamp <= $end; $timestamp += $bucket_size ) {
			$buckets[ wp_date( 'Y-m-d H:i', $timestamp ) ] = 0;
		}

		foreach ( $events as $event ) {
			$timestamp = strtotime( $event->created_at );
			if ( false === $timestamp ) {
				continue;
			}
			$key = wp_date( 'Y-m-d H:i', $timestamp - ( $timestamp % $bucket_size ) );
			if ( isset( $buckets[ $key ] ) ) {
				$buckets[ $key ]++;
			}
		}

		return $buckets;
	}
}
