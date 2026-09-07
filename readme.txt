=== Simple Activity Log ===
Contributors: mohamed
Tags: activity log, audit log, security, logging
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Logs who did what and when — including logins, content changes, orders, settings, users, and failed login attempts.

== Description ==

Simple Activity Log provides a central audit trail for important WordPress and WooCommerce activity.

It records:

* Successful logins, logouts, and failed login attempts.
* Product creation, edits, status changes, trash, restore, and permanent deletion.
* Product price, sale price, SKU, stock quantity, and stock status changes.
* WooCommerce order creation, status changes, trash, restore, and permanent deletion.
* Curated WordPress, WooCommerce, plugin, theme, and core setting changes.
* User creation, deletion, role changes, profile updates, and password resets.
* Failed-login clustering by IP address and username.
* Optional email alerts for suspicious failed-login activity.
* Automatic log retention and scheduled cleanup.
* A WordPress Dashboard activity widget.
* Filterable admin logs and CSV export.

The plugin is designed around one flexible database table and a small logger architecture so additional event types can be added without changing the core logging API.

== Security and privacy ==

Activity records can contain usernames, user IDs, IP addresses, user-agent strings, timestamps, and selected before/after setting values. Access to the log viewer and CSV export is restricted to users with the `manage_options` capability.

IP addresses and user-agent strings are retained as part of the audit record until the configured retention period removes them. The default retention period is 90 days; setting retention to 0 keeps logs indefinitely.

The plugin does not log passwords or password values. Site owners should review their privacy policy and retention requirements before enabling indefinite retention.

== Installation ==

1. Upload the `simple-activity-log` directory to `/wp-content/plugins/`.
2. Activate the plugin from **Plugins** in WordPress.
3. Open **Settings > Simple Activity Log** to configure retention and optional email alerts.
4. Open **Tools > Activity Log** to review events.
5. Use **Tools > Activity Log > Security** to review failed-login activity.

WooCommerce logging is enabled automatically when WooCommerce is active.

== Architecture ==

`simple-activity-log.php` bootstraps the plugin and handles activation/deactivation.

`includes/Core/`

* `Database.php` — creates and upgrades the single `wp_sal_logs` table.
* `Logger.php` — central write API used by every logger.
* `LogQuery.php` — filtering, pagination, counts, and export reads.
* `Plugin.php` — registers loggers and admin components.
* `SecurityAnalyzer.php` — failed-login clustering by IP and username.
* `Retention.php` — scheduled deletion of expired records.
* `AlertManager.php` — optional email alerts for suspicious login activity.

`includes/Loggers/`

* `AbstractLogger.php` — base logger contract.
* `AuthLogger.php` — authentication activity.
* `ProductLogger.php` — WooCommerce product activity.
* `OrderLogger.php` — WooCommerce order activity.
* `SettingsLogger.php` — curated settings and plugin/theme lifecycle activity.
* `UserLogger.php` — user and role activity.

`includes/Admin/`

* `LogsPage.php` — admin log viewer and CSV export.
* `SecurityPage.php` — failed-login security view.
* `SettingsPage.php` — retention and alert configuration.
* `DashboardWidget.php` — recent activity dashboard widget.

== Extending ==

Third-party code can register additional logger classes without modifying the core plugin:

    add_action( 'sal_register_loggers', function( $plugin ) {
        $plugin->add( new \YourNamespace\ProductLogger() );
    } );

The central logging API is `SAL\\Core\\Logger::log()`.

== Filters ==

Suspicious-login threshold:

    add_filter( 'sal_suspicious_login_threshold', function( $threshold ) {
        return 10;
    } );

Watched product meta fields:

    add_filter( 'sal_watched_product_meta', function( $fields ) {
        $fields['_my_product_meta'] = 'My field';
        return $fields;
    } );

Watched WordPress options:

    add_filter( 'sal_watched_options', function( $options ) {
        $options[] = 'my_custom_option';
        return $options;
    } );

== WooCommerce compatibility ==

WooCommerce logging uses WooCommerce order hooks rather than direct assumptions about the underlying order storage, so it is designed to work with both legacy order storage and HPOS.

WooCommerce is optional. The plugin does not require WooCommerce to log normal WordPress activity.

== FAQ ==

= Does this plugin block attackers? =

No. The Security view and optional email alerts detect and report suspicious failed-login patterns. They do not automatically block IP addresses or lock accounts.

= Can I keep logs forever? =

Yes. Set the retention period to 0. Be aware that audit logs can contain personal data such as IP addresses and usernames, so indefinite retention should be used only when appropriate for your site's requirements.

= Who can view the logs? =

Users with the `manage_options` capability can view and export logs.

= Does it support WooCommerce HPOS? =

The order logger uses WooCommerce order-level hooks and is designed to work with HPOS as well as legacy order storage. A real store test is still recommended before relying on it for compliance-critical auditing.

== Changelog ==

= 1.0.0 =
* First release.
* Added WordPress authentication, product, order, settings, user, and security logging.
* Added retention, suspicious-login alerts, dashboard activity widget, filters, and CSV export.
* Added WordPress.org release metadata and automated quality checks.

== Upgrade Notice ==

= 1.0.0 =
Initial stable release.
