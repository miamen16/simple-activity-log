<?php
/**
 * Vars available: $retention_days, $alerts_enabled, $alert_email, $threshold
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sal-wrap">
	<h1><?php esc_html_e( 'Activity Log Settings', 'simple-activity-log' ); ?></h1>

	<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'simple-activity-log' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sal-settings-form">
		<input type="hidden" name="action" value="sal_save_settings" />
		<?php wp_nonce_field( 'sal_save_settings' ); ?>

		<h2><?php esc_html_e( 'Log Retention', 'simple-activity-log' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="sal_retention_days"><?php esc_html_e( 'Keep logs for', 'simple-activity-log' ); ?></label>
				</th>
				<td>
					<input type="number" min="0" id="sal_retention_days" name="sal_retention_days" value="<?php echo esc_attr( $retention_days ); ?>" class="small-text" />
					<?php esc_html_e( 'days', 'simple-activity-log' ); ?>
					<p class="description">
						<?php esc_html_e( 'Older entries are deleted automatically once a day. Set to 0 to keep logs forever (not recommended long-term — the table will keep growing).', 'simple-activity-log' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Security Alerts', 'simple-activity-log' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Email alerts', 'simple-activity-log' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sal_alerts_enabled" value="1" <?php checked( $alerts_enabled ); ?> />
						<?php esc_html_e( 'Email me when failed login attempts cross the suspicious threshold', 'simple-activity-log' ); ?>
					</label>
					<p class="description">
						<?php
						printf(
							/* translators: %d: threshold count */
							esc_html__( 'Current threshold: %d attempts within an hour, from one IP or against one username. Change it with the sal_suspicious_login_threshold filter.', 'simple-activity-log' ),
							(int) $threshold
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="sal_alert_email"><?php esc_html_e( 'Alert email address', 'simple-activity-log' ); ?></label>
				</th>
				<td>
					<input type="email" id="sal_alert_email" name="sal_alert_email" value="<?php echo esc_attr( $alert_email ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Leave blank to use the site admin email.', 'simple-activity-log' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'simple-activity-log' ) ); ?>
	</form>
</div>
