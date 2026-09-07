<?php
/**
 * Vars available: $incidents, $statuses, $status.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sal-wrap">
	<h1><?php esc_html_e( 'Security Incidents', 'simple-activity-log' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Incidents group repeated detections into one manageable security case.', 'simple-activity-log' ); ?></p>

	<p>
		<?php foreach ( $statuses as $key => $label ) : ?>
			<a class="button <?php echo $status === $key ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'sal-incidents', 'status' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sal-incidents' ) ); ?>"><?php esc_html_e( 'All', 'simple-activity-log' ); ?></a>
	</p>

	<?php if ( empty( $incidents ) ) : ?>
		<p><?php esc_html_e( 'No incidents found.', 'simple-activity-log' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead><tr>
				<th><?php esc_html_e( 'Incident', 'simple-activity-log' ); ?></th>
				<th><?php esc_html_e( 'Severity', 'simple-activity-log' ); ?></th>
				<th><?php esc_html_e( 'Score', 'simple-activity-log' ); ?></th>
				<th><?php esc_html_e( 'Occurrences', 'simple-activity-log' ); ?></th>
				<th><?php esc_html_e( 'Username', 'simple-activity-log' ); ?></th>
				<th><?php esc_html_e( 'IP Address', 'simple-activity-log' ); ?></th>
				<th><?php esc_html_e( 'Last Seen', 'simple-activity-log' ); ?></th>
				<th><?php esc_html_e( 'Status', 'simple-activity-log' ); ?></th>
			</tr></thead>
			<tbody>
				<?php foreach ( $incidents as $incident ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sal-incidents', 'incident' => absint( $incident->id ) ), admin_url( 'admin.php' ) ) ); ?>"><strong><?php echo esc_html( $incident->title ); ?></strong></a><br /><span class="description">#<?php echo esc_html( $incident->id ); ?></span></td>
						<td><span class="sal-badge sal-badge-<?php echo esc_attr( $incident->severity ); ?>"><?php echo esc_html( ucfirst( $incident->severity ) ); ?></span></td>
						<td><strong><?php echo esc_html( $incident->score ); ?>/100</strong></td>
						<td><?php echo esc_html( $incident->occurrences ); ?></td>
						<td><?php echo esc_html( $incident->username ?: '—' ); ?></td>
						<td><?php echo esc_html( $incident->ip_address ?: '—' ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $incident->last_seen ) ) ); ?></td>
						<td>
							<form method="post">
								<?php wp_nonce_field( 'sal_incident_status' ); ?>
								<input type="hidden" name="action" value="sal_incident_status" />
								<input type="hidden" name="incident_id" value="<?php echo esc_attr( $incident->id ); ?>" />
								<select name="incident_status" onchange="this.form.submit()">
									<?php foreach ( $statuses as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $incident->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
