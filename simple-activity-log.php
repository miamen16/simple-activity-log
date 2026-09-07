<?php
/**
 * Plugin Name:       Simple Activity Log
 * Plugin URI:        https://github.com/miamen16/simple-activity-log
 * Description:       Logs who did what and when — logins, content changes, orders, settings, and failed login attempts.
 * Version:           1.0.0
 * Requires at least: 5.3
 * Requires PHP:      7.4
 * Author:            Mohamed Ibrahim
 * Author URI:        https://github.com/miamen16
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       simple-activity-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'SAL_VERSION', '1.0.0' );
define( 'SAL_FILE', __FILE__ );
define( 'SAL_PATH', plugin_dir_path( __FILE__ ) );
define( 'SAL_URL', plugin_dir_url( __FILE__ ) );
define( 'SAL_DB_VERSION', '1.0.1' );

/**
 * Simple PSR-4-ish autoloader for the SAL namespace.
 * SAL\Core\Plugin        -> includes/Core/Plugin.php
 * SAL\Loggers\AuthLogger -> includes/Loggers/AuthLogger.php
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'SAL\\';

	if ( strpos( $class, $prefix ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, strlen( $prefix ) );
	$relative_path  = str_replace( '\\', '/', $relative_class ) . '.php';
	$file           = SAL_PATH . 'includes/' . $relative_path;

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

/**
 * Public API for third-party plugins and themes to record activity.
 *
 * Example:
 * sal_log( 'custom_action', 'A custom action occurred', array(
 *     'object_type' => 'product',
 *     'object_id'   => 123,
 *     'meta'        => array( 'source' => 'my-plugin' ),
 * ) );
 *
 * @param string $action Machine-readable event type.
 * @param string $message Human-readable event summary.
 * @param array  $args Optional event context.
 * @return int|false Inserted log ID on success, false on failure.
 */
function sal_log( $action, $message, array $args = array() ) {
	return \SAL\Core\Logger::log( $action, $message, $args );
}

/**
 * Boot the plugin once all plugins are loaded.
 */
function sal_boot() {
	\SAL\Core\Plugin::instance()->init();
}
add_action( 'plugins_loaded', 'sal_boot' );

/**
 * Activation: create the log table and schedule the daily cleanup cron.
 */
function sal_activate() {
	require_once SAL_PATH . 'includes/Core/Database.php';
	\SAL\Core\Database::install();

	require_once SAL_PATH . 'includes/Core/Retention.php';
	\SAL\Core\Retention::schedule();
}
register_activation_hook( __FILE__, 'sal_activate' );

/**
 * Deactivation: stop the cleanup cron (keep the data — uninstall.php
 * handles full cleanup if the plugin is deleted).
 */
function sal_deactivate() {
	require_once SAL_PATH . 'includes/Core/Retention.php';
	\SAL\Core\Retention::unschedule();
}
register_deactivation_hook( __FILE__, 'sal_deactivate' );
