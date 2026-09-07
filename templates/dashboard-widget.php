<?php
/**
 * Vars available: $logs, $failed_today, $suspicious_ips
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( $failed_today > 0 ) : ?>
	<p class="sal-widget-alert <?php echo ! empty( $suspicious_ips ) ? 'sal-widget-alert-danger' : ''; ?>">
		<?php
		printf(
			/* translators: %d: number of failed login attempts */
			esc_html__( '%d failed login attempt(s) in the last 24 hours.', 'simple-activity-log' ),
			(int) $failed_today
		);
		?>
		<?php if ( ! empty( $suspicious_ips ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=sal-security' ) ); ?>">
				<?php esc_html_e( 'Review Security page →', 'simple-activity-log' ); ?>
			</a>
		<?php endif; ?>
	</p>
<?php endif; ?>

<?php if ( empty( $logs ) ) : ?>
	<p><?php esc_html_e( 'No activity recorded yet.', 'simple-activity-log' ); ?></p>
<?php else : ?>
	<ul class="sal-widget-list">
		<?php foreach ( $logs as $log ) : ?>
			<li>
				<span class="sal-widget-time"><?php echo esc_html( human_time_diff( strtotime( $log->created_at ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'ago', 'simple-activity-log' ); ?></span>
				<span class="sal-widget-message"><?php echo esc_html( $log->message ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<p class="sal-widget-footer">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sal-logs' ) ); ?>"><?php esc_html_e( 'View full activity log →', 'simple-activity-log' ); ?></a>
	</p>
<?php endif; ?>
