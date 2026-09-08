<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sal-wrap">
	<h1><?php esc_html_e( 'IP Blocklist & Allowlist', 'simple-activity-log' ); ?></h1>
	<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'IP security lists updated.', 'simple-activity-log' ); ?></p></div>
	<?php endif; ?>

	<section class="sal-dashboard-card">
		<h2><?php esc_html_e( 'Add IP Rule', 'simple-activity-log' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sal_ip_block_action" />
			<?php wp_nonce_field( 'sal_ip_block_action' ); ?>
			<input type="text" name="ip_address" placeholder="203.0.113.10" required />
			<input type="text" name="reason" placeholder="<?php esc_attr_e( 'Reason (optional)', 'simple-activity-log' ); ?>" />
			<button class="button button-primary" name="ip_action" value="block"><?php esc_html_e( 'Block', 'simple-activity-log' ); ?></button>
			<button class="button" name="ip_action" value="allow"><?php esc_html_e( 'Allow', 'simple-activity-log' ); ?></button>
		</form>
	</section>

	<div class="sal-dashboard-grid">
		<section class="sal-dashboard-card">
			<h2><?php esc_html_e( 'Blocked IPs', 'simple-activity-log' ); ?></h2>
			<?php if ( empty( $blocklist ) ) : ?><p><?php esc_html_e( 'No blocked IPs.', 'simple-activity-log' ); ?></p><?php else : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'IP Address', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Action', 'simple-activity-log' ); ?></th></tr></thead><tbody>
			<?php foreach ( $blocklist as $ip ) : ?><tr><td><code><?php echo esc_html( $ip ); ?></code></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'sal_ip_block_action' ); ?><input type="hidden" name="action" value="sal_ip_block_action" /><input type="hidden" name="ip_address" value="<?php echo esc_attr( $ip ); ?>" /><button class="button" name="ip_action" value="unblock"><?php esc_html_e( 'Unblock', 'simple-activity-log' ); ?></button><button class="button" name="ip_action" value="allow"><?php esc_html_e( 'Move to Allowlist', 'simple-activity-log' ); ?></button></form></td></tr><?php endforeach; ?>
			</tbody></table>
			<?php endif; ?>
		</section>

		<section class="sal-dashboard-card">
			<h2><?php esc_html_e( 'Allowed IPs', 'simple-activity-log' ); ?></h2>
			<?php if ( empty( $allowlist ) ) : ?><p><?php esc_html_e( 'No allowed IPs.', 'simple-activity-log' ); ?></p><?php else : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'IP Address', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Action', 'simple-activity-log' ); ?></th></tr></thead><tbody>
			<?php foreach ( $allowlist as $ip ) : ?><tr><td><code><?php echo esc_html( $ip ); ?></code></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'sal_ip_block_action' ); ?><input type="hidden" name="action" value="sal_ip_block_action" /><input type="hidden" name="ip_address" value="<?php echo esc_attr( $ip ); ?>" /><button class="button" name="ip_action" value="remove_allow"><?php esc_html_e( 'Remove Allow Rule', 'simple-activity-log' ); ?></button></form></td></tr><?php endforeach; ?>
			</tbody></table>
			<?php endif; ?>
		</section>
	</div>
</div>
