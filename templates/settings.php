<?php
/**
 * Vars available: $retention_days, $alerts_enabled, $alert_email, $threshold, $alert_settings, $auto_block, $auto_threshold.
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
				<th scope="row"><label for="sal_retention_days"><?php esc_html_e( 'Keep logs for', 'simple-activity-log' ); ?></label></th>
				<td><input type="number" min="0" id="sal_retention_days" name="sal_retention_days" value="<?php echo esc_attr( $retention_days ); ?>" class="small-text" /> <?php esc_html_e( 'days', 'simple-activity-log' ); ?><p class="description"><?php esc_html_e( 'Older entries are deleted automatically once a day. Set to 0 to keep logs forever.', 'simple-activity-log' ); ?></p></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Security Alerts', 'simple-activity-log' ); ?></h2>
		<table class="form-table">
			<tr><th scope="row"><?php esc_html_e( 'Email alerts', 'simple-activity-log' ); ?></th><td><label><input type="checkbox" name="sal_alerts_enabled" value="1" <?php checked( $alerts_enabled ); ?> /> <?php esc_html_e( 'Enable security incident email alerts', 'simple-activity-log' ); ?></label><p class="description"><?php printf( esc_html__( 'Detection threshold: %d failed attempts within an hour.', 'simple-activity-log' ), (int) $threshold ); ?></p></td></tr>
			<tr><th scope="row"><label for="sal_alert_email"><?php esc_html_e( 'Alert email address', 'simple-activity-log' ); ?></label></th><td><input type="email" id="sal_alert_email" name="sal_alert_email" value="<?php echo esc_attr( $alert_email ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" /><p class="description"><?php esc_html_e( 'Leave blank to use the site admin email.', 'simple-activity-log' ); ?></p></td></tr>
			<tr><th scope="row"><label for="sal_alert_min_severity"><?php esc_html_e( 'Minimum severity', 'simple-activity-log' ); ?></label></th><td><select id="sal_alert_min_severity" name="sal_alert_min_severity"><?php foreach ( array( 'medium', 'high', 'critical' ) as $severity ) : ?><option value="<?php echo esc_attr( $severity ); ?>" <?php selected( $alert_settings['min_severity'], $severity ); ?>><?php echo esc_html( ucfirst( $severity ) ); ?></option><?php endforeach; ?></select><p class="description"><?php esc_html_e( 'Only incidents at or above this severity send an email.', 'simple-activity-log' ); ?></p></td></tr>
			<tr><th scope="row"><label for="sal_alert_cooldown_minutes"><?php esc_html_e( 'Alert cooldown', 'simple-activity-log' ); ?></label></th><td><input type="number" min="5" max="1440" id="sal_alert_cooldown_minutes" name="sal_alert_cooldown_minutes" value="<?php echo esc_attr( $alert_settings['cooldown_minutes'] ); ?>" class="small-text" /> <?php esc_html_e( 'minutes', 'simple-activity-log' ); ?><p class="description"><?php esc_html_e( 'Prevents repeated emails for the same incident during an active attack.', 'simple-activity-log' ); ?></p></td></tr>
		</table>

		<h2><?php esc_html_e( 'Automatic IP Blocking', 'simple-activity-log' ); ?></h2>
		<table class="form-table">
			<tr><th scope="row"><?php esc_html_e( 'Auto-block', 'simple-activity-log' ); ?></th><td><label><input type="checkbox" name="sal_auto_block_enabled" value="1" <?php checked( $auto_block ); ?> /> <?php esc_html_e( 'Automatically block an IP after repeated failed logins', 'simple-activity-log' ); ?></label><p class="description"><?php esc_html_e( 'Allowlisted IPs are never automatically blocked. Blocking applies to WordPress authentication requests.', 'simple-activity-log' ); ?></p></td></tr>
			<tr><th scope="row"><label for="sal_auto_block_threshold"><?php esc_html_e( 'Auto-block threshold', 'simple-activity-log' ); ?></label></th><td><input type="number" min="5" max="1000" id="sal_auto_block_threshold" name="sal_auto_block_threshold" value="<?php echo esc_attr( $auto_threshold ); ?>" class="small-text" /> <?php esc_html_e( 'failed attempts / hour', 'simple-activity-log' ); ?><p class="description"><?php esc_html_e( 'Choose a conservative value to reduce the chance of blocking legitimate users.', 'simple-activity-log' ); ?></p></td></tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'simple-activity-log' ) ); ?>
	</form>
</div>
