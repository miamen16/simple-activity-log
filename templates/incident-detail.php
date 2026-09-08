<?php
/**
 * Vars available: $incident, $timeline, $signals, $statuses.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$format_date = static function ( $value ) {
	$timestamp = strtotime( $value );
	return false === $timestamp
		? '—'
		: date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
};

$severity = sanitize_key( $incident->severity );
$status   = sanitize_key( $incident->status );
$ip_url   = wp_nonce_url( admin_url( 'admin.php?page=sal-investigation&type=ip&value=' . rawurlencode( $incident->ip_address ) ), 'sal_investigation' );
$user_url = wp_nonce_url( admin_url( 'admin.php?page=sal-investigation&type=username&value=' . rawurlencode( $incident->username ) ), 'sal_investigation' );
?>
<div class="wrap sal-wrap">
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=sal-incidents' ) ); ?>">&larr; <?php esc_html_e( 'Back to incidents', 'simple-activity-log' ); ?></a></p>

	<div class="sal-incident-header">
		<div>
			<p class="description">
				<?php
				/* translators: %d: incident ID */
				echo esc_html( sprintf( __( 'Incident #%d', 'simple-activity-log' ), absint( $incident->id ) ) );
				?>
			</p>
			<h1><?php echo esc_html( $incident->title ); ?></h1>
		</div>
		<div class="sal-incident-badges">
			<span class="sal-badge sal-badge-<?php echo esc_attr( $severity ); ?>"><?php echo esc_html( ucfirst( $severity ) ); ?></span>
			<span class="sal-badge sal-badge-status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
		</div>
	</div>

	<div class="sal-incident-kpis">
		<div class="sal-summary-card"><span class="sal-summary-number"><?php echo esc_html( $incident->score ); ?></span><span class="sal-summary-label"><?php esc_html_e( 'Risk score / 100', 'simple-activity-log' ); ?></span></div>
		<div class="sal-summary-card"><span class="sal-summary-number"><?php echo esc_html( $incident->occurrences ); ?></span><span class="sal-summary-label"><?php esc_html_e( 'Occurrences', 'simple-activity-log' ); ?></span></div>
		<div class="sal-summary-card"><span class="sal-summary-number"><?php echo esc_html( $format_date( $incident->first_seen ) ); ?></span><span class="sal-summary-label"><?php esc_html_e( 'First seen', 'simple-activity-log' ); ?></span></div>
		<div class="sal-summary-card"><span class="sal-summary-number"><?php echo esc_html( $format_date( $incident->last_seen ) ); ?></span><span class="sal-summary-label"><?php esc_html_e( 'Last seen', 'simple-activity-log' ); ?></span></div>
	</div>

	<div class="sal-dashboard-grid sal-incident-grid">
		<div class="sal-dashboard-card">
			<h2><?php esc_html_e( 'Incident context', 'simple-activity-log' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'Username', 'simple-activity-log' ); ?></th><td><?php if ( $incident->username ) : ?><a href="<?php echo esc_url( $user_url ); ?>"><?php echo esc_html( $incident->username ); ?></a><?php else : ?>—<?php endif; ?></td></tr>
					<tr><th><?php esc_html_e( 'IP Address', 'simple-activity-log' ); ?></th><td><?php if ( $incident->ip_address ) : ?><a href="<?php echo esc_url( $ip_url ); ?>"><code><?php echo esc_html( $incident->ip_address ); ?></code></a><?php else : ?>—<?php endif; ?></td></tr>
					<tr><th><?php esc_html_e( 'Detection type', 'simple-activity-log' ); ?></th><td><code><?php echo esc_html( $incident->type ); ?></code></td></tr>
					<tr><th><?php esc_html_e( 'Source log ID', 'simple-activity-log' ); ?></th><td><?php echo esc_html( $incident->source_log_id ? '#' . absint( $incident->source_log_id ) : '—' ); ?></td></tr>
				</tbody>
			</table>
			<p><a class="button" href="<?php echo esc_url( $ip_url ); ?>"><?php esc_html_e( 'Investigate IP', 'simple-activity-log' ); ?></a> <?php if ( $incident->username ) : ?><a class="button" href="<?php echo esc_url( $user_url ); ?>"><?php esc_html_e( 'Investigate User', 'simple-activity-log' ); ?></a><?php endif; ?></p>
		</div>

		<div class="sal-dashboard-card">
			<h2><?php esc_html_e( 'Detection signals', 'simple-activity-log' ); ?></h2>
			<?php if ( empty( $signals ) ) : ?>
				<p class="description"><?php esc_html_e( 'No signal details were recorded.', 'simple-activity-log' ); ?></p>
			<?php else : ?>
				<ul class="sal-signal-list">
					<?php foreach ( $signals as $signal ) : ?>
						<li><code><?php echo esc_html( is_scalar( $signal ) ? $signal : wp_json_encode( $signal ) ); ?></code></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="sal-dashboard-card sal-incident-status-card">
		<h2><?php esc_html_e( 'Incident status', 'simple-activity-log' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'sal_incident_status' ); ?>
			<input type="hidden" name="action" value="sal_incident_status" />
			<input type="hidden" name="incident_id" value="<?php echo esc_attr( $incident->id ); ?>" />
			<select name="incident_status">
				<?php foreach ( $statuses as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $incident->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Update Status', 'simple-activity-log' ), 'primary', 'submit', false ); ?>
		</form>
	</div>

	<div class="sal-dashboard-card">
		<h2><?php esc_html_e( 'Activity Timeline', 'simple-activity-log' ); ?></h2>
		<?php if ( empty( $timeline ) ) : ?>
			<p class="description"><?php esc_html_e( 'No matching activity was found in the incident window.', 'simple-activity-log' ); ?></p>
		<?php else : ?>
			<div class="sal-table-scroll">
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'Time', 'simple-activity-log' ); ?></th>
						<th><?php esc_html_e( 'Action', 'simple-activity-log' ); ?></th>
						<th><?php esc_html_e( 'User', 'simple-activity-log' ); ?></th>
						<th><?php esc_html_e( 'IP Address', 'simple-activity-log' ); ?></th>
						<th><?php esc_html_e( 'Message', 'simple-activity-log' ); ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ( $timeline as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $format_date( $log->created_at ) ); ?></td>
								<td><span class="sal-badge sal-badge-<?php echo esc_attr( $log->action ); ?>"><?php echo esc_html( $log->action ); ?></span></td>
								<td><?php echo esc_html( $log->username ?: '—' ); ?></td>
								<td><code><?php echo esc_html( $log->ip_address ?: '—' ); ?></code></td>
								<td><?php echo esc_html( $log->message ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>
