<?php

namespace SAL\Admin;

use SAL\Core\IncidentManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security incident management page.
 */
class IncidentsPage {

	const CAPABILITY = 'manage_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_sal_incident_status', array( $this, 'change_status' ) );
	}

	public function register_menu() {
		add_submenu_page(
			'sal-logs',
			__( 'Incidents', 'simple-activity-log' ),
			__( 'Incidents', 'simple-activity-log' ),
			self::CAPABILITY,
			'sal-incidents',
			array( $this, 'render' )
		);
	}

	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'simple-activity-log' ) );
		}

		$status    = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$incidents = IncidentManager::all( $status, 100 );
		$statuses  = array(
			'open'          => __( 'Open', 'simple-activity-log' ),
			'investigating' => __( 'Investigating', 'simple-activity-log' ),
			'resolved'      => __( 'Resolved', 'simple-activity-log' ),
			'ignored'       => __( 'Ignored', 'simple-activity-log' ),
		);

		include SAL_PATH . 'templates/incidents.php';
	}

	public function change_status() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'simple-activity-log' ) );
		}

		check_admin_referer( 'sal_incident_status' );

		$id     = isset( $_POST['incident_id'] ) ? absint( $_POST['incident_id'] ) : 0;
		$status = isset( $_POST['incident_status'] ) ? sanitize_key( wp_unslash( $_POST['incident_status'] ) ) : '';
		IncidentManager::set_status( $id, $status );

		wp_safe_redirect( admin_url( 'admin.php?page=sal-incidents' ) );
		exit;
	}
}
