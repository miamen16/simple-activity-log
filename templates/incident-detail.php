<?php
/**
 * Vars available: $incident, $timeline, $signals, $statuses.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$format_date = static function ( $value ) {
	return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $value ) );
};
?>
<div class="wrap sal-wrap">
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=sal-incidents' ) ); ?>">&larr; <?php esc_html_e( 'Back to incidents', 'simple-activity-log' ); ?></a></p>
	<h1><?php echo esc_html( $incident->title ); ?></h1>

	<table class="widefat striped" style="max-width:900px; margin:20px 0;">
		<tbody>
			<tr><th><?php esc_html_e( 'Severity', 'simple-activity-log' ); ?></th><td><strong><?php echo esc_html( ucfirst( $incident->severity ) ); ?></strong></td></tr>
			<tr><th><?php esc_html_e( 'Risk Score', 'simple-activity-log' ); ?></th><td><strong><?php echo esc_html( $incident->score ); ?>/100</strong></td></tr>
			<tr><th><?php esc_html_e( 'Occurrences', 'simple-activity-log' ); ?></th><td><?php echo esc_html( $incident->occurrences ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Username', 'simple-activity-log' ); ?></th><td><?php echo esc_html( $incident->username ?: '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'IP Address', 'simple-activity-log' ); ?></th><td><?php echo esc_html( $incident->ip_address ?: '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'First Seen', 'simple-activity-log' ); ?></th><td><?php echo esc_html( $format_date( $incident->first_seen ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Last Seen', 'simple-activity-log' ); ?></th><td><?php echo esc_html( $format_date( $incident->last_seen ) ); ?></td></tr>
			<tr>
				<th><?php esc_html_e( 'Status', 'simple-activity-log' ); ?></th>
				<td>
					<form method="post">
						<?php wp_nonce_field( 'sal_incident_status' ); ?>
						<input type="hidden" name="action" value="sal_incident_status" />
						<input type="hidden" name="incident_id" value="<?php echo esc_attr( $incident->id ); ?>" />
						<select name="incident_status">
							<?php foreach ( $statuses as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $incident->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php submit_button( __( 'Update Status', 'simple-activity-log' ), 'secondary', 'submit', false ); ?>
					</form>
				</td>
			</tr>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Detection Signals', 'simple-activity-log' ); ?></h2>
	<?php if ( empty( $signals ) ) : ?>
		<p><?php esc_html_e( 'No signal details were recorded.', 'simple-activity-log' ); ?></p>
	<?php else : ?>
		<ul>
			<?php foreach ( $signals as $signal ) : ?>
				<li><code><?php echo esc_html( is_scalar( $signal ) ? $signal : wp_json_encode( $signal ) ); ?></code></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Activity Timeline', 'simple-activity-log' ); ?></h2>
	<?php if ( empty( $timeline ) ) : ?>
		<p><?php esc_html_e( 'No matching activity was found in the incident window.', 'simple-activity-log' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Time', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Action', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'User', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'IP Address', 'simple-activity-log' ); ?></th>
					<th><?php esc_html_e( 'Message', 'simple-activity-log' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $timeline as $log ) : ?>
					<tr>
						<td><?php echo esc_html( $format_date( $log->created_at ) ); ?></td>
						<td><code><?php echo esc_html( $log->action ); ?></code></td>
						<td><?php echo esc_html( $log->username ?: '—' ); ?></td>
						<td><?php echo esc_html( $log->ip_address ?: '—' ); ?></td>
						<td><?php echo esc_html( $log->message ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
