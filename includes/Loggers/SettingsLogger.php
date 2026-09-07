<?php

namespace SAL\Loggers;

use SAL\Core\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logs two related but distinct things:
 *
 * 1. Changes to a curated list of meaningfully important WordPress/
 *    WooCommerce settings (site title, URL, admin email, permalink
 *    structure, currency, etc.) — NOT every option update, since
 *    wp_options changes constantly (transients, cron locks, cache) and
 *    logging all of it would drown the log in noise.
 * 2. Plugin and theme lifecycle: activated, deactivated, installed,
 *    updated, deleted, and theme switches.
 */
class SettingsLogger extends AbstractLogger {

	public function id() {
		return 'settings';
	}

	/**
	 * The option names worth logging. Filterable so a site (or a future
	 * phase) can watch more settings without editing this file.
	 */
	private function watched_options() {
		$defaults = array(
			'blogname',
			'blogdescription',
			'siteurl',
			'home',
			'admin_email',
			'permalink_structure',
			'timezone_string',
			'gmt_offset',
			'users_can_register',
			'default_role',
			'WPLANG',
			'woocommerce_currency',
			'woocommerce_default_country',
		);

		return apply_filters( 'sal_watched_options', $defaults );
	}

	public function register_hooks() {
		add_action( 'updated_option', array( $this, 'on_option_updated' ), 10, 3 );

		add_action( 'activated_plugin', array( $this, 'on_plugin_activated' ), 10, 2 );
		add_action( 'deactivated_plugin', array( $this, 'on_plugin_deactivated' ), 10, 2 );
		add_action( 'deleted_plugin', array( $this, 'on_plugin_deleted' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'on_upgrader_complete' ), 10, 2 );

		add_action( 'switch_theme', array( $this, 'on_theme_switched' ), 10, 3 );
		add_action( 'delete_theme', array( $this, 'on_theme_deleted' ) );
	}

	public function on_option_updated( $option, $old_value, $value ) {
		if ( ! in_array( $option, $this->watched_options(), true ) ) {
			return;
		}

		if ( $old_value === $value ) {
			return;
		}

		Logger::log(
			'setting_changed',
			sprintf(
				/* translators: 1: setting name, 2: old value, 3: new value */
				__( 'Setting "%1$s" changed from %2$s to %3$s.', 'simple-activity-log' ),
				$option,
				$this->stringify( $old_value ),
				$this->stringify( $value )
			),
			array(
				'object_type' => 'option',
				'meta'        => array( 'option' => $option ),
			)
		);
	}

	public function on_plugin_activated( $plugin_file, $network_wide = false ) {
		Logger::log(
			'plugin_activated',
			sprintf(
				/* translators: %s: plugin name */
				__( 'Activated plugin "%s".', 'simple-activity-log' ),
				$this->plugin_name( $plugin_file )
			),
			array( 'object_type' => 'plugin', 'meta' => array( 'file' => $plugin_file, 'network_wide' => (bool) $network_wide ) )
		);
	}

	public function on_plugin_deactivated( $plugin_file, $network_wide = false ) {
		Logger::log(
			'plugin_deactivated',
			sprintf(
				/* translators: %s: plugin name */
				__( 'Deactivated plugin "%s".', 'simple-activity-log' ),
				$this->plugin_name( $plugin_file )
			),
			array( 'object_type' => 'plugin', 'meta' => array( 'file' => $plugin_file, 'network_wide' => (bool) $network_wide ) )
		);
	}

	/**
	 * $deleted is true/false depending on whether delete_plugins()
	 * actually succeeded — only log a real deletion.
	 */
	public function on_plugin_deleted( $plugin_file, $deleted ) {
		if ( ! $deleted ) {
			return;
		}

		Logger::log(
			'plugin_deleted',
			sprintf(
				/* translators: %s: plugin file path */
				__( 'Deleted plugin "%s".', 'simple-activity-log' ),
				$plugin_file
			),
			array( 'object_type' => 'plugin', 'meta' => array( 'file' => $plugin_file ) )
		);
	}

	/**
	 * Fires after ANY install/update via the WP upgrader — covers plugins,
	 * themes, and WordPress core in one hook. $options carries 'action'
	 * ('install'|'update') and 'type' ('plugin'|'theme'|'core'), plus
	 * either a single item or a list depending on whether it was a bulk
	 * operation.
	 */
	public function on_upgrader_complete( $upgrader, $options ) {
		$action = isset( $options['action'] ) ? $options['action'] : '';
		$type   = isset( $options['type'] ) ? $options['type'] : '';

		if ( 'plugin' === $type ) {
			$files = ! empty( $options['plugins'] ) ? $options['plugins'] : ( ! empty( $options['plugin'] ) ? array( $options['plugin'] ) : array() );

			foreach ( $files as $file ) {
				$log_action = 'install' === $action ? 'plugin_installed' : 'plugin_updated';
				$verb       = 'install' === $action
					? /* translators: %s: plugin name */
					__( 'Installed plugin "%s".', 'simple-activity-log' )
					: /* translators: %s: plugin name */
					__( 'Updated plugin "%s".', 'simple-activity-log' );

				Logger::log(
					$log_action,
					sprintf( $verb, $this->plugin_name( $file ) ),
					array( 'object_type' => 'plugin', 'meta' => array( 'file' => $file ) )
				);
			}
		} elseif ( 'theme' === $type ) {
			$slugs = ! empty( $options['themes'] ) ? $options['themes'] : ( ! empty( $options['theme'] ) ? array( $options['theme'] ) : array() );

			foreach ( $slugs as $slug ) {
				$log_action = 'install' === $action ? 'theme_installed' : 'theme_updated';
				$verb       = 'install' === $action
					? /* translators: %s: theme slug */
					__( 'Installed theme "%s".', 'simple-activity-log' )
					: /* translators: %s: theme slug */
					__( 'Updated theme "%s".', 'simple-activity-log' );

				Logger::log(
					$log_action,
					sprintf( $verb, $slug ),
					array( 'object_type' => 'theme', 'meta' => array( 'slug' => $slug ) )
				);
			}
		} elseif ( 'core' === $type ) {
			Logger::log(
				'core_updated',
				__( 'WordPress core was updated.', 'simple-activity-log' ),
				array( 'object_type' => 'core' )
			);
		}
	}

	public function on_theme_switched( $new_name, $new_theme, $old_theme ) {
		$old_name = ( $old_theme instanceof \WP_Theme ) ? $old_theme->get( 'Name' ) : __( 'Unknown', 'simple-activity-log' );

		Logger::log(
			'theme_switched',
			sprintf(
				/* translators: 1: old theme name, 2: new theme name */
				__( 'Switched theme from "%1$s" to "%2$s".', 'simple-activity-log' ),
				$old_name,
				$new_name
			),
			array( 'object_type' => 'theme' )
		);
	}

	public function on_theme_deleted( $stylesheet ) {
		Logger::log(
			'theme_deleted',
			sprintf(
				/* translators: %s: theme slug */
				__( 'Deleted theme "%s".', 'simple-activity-log' ),
				$stylesheet
			),
			array( 'object_type' => 'theme', 'meta' => array( 'slug' => $stylesheet ) )
		);
	}

	private function plugin_name( $plugin_file ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$path = WP_PLUGIN_DIR . '/' . $plugin_file;

		if ( ! file_exists( $path ) ) {
			return $plugin_file; // e.g. after deletion, or a malformed path — fall back to the raw file reference.
		}

		$data = get_plugin_data( $path, false, false );
		return ! empty( $data['Name'] ) ? $data['Name'] : $plugin_file;
	}

	/**
	 * Short, log-safe representation of an option value. Scalars are
	 * shown (truncated); arrays/objects are just flagged as changed
	 * rather than dumped, to keep log messages readable.
	 */
	private function stringify( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( is_scalar( $value ) || null === $value ) {
			$str = (string) $value;
			return strlen( $str ) > 80 ? substr( $str, 0, 80 ) . '…' : ( '' === $str ? __( '(empty)', 'simple-activity-log' ) : $str );
		}
		return __( '(complex value)', 'simple-activity-log' );
	}
}
