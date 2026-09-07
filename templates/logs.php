<?php
/**
 * Vars available: $filters, $logs, $total, $total_pages, $users, $action_types
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$action_labels = array(
	'login'                  => __( 'Login', 'simple-activity-log' ),
	'logout'                 => __( 'Logout', 'simple-activity-log' ),
	'login_failed'           => __( 'Failed Login', 'simple-activity-log' ),
	'product_created'        => __( 'Product Published', 'simple-activity-log' ),
	'product_updated'        => __( 'Product Updated', 'simple-activity-log' ),
	'product_trashed'        => __( 'Product Trashed', 'simple-activity-log' ),
	'product_restored'       => __( 'Product Restored', 'simple-activity-log' ),
	'product_unpublished'    => __( 'Product Unpublished', 'simple-activity-log' ),
	'product_status_changed' => __( 'Product Status Changed', 'simple-activity-log' ),
	'product_deleted'        => __( 'Product Deleted', 'simple-activity-log' ),
	'order_created'          => __( 'Order Created', 'simple-activity-log' ),
	'order_status_changed'   => __( 'Order Status Changed', 'simple-activity-log' ),
	'order_completed'        => __( 'Order Completed', 'simple-activity-log' ),
	'order_cancelled'        => __( 'Order Cancelled', 'simple-activity-log' ),
	'order_refunded'         => __( 'Order Refunded', 'simple-activity-log' ),
	'order_failed'           => __( 'Order Failed', 'simple-activity-log' ),
	'order_trashed'          => __( 'Order Trashed', 'simple-activity-log' ),
	'order_restored'         => __( 'Order Restored', 'simple-activity-log' ),
	'order_deleted'          => __( 'Order Deleted', 'simple-activity-log' ),
	'setting_changed'        => __( 'Setting Changed', 'simple-activity-log' ),
	'plugin_activated'       => __( 'Plugin Activated', 'simple-activity-log' ),
	'plugin_deactivated'     => __( 'Plugin Deactivated', 'simple-activity-log' ),
	'plugin_installed'       => __( 'Plugin Installed', 'simple-activity-log' ),
	'plugin_updated'         => __( 'Plugin Updated', 'simple-activity-log' ),
	'plugin_deleted'         => __( 'Plugin Deleted', 'simple-activity-log' ),
	'theme_switched'         => __( 'Theme Switched', 'simple-activity-log' ),
	'theme_installed'        => __( 'Theme Installed', 'simple-activity-log' ),
	'theme_updated'          => __( 'Theme Updated', 'simple-activity-log' ),
	'theme_deleted'          => __( 'Theme Deleted', 'simple-activity-log' ),
	'core_updated'           => __( 'WordPress Core Updated', 'simple-activity-log' ),
	'user_created'           => __( 'User Created', 'simple-activity-log' ),
	'user_deleted'           => __( 'User Deleted', 'simple-activity-log' ),
	'user_role_changed'      => __( 'User Role Changed', 'simple-activity-log' ),
	'user_updated'           => __( 'User Updated', 'simple-activity-log' ),
	'user_password_reset'    => __( 'Password Reset', 'simple-activity-log' ),
	'product_renamed'        => __( 'Product Renamed', 'simple-activity-log' ),
	'product_field_changed'  => __( 'Product Field Changed', 'simple-activity-log' ),
);
?>
<div class="wrap sal-wrap">
	<h1><?php esc_html_e( 'Activity Log', 'simple-activity-log' ); ?></h1>

	<form method="get" class="sal-filters">
		<input type="hidden" name="page" value="sal-logs" />

		<select name="sal_user">
			<option value="0"><?php esc_html_e( 'All users', 'simple-activity-log' ); ?></option>
			<?php foreach ( $users as $u ) : ?>
				<option value="<?php echo esc_attr( $u->user_id ); ?>" <?php selected( $filters['user_id'], $u->user_id ); ?>>
					<?php echo esc_html( $u->username ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<select name="sal_action">
			<option value=""><?php esc_html_e( 'All actions', 'simple-activity-log' ); ?></option>
			<?php foreach ( $action_types as $type ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $filters['action'], $type ); ?>>
					<?php echo esc_html( $action_labels[ $type ] ?? ucwords( str_replace( '_', ' ', $type ) ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label>
			<?php esc_html_e( 'From', 'simple-activity-log' ); ?>
			<input type="date" name="sal_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>" />
		</label>
		<label>
			<?php esc_html_e( 'To', 'simple-activity-log' ); ?>
			<input type="date" name="sal_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>" />
		</label>

		<button type="submit" class="button"><?php esc_html_e( 'Filter', 'simple-activity-log' ); ?></button>
		<?php if ( $filters['user_id'] || $filters['action'] || $filters['date_from'] || $filters['date_to'] ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=sal-logs' ) ); ?>" class="button">
				<?php esc_html_e( 'Clear', 'simple-activity-log' ); ?>
			</a>
		<?php endif; ?>

		<a
			href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sal_export_csv&' . http_build_query( array(
				'sal_user'   => $filters['user_id'],
				'sal_action' => $filters['action'],
				'sal_from'   => $filters['date_from'],
				'sal_to'     => $filters['date_to'],
			) ) ), 'sal_export_csv' ) ); ?>"
			class="button button-secondary"
		>
			<?php esc_html_e( 'Export CSV', 'simple-activity-log' ); ?>
		</a>
	</form>

	<?php if ( empty( $logs ) ) : ?>
		<p><?php esc_html_e( 'No activity recorded yet for this filter.', 'simple-activity-log' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'User', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Action', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Details', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'IP Address', 'simple-activity-log' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) ) ); ?></td>
						<td><?php echo esc_html( $log->username ?: __( 'Guest', 'simple-activity-log' ) ); ?></td>
						<td>
							<span class="sal-badge sal-badge-<?php echo esc_attr( $log->action ); ?>">
								<?php echo esc_html( $action_labels[ $log->action ] ?? ucwords( str_replace( '_', ' ', $log->action ) ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $log->message ); ?></td>
						<td><?php echo esc_html( $log->ip_address ?: '—' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="sal-pagination">
				<?php
				echo wp_kses_post( paginate_links( array(
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'current'   => $filters['page'],
					'total'     => $total_pages,
					'prev_text' => __( '&laquo; Previous', 'simple-activity-log' ),
					'next_text' => __( 'Next &raquo;', 'simple-activity-log' ),
				) ) );
				?>
			</div>
		<?php endif; ?>

		<p class="description">
			<?php
			printf(
				/* translators: %d: total number of matching log entries */
				esc_html__( '%d total entries for this filter.', 'simple-activity-log' ),
				(int) $total
			);
			?>
		</p>
	<?php endif; ?>
</div>
