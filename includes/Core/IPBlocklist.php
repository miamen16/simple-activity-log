<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages IP allowlisting and blocking for authentication requests.
 */
class IPBlocklist {

	const OPTION_BLOCKLIST = 'sal_ip_blocklist';
	const OPTION_ALLOWLIST = 'sal_ip_allowlist';
	const OPTION_AUTO_BLOCK = 'sal_auto_block_enabled';
	const OPTION_AUTO_THRESHOLD = 'sal_auto_block_threshold';

	public static function is_blocked( $ip ) {
		$ip = self::normalize_ip( $ip );
		if ( ! $ip || self::is_allowed( $ip ) ) {
			return false;
		}

		return in_array( $ip, self::get_list( self::OPTION_BLOCKLIST ), true );
	}

	public static function is_allowed( $ip ) {
		$ip = self::normalize_ip( $ip );
		return $ip && in_array( $ip, self::get_list( self::OPTION_ALLOWLIST ), true );
	}

	public static function block( $ip, $reason = '' ) {
		$ip = self::normalize_ip( $ip );
		if ( ! $ip || self::is_allowed( $ip ) ) {
			return false;
		}

		$list = self::get_list( self::OPTION_BLOCKLIST );
		if ( ! in_array( $ip, $list, true ) ) {
			$list[] = $ip;
			self::save_list( self::OPTION_BLOCKLIST, $list );
		}

		/* translators: %s: IP address. */
		Logger::log( 'security_ip_blocked', sprintf( __( 'IP address %s was blocked.', 'simple-activity-log' ), $ip ), array( 'ip_address' => $ip, 'object_type' => 'security', 'meta' => array( 'reason' => sanitize_text_field( $reason ), 'source' => 'manual' ) ) );
		return true;
	}

	public static function unblock( $ip ) {
		$ip = self::normalize_ip( $ip );
		if ( ! $ip ) {
			return false;
		}

		$list = self::get_list( self::OPTION_BLOCKLIST );
		$new  = array_values( array_diff( $list, array( $ip ) ) );
		if ( count( $new ) === count( $list ) ) {
			return false;
		}

		self::save_list( self::OPTION_BLOCKLIST, $new );
		/* translators: %s: IP address. */
		Logger::log( 'security_ip_unblocked', sprintf( __( 'IP address %s was unblocked.', 'simple-activity-log' ), $ip ), array( 'ip_address' => $ip, 'object_type' => 'security' ) );
		return true;
	}

	public static function allow( $ip ) {
		$ip = self::normalize_ip( $ip );
		if ( ! $ip ) {
			return false;
		}
		$list = self::get_list( self::OPTION_ALLOWLIST );
		if ( ! in_array( $ip, $list, true ) ) {
			$list[] = $ip;
			self::save_list( self::OPTION_ALLOWLIST, $list );
		}
		self::unblock( $ip );
		return true;
	}

	public static function remove_allow( $ip ) {
		$ip = self::normalize_ip( $ip );
		if ( ! $ip ) {
			return false;
		}
		$list = self::get_list( self::OPTION_ALLOWLIST );
		$new  = array_values( array_diff( $list, array( $ip ) ) );
		if ( count( $new ) === count( $list ) ) {
			return false;
		}
		self::save_list( self::OPTION_ALLOWLIST, $new );
		return true;
	}

	public static function maybe_auto_block( $ip, $attempts ) {
		if ( ! get_option( self::OPTION_AUTO_BLOCK, '0' ) || self::is_allowed( $ip ) ) {
			return false;
		}
		$threshold = max( 1, min( 1000, (int) get_option( self::OPTION_AUTO_THRESHOLD, 20 ) ) );
		if ( (int) $attempts < $threshold || self::is_blocked( $ip ) ) {
			return false;
		}
		$blocked = self::block( $ip, 'automatic threshold' );
		if ( $blocked ) {
			/* translators: 1: IP address, 2: number of failed login attempts. */
			Logger::log( 'security_auto_block', sprintf( __( 'IP address %1$s was automatically blocked after %2$d failed login attempts.', 'simple-activity-log' ), $ip, (int) $attempts ), array( 'ip_address' => $ip, 'object_type' => 'security', 'meta' => array( 'attempts' => (int) $attempts, 'threshold' => $threshold, 'source' => 'automatic' ) ) );
		}
		return $blocked;
	}

	public static function get_blocklist() { return self::get_list( self::OPTION_BLOCKLIST ); }
	public static function get_allowlist() { return self::get_list( self::OPTION_ALLOWLIST ); }
	public static function auto_block_enabled() { return '1' === get_option( self::OPTION_AUTO_BLOCK, '0' ); }
	public static function auto_block_threshold() { return max( 1, min( 1000, (int) get_option( self::OPTION_AUTO_THRESHOLD, 20 ) ) ); }

	private static function get_list( $option ) {
		$value = get_option( $option, array() );
		return is_array( $value ) ? array_values( array_filter( array_map( array( __CLASS__, 'normalize_ip' ), $value ) ) ) : array();
	}

	private static function save_list( $option, $list ) {
		update_option( $option, array_values( array_unique( $list ) ), false );
	}

	private static function normalize_ip( $ip ) {
		$ip = trim( sanitize_text_field( (string) $ip ) );
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}
}
