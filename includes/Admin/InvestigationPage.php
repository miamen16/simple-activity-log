<?php

namespace SAL\Admin;

use SAL\Core\SecurityInvestigator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security investigation workspace for IP addresses and usernames.
 */
class InvestigationPage {

	const CAPABILITY = 'manage_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	public function register_menu() {
		add_submenu_page( 'sal-logs', __( 'Investigation', 'simple-activity-log' ), __( 'Investigation', 'simple-activity-log' ), self::CAPABILITY, 'sal-investigation', array( $this, 'render' ) );
	}

	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'simple-activity-log' ) );
		}

		$type  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = isset( $_GET['value'] ) ? sanitize_text_field( wp_unslash( $_GET['value'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$hours = isset( $_GET['hours'] ) ? absint( $_GET['hours'] ) : 168; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $hours, array( 24, 168, 720 ), true ) ) {
			$hours = 168;
		}

		$report = array();
		if ( 'ip' === $type && filter_var( $value, FILTER_VALIDATE_IP ) ) {
			$report = SecurityInvestigator::by_ip( $value, $hours );
		} elseif ( 'username' === $type && '' !== $value ) {
			$report = SecurityInvestigator::by_username( $value, $hours );
		}

		include SAL_PATH . 'templates/investigation.php';
	}
}
