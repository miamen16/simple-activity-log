<?php
/**
 * Vars available: $window, $summary, $by_ip, $by_username, $threshold,
 * $security_events, $security_summary
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$window_labels = array(
	24  => __( 'Last 24 hours', 'simple-activity-log' ),
	168 => __( 'Last 7 days', 'simple-activity-log' ),
	720 => __( 'Last 30 days', 'simple-activity-log' ),
);
?>
<div class="wrap sal-wrap">
	<h1><?php esc_html_e( 'Security', 'simple-activity-log' ); ?></h1>
	<p class="description">
		<?php
		printf(
			/* translators: %d: number of attempts */
			esc_html__( 'Rows with %d or more failed attempts in the selected window are flagged as suspicious.', 'simple-activity-log' ),
			(int) $threshold
		);
		?>
	</p>

	<div class="sal-window-tabs">
		<?php foreach ( $window_labels as $hours => $label ) : ?>
			<a
				href="<?php echo esc_url( add_query_arg( array( 'page' => 'sal-security', 'sal_window' => $hours ), admin_url( 'admin.php' ) ) ); ?>"
				class="button <?php echo $window === $hours ? 'button-primary' : ''; ?>"
			>
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<div class="sal-security-summary">
		<div class="sal-summary-card">
			<span class="sal-summary-number"><?php echo esc_html( $summary['total_attempts'] ); ?></span>
			<span class="sal-summary-label"><?php esc_html_e( 'Failed attempts', 'simple-activity-log' ); ?></span>
		</div>
		<div class="sal-summary-card">
			<span class="sal-summary-number"><?php echo esc_html( $summary['distinct_ips'] ); ?></span>
			<span class="sal-summary-label"><?php esc_html_e( 'Distinct IPs', 'simple-activity-log' ); ?></span>
		</div>
		<div class="sal-summary-card">
			<span class="sal-summary-number"><?php echo esc_html( $summary['distinct_usernames'] ); ?></span>
			<span class="sal-summary-label"><?php esc_html_e( 'Usernames targeted', 'simple-activity-log' ); ?></span>
		</div>
	</div>

	<h2><?php esc_html_e( 'Detected security events', 'simple-activity-log' ); ?></h2>
	<div class="sal-security-summary">
		<?php foreach ( array( 'critical', 'high', 'medium', 'low' ) as $level ) : ?>
			<div class="sal-summary-card">
				<span class="sal-summary-number"><?php echo esc_html( $security_summary[ $level ] ); ?></span>
				<span class="sal-summary-label"><?php echo esc_html( ucfirst( $level ) ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( empty( $security_events ) ) : ?>
		<p><?php esc_html_e( 'No detected security events in this window.', 'simple-activity-log' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Detected', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Risk', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Score', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Username', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'IP Address', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Signals', 'simple-activity-log' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $security_events as $event ) : ?>
					<?php
					$meta     = json_decode( (string) $event->meta, true );
					$meta     = is_array( $meta ) ? $meta : array();
					$level    = isset( $meta['risk_level'] ) ? sanitize_key( $meta['risk_level'] ) : 'low';
					$score    = isset( $meta['risk_score'] ) ? absint( $meta['risk_score'] ) : 0;
					$signals  = isset( $meta['signals'] ) && is_array( $meta['signals'] ) ? $meta['signals'] : array();
					$username = ! empty( $meta['username'] ) ? sanitize_user( $meta['username'] ) : $event->username;
					?>
					<tr class="sal-row-<?php echo esc_attr( $level ); ?>">
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $event->created_at ) ) ); ?></td>
						<td><span class="sal-badge sal-badge-<?php echo esc_attr( $level ); ?>"><?php echo esc_html( ucfirst( $level ) ); ?></span></td>
						<td><strong><?php echo esc_html( $score ); ?>/100</strong></td>
						<td><?php echo esc_html( $username ?: __( 'Unknown', 'simple-activity-log' ) ); ?></td>
						<td><?php echo esc_html( $event->ip_address ?: '—' ); ?></td>
						<td><?php echo esc_html( implode( ', ', array_map( 'sanitize_key', $signals ) ) ?: __( 'Risk threshold exceeded', 'simple-activity-log' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e( 'By IP address', 'simple-activity-log' ); ?></h2>
	<p class="description"><?php esc_html_e( 'One IP trying many different usernames looks like brute-force scanning.', 'simple-activity-log' ); ?></p>
	<?php if ( empty( $by_ip ) ) : ?>
		<p><?php esc_html_e( 'No failed login attempts in this window.', 'simple-activity-log' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'IP Address', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Attempts', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Usernames Tried', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Last Attempt', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Status', 'simple-activity-log' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $by_ip as $row ) : ?>
					<?php $suspicious = \SAL\Core\SecurityAnalyzer::is_suspicious( $row->attempts ); ?>
					<tr class="<?php echo $suspicious ? 'sal-row-suspicious' : ''; ?>">
						<td><?php echo esc_html( $row->ip_address ); ?></td>
						<td><?php echo esc_html( $row->attempts ); ?> <span class="description">(<?php echo esc_html( $row->distinct_usernames ); ?> <?php esc_html_e( 'unique', 'simple-activity-log' ); ?>)</span></td>
						<td><?php echo esc_html( $row->usernames_tried ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->last_attempt ) ) ); ?></td>
						<td>
							<?php if ( $suspicious ) : ?>
								<span class="sal-badge sal-badge-login_failed"><?php esc_html_e( 'Suspicious', 'simple-activity-log' ); ?></span>
							<?php else : ?>
								<span class="description"><?php esc_html_e( 'Normal', 'simple-activity-log' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e( 'By username', 'simple-activity-log' ); ?></h2>
	<p class="description"><?php esc_html_e( 'One username getting hit from many different IPs looks like a targeted attack on that account.', 'simple-activity-log' ); ?></p>
	<?php if ( empty( $by_username ) ) : ?>
		<p><?php esc_html_e( 'No failed login attempts in this window.', 'simple-activity-log' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Username', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Attempts', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Distinct IPs', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Last Attempt', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Status', 'simple-activity-log' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $by_username as $row ) : ?>
					<?php $suspicious = \SAL\Core\SecurityAnalyzer::is_suspicious( $row->attempts ); ?>
					<tr class="<?php echo $suspicious ? 'sal-row-suspicious' : ''; ?>">
						<td><?php echo esc_html( $row->username ); ?></td>
						<td><?php echo esc_html( $row->attempts ); ?></td>
						<td><?php echo esc_html( $row->distinct_ips ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->last_attempt ) ) ); ?></td>
						<td>
							<?php if ( $suspicious ) : ?>
								<span class="sal-badge sal-badge-login_failed"><?php esc_html_e( 'Suspicious', 'simple-activity-log' ); ?></span>
							<?php else : ?>
								<span class="description"><?php esc_html_e( 'Normal', 'simple-activity-log' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
