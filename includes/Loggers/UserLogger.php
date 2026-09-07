<?php

namespace SAL\Loggers;

use SAL\Core\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logs user/role management — the obvious gap on any site with more
 * than one admin: who created an account, who got promoted to admin,
 * whose password was reset, who got deleted.
 *
 * Note: 'set_user_role' and 'profile_update' can both fire for a single
 * role change made through the user-edit screen (WordPress calls
 * set_user_role() internally as part of a broader profile save). That
 * means a role change may produce two log lines — a specific "role
 * changed" entry and a generic "profile updated" entry — rather than
 * being perfectly de-duplicated. Left as-is: both lines are accurate on
 * their own, and this mirrors the same acceptable minor overlap noted
 * for OrderLogger.
 */
class UserLogger extends AbstractLogger {

	public function id() {
		return 'user';
	}

	public function register_hooks() {
		add_action( 'user_register', array( $this, 'on_user_created' ) );
		add_action( 'delete_user', array( $this, 'on_user_deleted' ), 10, 3 );
		add_action( 'set_user_role', array( $this, 'on_role_changed' ), 10, 3 );
		add_action( 'profile_update', array( $this, 'on_profile_updated' ), 10, 2 );
		add_action( 'password_reset', array( $this, 'on_password_reset' ), 10, 2 );
	}

	public function on_user_created( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		Logger::log(
			'user_created',
			sprintf(
				/* translators: 1: username, 2: role(s) */
				__( 'Created new user "%1$s" (%2$s).', 'simple-activity-log' ),
				$user->user_login,
				implode( ', ', $user->roles )
			),
			array( 'object_type' => 'user', 'object_id' => $user_id )
		);
	}

	/**
	 * $user (the WP_User being removed, captured before deletion) is
	 * only passed since WP 5.5 — fall back to just the ID on older cores.
	 */
	public function on_user_deleted( $id, $reassign, $user = null ) {
		$username = ( $user instanceof \WP_User ) ? $user->user_login : ( '#' . $id );

		Logger::log(
			'user_deleted',
			sprintf(
				/* translators: %s: username */
				__( 'Deleted user "%s".', 'simple-activity-log' ),
				$username
			),
			array( 'object_type' => 'user', 'object_id' => $id )
		);
	}

	public function on_role_changed( $user_id, $role, $old_roles ) {
		$user     = get_userdata( $user_id );
		$username = $user ? $user->user_login : ( '#' . $user_id );
		$old      = ! empty( $old_roles ) ? implode( ', ', $old_roles ) : __( 'none', 'simple-activity-log' );

		Logger::log(
			'user_role_changed',
			sprintf(
				/* translators: 1: username, 2: old role(s), 3: new role */
				__( 'Changed role of "%1$s" from %2$s to %3$s.', 'simple-activity-log' ),
				$username,
				$old,
				$role
			),
			array(
				'object_type' => 'user',
				'object_id'   => $user_id,
				'meta'        => array( 'old_roles' => $old_roles, 'new_role' => $role ),
			)
		);
	}

	public function on_profile_updated( $user_id, $old_user_data ) {
		$user     = get_userdata( $user_id );
		$username = $user ? $user->user_login : ( '#' . $user_id );

		Logger::log(
			'user_updated',
			sprintf(
				/* translators: %s: username */
				__( 'Updated profile for user "%s".', 'simple-activity-log' ),
				$username
			),
			array( 'object_type' => 'user', 'object_id' => $user_id )
		);
	}

	/**
	 * Fires after a password reset via the "lost password" flow. Never
	 * logs the password itself, obviously — just that a reset happened.
	 */
	public function on_password_reset( $user, $new_pass ) {
		Logger::log(
			'user_password_reset',
			sprintf(
				/* translators: %s: username */
				__( 'Password reset for user "%s".', 'simple-activity-log' ),
				$user->user_login
			),
			array( 'object_type' => 'user', 'object_id' => $user->ID )
		);
	}
}
