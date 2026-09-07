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
			'login' => array(
				'label' => __( 'Login', 'simple-activity-log' ), 'category' => 'authentication', 'risk' => 10,
			),
			'logout' => array(
				'label' => __( 'Logout', 'simple-activity-log' ), 'category' => 'authentication', 'risk' => 0,
			),
			'login_failed' => array(
				'label' => __( 'Failed login', 'simple-activity-log' ), 'category' => 'authentication', 'risk' => 30,
			),
			'user_created' => array(
				'label' => __( 'User created', 'simple-activity-log' ), 'category' => 'users', 'risk' => 20,
			),
			'user_deleted' => array(
				'label' => __( 'User deleted', 'simple-activity-log' ), 'category' => 'users', 'risk' => 60,
			),
			'user_role_changed' => array(
				'label' => __( 'User role changed', 'simple-activity-log' ), 'category' => 'users', 'risk' => 70,
			),
			'post_created' => array(
				'label' => __( 'Content created', 'simple-activity-log' ), 'category' => 'content', 'risk' => 5,
			),
			'post_updated' => array(
				'label' => __( 'Content updated', 'simple-activity-log' ), 'category' => 'content', 'risk' => 10,
			),
			'post_deleted' => array(
				'label' => __( 'Content deleted', 'simple-activity-log' ), 'category' => 'content', 'risk' => 35,
			),
			'plugin_activated' => array(
				'label' => __( 'Plugin activated', 'simple-activity-log' ), 'category' => 'system', 'risk' => 30,
			),
			'plugin_deactivated' => array(
				'label' => __( 'Plugin deactivated', 'simple-activity-log' ), 'category' => 'system', 'risk' => 50,
			),
			'option_updated' => array(
				'label' => __( 'Setting updated', 'simple-activity-log' ), 'category' => 'settings', 'risk' => 20,
			),
			'order_created' => array(
				'label' => __( 'Order created', 'simple-activity-log' ), 'category' => 'woocommerce', 'risk' => 5,
			),
			'order_status_changed' => array(
				'label' => __( 'Order status changed', 'simple-activity-log' ), 'category' => 'woocommerce', 'risk' => 25,
			),
			'order_cancelled' => array(
				'label' => __( 'Order cancelled', 'simple-activity-log' ), 'category' => 'woocommerce', 'risk' => 40,
			),
			'order_refunded' => array(
				'label' => __( 'Order refunded', 'simple-activity-log' ), 'category' => 'woocommerce', 'risk' => 50,
			),
			'order_completed' => array(
				'label' => __( 'Order completed', 'simple-activity-log' ), 'category' => 'woocommerce', 'risk' => 10,
			),
			'order_failed' => array(
				'label' => __( 'Order failed', 'simple-activity-log' ), 'category' => 'woocommerce', 'risk' => 35,
			),
			'order_payment_completed' => array(
				'label' => __( 'Order payment completed', 'simple-activity-log' ), 'category' => 'woocommerce', 'risk' => 5,
			),
			'order_trashed' => array(
				'label' => __( 'Order trashed', 'simple-activity-log' ), 'category' => 'woocommerce', 'risk' => 35,
			),
			'order_restored' => array(
				'label' => __( 'Order restored', 'simple-activity-log' ), 'category' => 'woocommerce', 'risk' => 20,
			),
			'order_deleted' => array(
				'label' => __( 'Order deleted', 'simple-activity-log' ), 'category' => 'woocommerce', 'risk' => 60,
			),
			'suspicious_activity' => array(
				'label' => __( 'Suspicious activity', 'simple-activity-log' ), 'category' => 'security', 'risk' => 80,
			),
		);

		return apply_filters( 'sal_event_registry', $events );
	}

	public static function exists( $action ) {
		return isset( self::all()[ $action ] );
	}

	public static function get( $action ) {
		$events = self::all();
		return isset( $events[ $action ] ) ? $events[ $action ] : null;
	}

	public static function risk( $action ) {
		$event = self::get( $action );
		return $event ? (int) $event['risk'] : 0;
	}
}
