<?php

namespace SAL\Loggers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every logger (AuthLogger now; ProductLogger/OrderLogger/SettingsLogger/
 * SecurityLogger later) extends this. A logger's only job is to hook the
 * relevant WordPress actions and call Logger::log() — it never queries
 * or displays logs itself.
 */
abstract class AbstractLogger {

	/**
	 * Unique machine name, e.g. 'auth', 'product', 'order'.
	 */
	abstract public function id();

	/**
	 * Register this logger's WordPress hooks. Called once, when the
	 * logger is added via Plugin::add().
	 */
	abstract public function register_hooks();
}
