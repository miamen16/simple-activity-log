<?php

namespace SAL\Loggers;

use SAL\Core\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logs the three core auth events: successful login, logout, and failed
 * login attempts (useful both as an activity record and as a lightweight
 * security signal — e.g. spotting repeated failed attempts for one
 * username).
 */
class AuthLogger extends AbstractLogger {

	public function id() {
		return 'auth';
	}

	public function register_hooks() {
		add_action( 'wp_login', array( $this, 'on_login' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'on_logout' ) );
		add_action( 'wp_login_failed', array( $this, 'on_login_failed' ) );
	}

	public function on_login( $user_login, $user ) {
		Logger::log(
			'login',
			sprintf(
				/* translators: %s: username */
				__( '%s logged in.', 'simple-activity-log' ),
				$user_login
			),
			array(
				'user_id'  => $user->ID,
				'username' => $user_login,
			)
		);
	}

	/**
	 * 'wp_logout' has passed the user ID since WP 5.5. If an older WP
	 * version fires it without an ID, fall back to the current user
	 * (still available at this point, before the session is destroyed).
	 */
	public function on_logout( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$user     = $user_id ? get_userdata( $user_id ) : false;
		$username = $user ? $user->user_login : __( 'Unknown user', 'simple-activity-log' );

		Logger::log(
			'logout',
			sprintf(
				/* translators: %s: username */
				__( '%s logged out.', 'simple-activity-log' ),
				$username
			),
			array(
				'user_id'  => $user_id,
				'username' => $user ? $user->user_login : null,
			)
		);
	}

	/**
	 * Fires on a failed login attempt. $username is whatever was typed
	 * into the username field — it may not correspond to a real account
	 * (that's exactly the case worth logging).
	 */
	public function on_login_failed( $username ) {
		Logger::log(
			'login_failed',
			sprintf(
				/* translators: %s: attempted username */
				__( 'Failed login attempt for "%s".', 'simple-activity-log' ),
				$username
			),
			array(
				'user_id'  => 0,
				'username' => $username,
			)
		);

		\SAL\Core\AlertManager::check_and_alert( Logger::get_client_ip(), $username );
	}
}
