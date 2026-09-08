<?php
/** @var array $data */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$counts = $data['incidents'];
$labels = array(
	24  => __( '24 Hours', 'simple-activity-log' ),
	168 => __( '7 Days', 'simple-activity-log' ),
	720 => __( '30 Days', 'simple-activity-log' ),
);
?>
<div class="wrap sal-wrap">
	<h1><?php esc_html_e( 'Security Dashboard', 'simple-activity-log' ); ?></h1>

	<div class="sal-window-tabs" aria-label="<?php esc_attr_e( 'Dashboard time range', 'simple-activity-log' ); ?>">
		<?php foreach ( $labels as $hours => $label ) : ?>
			<a class="button <?php echo $data['window'] === $hours ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'sal-security-dashboard', 'sal_window' => $hours ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>

	<div class="sal-security-summary">
		<div class="sal-summary-card sal-summary-critical"><span class="sal-summary-number"><?php echo esc_html( $counts['critical'] ); ?></span><span class="sal-summary-label"><?php esc_html_e( 'Critical incidents', 'simple-activity-log' ); ?></span></div>
		<div class="sal-summary-card sal-summary-high"><span class="sal-summary-number"><?php echo esc_html( $counts['high'] ); ?></span><span class="sal-summary-label"><?php esc_html_e( 'High severity', 'simple-activity-log' ); ?></span></div>
		<div class="sal-summary-card"><span class="sal-summary-number"><?php echo esc_html( $counts['open'] + $counts['investigating'] ); ?></span><span class="sal-summary-label"><?php esc_html_e( 'Active incidents', 'simple-activity-log' ); ?></span></div>
		<div class="sal-summary-card"><span class="sal-summary-number"><?php echo esc_html( $data['failed_attempts'] ); ?></span><span class="sal-summary-label"><?php esc_html_e( 'Failed logins', 'simple-activity-log' ); ?></span></div>
		<div class="sal-summary-card"><span class="sal-summary-number"><?php echo esc_html( $data['distinct_ips'] ); ?></span><span class="sal-summary-label"><?php esc_html_e( 'Attacking IPs', 'simple-activity-log' ); ?></span></div>
		<div class="sal-summary-card"><span class="sal-summary-number"><?php echo esc_html( $data['targeted_users'] ); ?></span><span class="sal-summary-label"><?php esc_html_e( 'Targeted usernames', 'simple-activity-log' ); ?></span></div>
	</div>

	<div class="sal-dashboard-grid">
		<div class="sal-dashboard-card sal-dashboard-card-wide">
			<div class="sal-dashboard-card-header">
				<h2><?php esc_html_e( 'Security activity trend', 'simple-activity-log' ); ?></h2>
				<span class="description">
					<?php
					/* translators: %d: number of detected security events */
					echo esc_html( sprintf( __( '%d detected events', 'simple-activity-log' ), $counts['total'] ) );
					?>
				</span>
			</div>
			<div class="sal-trend-list">
				<?php foreach ( $data['trend'] as $period => $total ) : ?>
					<?php $max = max( 1, max( $data['trend'] ) ); ?>
					<div class="sal-trend-row">
						<span class="sal-trend-label"><?php echo esc_html( $period ); ?></span>
						<div class="sal-trend-bar"><span style="width: <?php echo esc_attr( ( $total / $max ) * 100 ); ?>%;"></span></div>
						<strong><?php echo esc_html( $total ); ?></strong>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="sal-dashboard-card">
			<h2><?php esc_html_e( 'Top attacking IPs', 'simple-activity-log' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'IP', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Attempts', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Users', 'simple-activity-log' ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $data['top_ips'] ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'No failed login activity found.', 'simple-activity-log' ); ?></td></tr><?php endif; ?>
				<?php foreach ( $data['top_ips'] as $row ) : ?><tr><td><code><?php echo esc_html( $row->ip_address ); ?></code></td><td><?php echo esc_html( $row->attempts ); ?></td><td><?php echo esc_html( $row->distinct_usernames ); ?></td></tr><?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="sal-dashboard-card">
			<h2><?php esc_html_e( 'Most targeted usernames', 'simple-activity-log' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Username', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Attempts', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'IPs', 'simple-activity-log' ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $data['top_users'] ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'No targeted accounts found.', 'simple-activity-log' ); ?></td></tr><?php endif; ?>
				<?php foreach ( $data['top_users'] as $row ) : ?><tr><td><strong><?php echo esc_html( $row->username ); ?></strong></td><td><?php echo esc_html( $row->attempts ); ?></td><td><?php echo esc_html( $row->distinct_ips ); ?></td></tr><?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="sal-dashboard-card sal-dashboard-card-status">
			<h2><?php esc_html_e( 'Incident status', 'simple-activity-log' ); ?></h2>
			<div class="sal-status-metrics">
				<div><strong><?php echo esc_html( $counts['open'] ); ?></strong><span><?php esc_html_e( 'Open', 'simple-activity-log' ); ?></span></div>
				<div><strong><?php echo esc_html( $counts['investigating'] ); ?></strong><span><?php esc_html_e( 'Investigating', 'simple-activity-log' ); ?></span></div>
				<div><strong><?php echo esc_html( $counts['resolved'] ); ?></strong><span><?php esc_html_e( 'Resolved', 'simple-activity-log' ); ?></span></div>
				<div><strong><?php echo esc_html( $counts['ignored'] ); ?></strong><span><?php esc_html_e( 'Ignored', 'simple-activity-log' ); ?></span></div>
			</div>
		</div>
	</div>

	<p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=sal-incidents' ) ); ?>"><?php esc_html_e( 'Open Incident Center', 'simple-activity-log' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sal-security' ) ); ?>"><?php esc_html_e( 'View Security Analysis', 'simple-activity-log' ); ?></a>
	</p>
</div>
