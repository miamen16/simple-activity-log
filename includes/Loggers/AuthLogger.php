<?php

namespace SAL\Loggers;

use SAL\Core\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logs authentication activity. Failed logins are also consumed by the
 * security detector, which is responsible for creating incidents and alerts.
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
	 * version fires it without an ID, fall back to the current user.
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
	 * Fires on a failed login attempt. The SecurityDetector listens to the
	 * resulting 'sal_logged' event and handles incident creation and alerts.
	 *
	 * @param string $username Attempted username.
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
	}
}
