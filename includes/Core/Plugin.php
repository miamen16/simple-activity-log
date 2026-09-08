<?php

namespace SAL\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central singleton that wires the plugin together.
 */
class Plugin {

	private static $instance = null;

	/** @var \SAL\Loggers\AbstractLogger[] */
	private $loggers = array();

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init() {
		Database::maybe_upgrade();
		add_action( Retention::CRON_HOOK, array( 'SAL\\Core\\Retention', 'run_cleanup' ) );
		Privacy::register();
		SecurityDetector::register();
		IncidentAlertManager::register();
		$this->register_loggers();

		if ( is_admin() ) {
			new \SAL\Admin\LogsPage();
			new \SAL\Admin\SecurityDashboardPage();
			new \SAL\Admin\SecurityPage();
			new \SAL\Admin\IncidentsPage();
			new \SAL\Admin\InvestigationPage();
			new \SAL\Admin\IPBlocklistPage();
			new \SAL\Admin\SettingsPage();
			new \SAL\Admin\DashboardWidget();
		}
	}

	private function register_loggers() {
		$this->add( new \SAL\Loggers\AuthLogger() );
		$this->add( new \SAL\Loggers\ProductLogger() );
		$this->add( new \SAL\Loggers\SettingsLogger() );
		$this->add( new \SAL\Loggers\UserLogger() );

		if ( \SAL\Loggers\OrderLogger::is_woocommerce_active() ) {
			$this->add( new \SAL\Loggers\OrderLogger() );
		}

		do_action( 'sal_register_loggers', $this );
	}

	public function add( \SAL\Loggers\AbstractLogger $logger ) {
		$this->loggers[ $logger->id() ] = $logger;
		$logger->register_hooks();
	}
}
