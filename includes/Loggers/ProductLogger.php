<?php

namespace SAL\Loggers;

use SAL\Core\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logs the WooCommerce product lifecycle: created, edited (while already
 * published), trashed, restored, unpublished, and permanently deleted —
 * plus specific before/after values for the fields that matter most for
 * an e-commerce audit (price, SKU, stock), since "Updated product X"
 * alone doesn't say what actually changed.
 *
 * Uses 'transition_post_status' as the single source of truth for
 * create/edit/trash/restore/unpublish — WordPress fires it on every
 * save regardless of whether the status actually changed, which lets
 * one hook cover both "status changed" and "same-status edit" cases
 * without double-logging against a separate save_post hook.
 * Permanent deletion needs its own hook since transition_post_status
 * doesn't fire when a post is actually removed from the database.
 *
 * Title changes are detected via 'post_updated', which WordPress fires
 * with both the before and after WP_Post objects — stashed in a static
 * cache keyed by post ID so on_transition() (which only has the
 * post-save state) can check whether a rename happened. This works
 * regardless of which hook fires first: if the cache isn't populated
 * yet when on_transition() runs, it just falls back to the generic
 * "Updated product X" message for that one save.
 */
class ProductLogger extends AbstractLogger {

	/** @var array<int, array{old_title:string, new_title:string}> */
	private static $title_diffs = array();

	/**
	 * Product meta fields worth their own before/after log entry.
	 * Filterable so a site can watch more fields without editing this file.
	 */
	private function watched_meta_keys() {
		return apply_filters( 'sal_watched_product_meta', array(
			'_regular_price' => __( 'Regular price', 'simple-activity-log' ),
			'_sale_price'    => __( 'Sale price', 'simple-activity-log' ),
			'_sku'           => __( 'SKU', 'simple-activity-log' ),
			'_stock'         => __( 'Stock quantity', 'simple-activity-log' ),
			'_stock_status'  => __( 'Stock status', 'simple-activity-log' ),
		) );
	}

	public function id() {
		return 'product';
	}

	public function register_hooks() {
		add_action( 'post_updated', array( $this, 'on_post_updated' ), 10, 3 );
		add_action( 'transition_post_status', array( $this, 'on_transition' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'on_permanent_delete' ) );
		add_action( 'update_post_meta', array( $this, 'on_meta_update' ), 10, 4 );
	}

	/**
	 * Stash a title diff (if any) for on_transition() to pick up. Fires
	 * only on updates to an existing post, never on first insert.
	 */
	public function on_post_updated( $post_ID, $post_after, $post_before ) {
		if ( 'product' !== $post_after->post_type ) {
			return;
		}

		if ( $post_before->post_title !== $post_after->post_title ) {
			self::$title_diffs[ $post_ID ] = array(
				'old_title' => $post_before->post_title,
				'new_title' => $post_after->post_title,
			);
		}
	}

	/**
	 * Fires BEFORE the meta value is written, so get_post_meta() here
	 * still returns the old value and $meta_value is the incoming new
	 * one — a reliable before/after pair without a separate lookup.
	 */
	public function on_meta_update( $meta_id, $object_id, $meta_key, $meta_value ) {
		$watched = $this->watched_meta_keys();

		if ( ! isset( $watched[ $meta_key ] ) ) {
			return;
		}

		$post = get_post( $object_id );
		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}

		$old_value = get_post_meta( $object_id, $meta_key, true );

		if ( (string) $old_value === (string) $meta_value ) {
			return; // No real change.
		}

		$title = $post->post_title ? $post->post_title : __( '(no title)', 'simple-activity-log' );
		$old   = '' !== (string) $old_value ? $old_value : __( '(empty)', 'simple-activity-log' );
		$new   = '' !== (string) $meta_value ? $meta_value : __( '(empty)', 'simple-activity-log' );

		Logger::log(
			'product_field_changed',
			sprintf(
				/* translators: 1: field label, 2: product title, 3: old value, 4: new value */
				__( '%1$s of "%2$s" changed from %3$s to %4$s.', 'simple-activity-log' ),
				$watched[ $meta_key ],
				$title,
				$old,
				$new
			),
			array(
				'object_type' => 'product',
				'object_id'   => $object_id,
				'meta'        => array( 'field' => $meta_key, 'old' => $old_value, 'new' => $meta_value ),
			)
		);
	}

	public function on_transition( $new_status, $old_status, $post ) {
		if ( 'product' !== $post->post_type ) {
			return;
		}

		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return;
		}

		// WordPress inserts a throwaway 'auto-draft' placeholder before the
		// real first save — not a meaningful event to log.
		if ( 'new' === $old_status && 'auto-draft' === $new_status ) {
			return;
		}

		$title = $post->post_title ? $post->post_title : __( '(no title)', 'simple-activity-log' );

		if ( $old_status === $new_status ) {
			// Same status, but the save still happened — i.e. an edit.
			if ( 'auto-draft' === $new_status ) {
				return; // Still mid-creation, nothing meaningful saved yet.
			}

			if ( isset( self::$title_diffs[ $post->ID ] ) ) {
				$diff = self::$title_diffs[ $post->ID ];
				unset( self::$title_diffs[ $post->ID ] );

				$this->log_event(
					'product_renamed',
					sprintf(
						/* translators: 1: old title, 2: new title */
						__( 'Renamed product "%1$s" to "%2$s".', 'simple-activity-log' ),
						$diff['old_title'],
						$diff['new_title']
					),
					$post
				);
				return;
			}

			$this->log_event(
				'product_updated',
				sprintf(
					/* translators: %s: product title */
					__( 'Updated product "%s".', 'simple-activity-log' ),
					$title
				),
				$post
			);
			return;
		}

		// Status actually changed.
		if ( 'publish' === $new_status ) {
			$this->log_event(
				'product_created',
				sprintf(
					/* translators: %s: product title */
					__( 'Published product "%s".', 'simple-activity-log' ),
					$title
				),
				$post
			);
		} elseif ( 'trash' === $new_status ) {
			$this->log_event(
				'product_trashed',
				sprintf(
					/* translators: %s: product title */
					__( 'Moved product "%s" to trash.', 'simple-activity-log' ),
					$title
				),
				$post
			);
		} elseif ( 'trash' === $old_status ) {
			$this->log_event(
				'product_restored',
				sprintf(
					/* translators: %s: product title */
					__( 'Restored product "%s" from trash.', 'simple-activity-log' ),
					$title
				),
				$post
			);
		} elseif ( 'publish' === $old_status ) {
			$this->log_event(
				'product_unpublished',
				sprintf(
					/* translators: 1: product title, 2: new status */
					__( 'Unpublished product "%1$s" (set to %2$s).', 'simple-activity-log' ),
					$title,
					$new_status
				),
				$post
			);
		} else {
			$this->log_event(
				'product_status_changed',
				sprintf(
					/* translators: 1: product title, 2: old status, 3: new status */
					__( 'Changed status of product "%1$s" from %2$s to %3$s.', 'simple-activity-log' ),
					$title,
					$old_status,
					$new_status
				),
				$post
			);
		}
	}

	/**
	 * 'before_delete_post' fires for a permanent removal (force-deleted or
	 * deleted from the trash) — a separate event from moving to trash.
	 */
	public function on_permanent_delete( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}

		$title = $post->post_title ? $post->post_title : __( '(no title)', 'simple-activity-log' );

		$this->log_event(
			'product_deleted',
			sprintf(
				/* translators: %s: product title */
				__( 'Permanently deleted product "%s".', 'simple-activity-log' ),
				$title
			),
			$post
		);
	}

	private function log_event( $action, $message, \WP_Post $post ) {
		Logger::log(
			$action,
			$message,
			array(
				'object_type' => 'product',
				'object_id'   => $post->ID,
			)
		);
	}
}
