<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central registry for activity event definitions.
 *
 * Event definitions provide a stable vocabulary for loggers, security rules,
 * alerts, filters, and future integrations.
 */
class EventRegistry {

	/**
	 * Get all built-in event definitions.
	 *
	 * @return array
	 */
	public static function all() {
		$events = array(
			'auth.login' => array(
				'label'    => __( 'Login', 'simple-activity-log' ),
				'category' => 'authentication',
				'risk'     => 10,
			),
			'auth.logout' => array(
				'label'    => __( 'Logout', 'simple-activity-log' ),
				'category' => 'authentication',
				'risk'     => 0,
			),
			'auth.login_failed' => array(
				'label'    => __( 'Failed login', 'simple-activity-log' ),
				'category' => 'authentication',
				'risk'     => 30,
			),
			'user.created' => array(
				'label'    => __( 'User created', 'simple-activity-log' ),
				'category' => 'users',
				'risk'     => 20,
			),
			'user.deleted' => array(
				'label'    => __( 'User deleted', 'simple-activity-log' ),
				'category' => 'users',
				'risk'     => 60,
			),
			'user.role_changed' => array(
				'label'    => __( 'User role changed', 'simple-activity-log' ),
				'category' => 'users',
				'risk'     => 70,
			),
			'post.created' => array(
				'label'    => __( 'Content created', 'simple-activity-log' ),
				'category' => 'content',
				'risk'     => 5,
			),
			'post.updated' => array(
				'label'    => __( 'Content updated', 'simple-activity-log' ),
				'category' => 'content',
				'risk'     => 10,
			),
			'post.deleted' => array(
				'label'    => __( 'Content deleted', 'simple-activity-log' ),
				'category' => 'content',
				'risk'     => 35,
			),
			'plugin.activated' => array(
				'label'    => __( 'Plugin activated', 'simple-activity-log' ),
				'category' => 'system',
				'risk'     => 30,
			),
			'plugin.deactivated' => array(
				'label'    => __( 'Plugin deactivated', 'simple-activity-log' ),
				'category' => 'system',
				'risk'     => 50,
			),
			'option.updated' => array(
				'label'    => __( 'Setting updated', 'simple-activity-log' ),
				'category' => 'settings',
				'risk'     => 20,
			),
			'woocommerce.order_created' => array(
				'label'    => __( 'Order created', 'simple-activity-log' ),
				'category' => 'woocommerce',
				'risk'     => 5,
			),
			'woocommerce.order_status_changed' => array(
				'label'    => __( 'Order status changed', 'simple-activity-log' ),
				'category' => 'woocommerce',
				'risk'     => 25,
			),
			'woocommerce.refund_created' => array(
				'label'    => __( 'Refund created', 'simple-activity-log' ),
				'category' => 'woocommerce',
				'risk'     => 40,
			),
			'security.suspicious_activity' => array(
				'label'    => __( 'Suspicious activity', 'simple-activity-log' ),
				'category' => 'security',
				'risk'     => 80,
			),
		);

		return apply_filters( 'sal_event_registry', $events );
	}

	/**
	 * Determine whether an event is registered.
	 *
	 * @param string $action Event action.
	 * @return bool
	 */
	public static function exists( $action ) {
		return isset( self::all()[ $action ] );
	}

	/**
	 * Get a single event definition.
	 *
	 * @param string $action Event action.
	 * @return array|null
	 */
	public static function get( $action ) {
		$events = self::all();
		return isset( $events[ $action ] ) ? $events[ $action ] : null;
	}

	/**
	 * Get the base risk score for an event.
	 *
	 * @param string $action Event action.
	 * @return int
	 */
	public static function risk( $action ) {
		$event = self::get( $action );
		return $event ? (int) $event['risk'] : 0;
	}
}
