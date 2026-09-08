<?php

namespace SAL\Admin;

use SAL\Core\IPBlocklist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin interface for IP blocklist and allowlist management.
 */
class IPBlocklistPage {

	const CAPABILITY = 'manage_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_sal_ip_block_action', array( $this, 'handle_action' ) );
	}

	public function register_menu() {
		add_submenu_page( 'sal-logs', __( 'IP Blocklist', 'simple-activity-log' ), __( 'IP Blocklist', 'simple-activity-log' ), self::CAPABILITY, 'sal-ip-blocklist', array( $this, 'render' ) );
	}

	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'simple-activity-log' ) );
		}
		$blocklist = IPBlocklist::get_blocklist();
		$allowlist = IPBlocklist::get_allowlist();
		include SAL_PATH . 'templates/ip-blocklist.php';
	}

	public function handle_action() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'simple-activity-log' ) );
		}
		check_admin_referer( 'sal_ip_block_action' );
		$action = isset( $_POST['ip_action'] ) ? sanitize_key( wp_unslash( $_POST['ip_action'] ) ) : '';
		$ip     = isset( $_POST['ip_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_address'] ) ) : '';
		$reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';

		switch ( $action ) {
			case 'block':
				IPBlocklist::block( $ip, $reason );
				break;
			case 'unblock':
				IPBlocklist::unblock( $ip );
				break;
			case 'allow':
				IPBlocklist::allow( $ip );
				break;
			case 'remove_allow':
				IPBlocklist::remove_allow( $ip );
				break;
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'sal-ip-blocklist', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
