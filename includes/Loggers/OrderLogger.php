<?php

namespace SAL\Loggers;

use SAL\Core\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logs the WooCommerce order lifecycle: created, status changes (with
 * cancelled/refunded/completed/failed called out as their own action
 * types for easier filtering), trashed, restored, and permanently
 * deleted.
 *
 * Uses WooCommerce's own abstracted hooks (woocommerce_new_order,
 * woocommerce_order_status_changed, woocommerce_trash_order, etc.)
 * rather than post-type hooks, so this works whether the store uses
 * legacy post-based order storage or HPOS (custom order tables) —
 * these are fired by WC_Order/the data store either way.
 */
class OrderLogger extends AbstractLogger {

	/**
	 * Every hook this logger uses is fired by WooCommerce itself, so
	 * without it active these would simply never fire — but checking
	 * explicitly (matching the is_dokan_active() pattern from the Store
	 * Doctor plugin) means Plugin::register_loggers() can skip
	 * registering this logger entirely rather than relying on that.
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Status -> specific action type, for statuses worth their own
	 * filterable category rather than a generic "status changed" entry.
	 */
	private $status_action_map = array(
		'cancelled' => 'order_cancelled',
		'refunded'  => 'order_refunded',
		'completed' => 'order_completed',
		'failed'    => 'order_failed',
	);

	public function id() {
		return 'order';
	}

	public function register_hooks() {
		add_action( 'woocommerce_new_order', array( $this, 'on_new_order' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_status_changed' ), 10, 4 );
		add_action( 'woocommerce_trash_order', array( $this, 'on_trash' ) );
		add_action( 'woocommerce_untrash_order', array( $this, 'on_untrash' ) );
		add_action( 'woocommerce_delete_order', array( $this, 'on_delete' ) );
	}

	public function on_new_order( $order_id, $order = null ) {
		$order = $this->resolve_order( $order_id, $order );
		if ( ! $order ) {
			return;
		}

		$this->log_event(
			'order_created',
			sprintf(
				/* translators: 1: order number, 2: formatted total */
				__( 'New order #%1$s created (%2$s).', 'simple-activity-log' ),
				$order->get_order_number(),
				$this->format_total( $order )
			),
			$order
		);
	}

	public function on_status_changed( $order_id, $old_status, $new_status, $order = null ) {
		$order = $this->resolve_order( $order_id, $order );
		if ( ! $order ) {
			return;
		}

		$action = isset( $this->status_action_map[ $new_status ] )
			? $this->status_action_map[ $new_status ]
			: 'order_status_changed';

		$this->log_event(
			$action,
			sprintf(
				/* translators: 1: order number, 2: old status, 3: new status */
				__( 'Order #%1$s status changed from %2$s to %3$s.', 'simple-activity-log' ),
				$order->get_order_number(),
				$old_status,
				$new_status
			),
			$order,
			array( 'old_status' => $old_status, 'new_status' => $new_status )
		);
	}

	public function on_trash( $order_id ) {
		$order = $this->resolve_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$this->log_event(
			'order_trashed',
			sprintf(
				/* translators: %s: order number */
				__( 'Order #%s moved to trash.', 'simple-activity-log' ),
				$order->get_order_number()
			),
			$order
		);
	}

	public function on_untrash( $order_id ) {
		$order = $this->resolve_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$this->log_event(
			'order_restored',
			sprintf(
				/* translators: %s: order number */
				__( 'Order #%s restored from trash.', 'simple-activity-log' ),
				$order->get_order_number()
			),
			$order
		);
	}

	/**
	 * Fires on permanent deletion. The order may already be gone from the
	 * data store by this point depending on WooCommerce version/storage
	 * mode, so we log with whatever we can still resolve — falling back
	 * to just the ID if the order object is no longer available.
	 */
	public function on_delete( $order_id ) {
		$order = $this->resolve_order( $order_id );

		$label = $order ? '#' . $order->get_order_number() : '#' . $order_id;

		Logger::log(
			'order_deleted',
			sprintf(
				/* translators: %s: order number or ID */
				__( 'Order %s permanently deleted.', 'simple-activity-log' ),
				$label
			),
			array(
				'object_type' => 'order',
				'object_id'   => $order_id,
			)
		);
	}

	private function resolve_order( $order_id, $maybe_order = null ) {
		if ( $maybe_order instanceof \WC_Order ) {
			return $maybe_order;
		}

		$order = wc_get_order( $order_id );
		return $order instanceof \WC_Order ? $order : null;
	}

	private function format_total( \WC_Order $order ) {
		// get_formatted_order_total() returns HTML (currency styling) —
		// strip tags so the log stays plain text.
		return wp_strip_all_tags( $order->get_formatted_order_total() );
	}

	private function log_event( $action, $message, \WC_Order $order, $extra_meta = null ) {
		Logger::log(
			$action,
			$message,
			array(
				'object_type' => 'order',
				'object_id'   => $order->get_id(),
				'meta'        => $extra_meta,
			)
		);
	}
}
