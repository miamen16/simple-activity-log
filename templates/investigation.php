<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_ip = ! empty( $report ) && 'ip_address' === $report['type'];
$title = $is_ip ? __( 'IP Investigation', 'simple-activity-log' ) : __( 'User Investigation', 'simple-activity-log' );
?>
<div class="wrap sal-wrap">
	<h1><?php echo esc_html( $title ); ?></h1>

	<form method="get" class="sal-investigation-form">
		<?php wp_nonce_field( 'sal_investigation' ); ?>
		<input type="hidden" name="page" value="sal-investigation">
		<select name="type">
			<option value="ip" <?php selected( 'ip', $type ); ?>><?php esc_html_e( 'IP Address', 'simple-activity-log' ); ?></option>
			<option value="username" <?php selected( 'username', $type ); ?>><?php esc_html_e( 'Username', 'simple-activity-log' ); ?></option>
		</select>
		<input type="text" name="value" value="<?php echo isset( $value ) ? esc_attr( $value ) : ''; ?>" placeholder="<?php esc_attr_e( 'IP or username', 'simple-activity-log' ); ?>" required>
		<select name="hours">
			<option value="24" <?php selected( 24, $hours ); ?>>24h</option>
			<option value="168" <?php selected( 168, $hours ); ?>>7d</option>
			<option value="720" <?php selected( 720, $hours ); ?>>30d</option>
		</select>
		<?php submit_button( __( 'Investigate', 'simple-activity-log' ), 'secondary', '', false ); ?>
	</form>

	<?php if ( empty( $report ) ) : ?>
		<div class="notice notice-info"><p><?php esc_html_e( 'Enter an IP address or username to start an investigation.', 'simple-activity-log' ); ?></p></div>
	<?php else : ?>
		<div class="sal-security-summary sal-investigation-kpis">
			<div><strong><?php echo esc_html( $report['score'] ); ?></strong><span><?php esc_html_e( 'Risk Score', 'simple-activity-log' ); ?></span></div>
			<div><strong><?php echo esc_html( ucfirst( $report['level'] ) ); ?></strong><span><?php esc_html_e( 'Risk Level', 'simple-activity-log' ); ?></span></div>
			<div><strong><?php echo esc_html( $report['failed'] ); ?></strong><span><?php esc_html_e( 'Failed Logins', 'simple-activity-log' ); ?></span></div>
			<div><strong><?php echo esc_html( $report['logins'] ); ?></strong><span><?php esc_html_e( 'Successful Logins', 'simple-activity-log' ); ?></span></div>
			<div><strong><?php echo esc_html( count( $report['incidents'] ) ); ?></strong><span><?php esc_html_e( 'Related Incidents', 'simple-activity-log' ); ?></span></div>
		</div>

		<div class="sal-dashboard-grid">
			<section class="sal-dashboard-card">
				<h2><?php esc_html_e( 'Context', 'simple-activity-log' ); ?></h2>
				<p><strong><?php esc_html_e( 'Target:', 'simple-activity-log' ); ?></strong> <?php echo esc_html( $report['value'] ); ?></p>
				<p><strong><?php esc_html_e( 'First seen:', 'simple-activity-log' ); ?></strong> <?php echo esc_html( $report['first_seen'] ); ?></p>
				<p><strong><?php esc_html_e( 'Last seen:', 'simple-activity-log' ); ?></strong> <?php echo esc_html( $report['last_seen'] ); ?></p>
			</section>

			<section class="sal-dashboard-card">
				<h2><?php echo $is_ip ? esc_html__( 'Usernames Tried', 'simple-activity-log' ) : esc_html__( 'IP Addresses Used', 'simple-activity-log' ); ?></h2>
				<table class="widefat striped">
					<thead><tr><th><?php echo $is_ip ? esc_html__( 'Username', 'simple-activity-log' ) : esc_html__( 'IP Address', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Attempts', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Last Seen', 'simple-activity-log' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $report['related'] as $row ) : ?>
						<tr><td><?php echo esc_html( $row->{$is_ip ? 'username' : 'ip_address'} ?: __( '(unknown)', 'simple-activity-log' ) ); ?></td><td><?php echo esc_html( $row->attempts ); ?></td><td><?php echo esc_html( $row->last_seen ); ?></td></tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		</div>

		<section class="sal-dashboard-card sal-table-scroll">
			<h2><?php esc_html_e( 'Related Incidents', 'simple-activity-log' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Incident', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Severity', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Score', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Occurrences', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Status', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Last Seen', 'simple-activity-log' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $report['incidents'] as $incident ) : ?>
					<tr><td><a href="<?php echo esc_url( admin_url( 'admin.php?page=sal-incidents&incident=' . absint( $incident->id ) ) ); ?>">#<?php echo esc_html( $incident->id ); ?> — <?php echo esc_html( $incident->title ); ?></a></td><td><span class="sal-badge sal-badge-<?php echo esc_attr( $incident->severity ); ?>"><?php echo esc_html( ucfirst( $incident->severity ) ); ?></span></td><td><?php echo esc_html( $incident->score ); ?></td><td><?php echo esc_html( $incident->occurrences ); ?></td><td><?php echo esc_html( ucfirst( $incident->status ) ); ?></td><td><?php echo esc_html( $incident->last_seen ); ?></td></tr>
				<?php endforeach; ?>
				<?php if ( empty( $report['incidents'] ) ) : ?><tr><td colspan="6"><?php esc_html_e( 'No related incidents found.', 'simple-activity-log' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>
		</section>

		<section class="sal-dashboard-card sal-table-scroll">
			<h2><?php esc_html_e( 'Activity Timeline', 'simple-activity-log' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Time', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Action', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Username', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'IP', 'simple-activity-log' ); ?></th><th><?php esc_html_e( 'Message', 'simple-activity-log' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $report['logs'] as $log ) : ?>
					<tr><td><?php echo esc_html( $log->created_at ); ?></td><td><?php echo esc_html( $log->action ); ?></td><td><?php echo esc_html( $log->username ); ?></td><td><?php echo esc_html( $log->ip_address ); ?></td><td><?php echo esc_html( $log->message ); ?></td></tr>
				<?php endforeach; ?>
				<?php if ( empty( $report['logs'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No activity found in this window.', 'simple-activity-log' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>
		</section>
	<?php endif; ?>
</div>
