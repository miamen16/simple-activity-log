=== Simple Activity Log ===
Contributors: mohamed
Tags: activity log, audit log, security, logging
Requires PHP: 7.4
Stable tag: 1.0.0-alpha

Logs who did what and when.

== Description ==

This is Phase 1-5 of the build, plus 5 extra features built on top:
Core logging engine + Auth logging (login, logout, failed login
attempts) + Product lifecycle logging (created, renamed, updated,
field changes like price/SKU/stock, trashed, restored, unpublished,
deleted) + WooCommerce order logging (created, status changes
including cancelled/refunded/completed/failed, trashed, restored,
deleted) + Settings/plugin/theme logging (curated setting changes,
plugin activate/deactivate/install/update/delete, theme switch/
install/update/delete, core updates) + User/role logging (created,
deleted, role changed, updated, password reset) + a Security view
(failed-login clustering by IP and by username) + automatic log
retention (daily cleanup) + email alerts on suspicious login
activity + a Dashboard widget + an admin viewer with filters and
CSV export.

== Architecture ==

simple-activity-log.php          Bootstrap, autoloader, activation
includes/Core/
    Database.php                 Single flexible log table (wp_sal_logs)
    Logger.php                   The only class that WRITES to the table
    LogQuery.php                 Filtering/pagination/export reads
    Plugin.php                   Registers loggers
    SecurityAnalyzer.php         Failed-login clustering by IP/username
    Retention.php                Scheduled cleanup of old log entries
    AlertManager.php             Email alerts on suspicious login activity
includes/Loggers/
    AbstractLogger.php           Base class every logger extends
    AuthLogger.php                Login / logout / failed login
    ProductLogger.php            Product created / renamed / updated /
                                  field changes / trashed / restored /
                                  unpublished / deleted
    OrderLogger.php              Order created / status changes /
                                  trashed / restored / deleted
                                  (WooCommerce-gated)
    SettingsLogger.php           Curated setting changes + plugin/theme
                                  activate/install/update/delete
    UserLogger.php                User created / deleted / role changed /
                                  updated / password reset
includes/Admin/
    LogsPage.php                 Admin page + CSV export handler
    SecurityPage.php             Failed-login security view
    SettingsPage.php             Retention + alert configuration
    DashboardWidget.php          WP Dashboard "Recent Activity" widget
templates/logs.php               Filterable log table
templates/security.php           IP/username clustering tables
templates/settings.php           Retention + alert settings form
templates/dashboard-widget.php   Dashboard widget content
assets/css/admin.css

== Why one flexible table ==

wp_sal_logs has generic columns (object_type, object_id, meta) even
though only auth events use them minimally today. This means adding
the next logger (products, orders, settings, or failed-login-based
security alerts) never requires a schema migration — just a new
Logger class that calls Logger::log() with the right action name.

== Extending ==

To add a new logger (e.g. ProductLogger for edits/deletes):

1. Create includes/Loggers/ProductLogger.php extending AbstractLogger.
2. Implement id() and register_hooks() (hook 'save_post_product',
   'before_delete_post', etc., and call Logger::log() from each).
3. Register it via the 'sal_register_loggers' action:

    add_action( 'sal_register_loggers', function( $plugin ) {
        $plugin->add( new \YourNamespace\ProductLogger() );
    } );

No core files need to change.

== Roadmap ==

Phase 1  Core engine + Auth logging (done)
Phase 2  Product lifecycle logging (done)
Phase 3  WooCommerce order logging (done)
Phase 4  Settings/plugin/theme logging (done)
Phase 5  Security-focused view (done)

All five originally planned phases are built, plus five extra
features: log retention, email alerts, user/role logging, product
field diffs (price/SKU/stock before-after), and a Dashboard widget.

== Notes on the extra features ==

* Retention (Retention.php): a daily cron (sal_cleanup_logs) deletes
  rows older than the configured window (Settings page, default 90
  days; 0 = keep forever). Scheduled on activation, unscheduled on
  deactivation — a logging plugin with no retention policy grows
  forever, so this isn't optional long-term.
* Email alerts (AlertManager.php): checked right after each failed
  login is logged. If an IP or username crosses the suspicious
  threshold (same threshold as the Security page, filterable) within
  the last hour, one email goes out, then a 1-hour cooldown per IP/
  username (via transients) prevents a sustained attack from flooding
  the inbox with one email per attempt. Off by default — enable and
  set the address on the Settings page.
* UserLogger: 'set_user_role' and 'profile_update' can both fire for
  a single role change made through the user-edit screen, so a role
  change may produce two log lines (a specific "role changed" entry
  and a generic "profile updated" one) rather than being perfectly
  de-duplicated — both are accurate on their own, left as acceptable
  overlap, same as the minor OrderLogger overlap noted elsewhere.
* Product field diffs: 'update_post_meta' (the "before" hook — value
  not yet written) is used to capture accurate old/new pairs for
  price, sale price, SKU, stock quantity, and stock status, filterable
  via `sal_watched_product_meta`. Renames are detected via
  'post_updated' (which gives both before/after WP_Post objects),
  stashed in a static cache so on_transition() can use it regardless
  of hook firing order — if the cache isn't populated yet when
  on_transition() runs, it just falls back to the generic "Updated
  product X" message for that save.
* Dashboard widget: shows the 8 most recent log entries plus a
  same-day failed-login count, with a link to the Security page when
  any IP is currently flagged suspicious.
* None of these five have been tested against a live site yet, same
  caveat as everything else in this plugin so far — test after
  deploying, don't assume correctness from the code alone.

== Notes on the Security page ==

* Reuses the existing 'login_failed' events AuthLogger already
  records — no separate security table, same principle as
  VendorHealth reusing Store Doctor's scan data instead of a
  per-vendor re-scan.
* Two clustering views, because they catch different attack shapes:
  by IP (one IP trying many different usernames = brute-force/
  credential-stuffing scanning) and by username (one username hit
  from many different IPs = a targeted or distributed attack on that
  specific account).
* "Suspicious" threshold defaults to 5 attempts in the selected
  window and is filterable without editing plugin files:

    add_filter( 'sal_suspicious_login_threshold', function( $threshold ) {
        return 10;
    } );

* Time windows are 24h / 7d / 30d. Cutoffs are computed with
  current_time('timestamp') to match how created_at is stored
  (via current_time('mysql'), i.e. site-local time) — using UTC time()
  here would silently misalign the window on any site not set to UTC.
* This is a detection view, not an enforcement mechanism — it
  surfaces patterns for a human to look at, it does not auto-block
  IPs or lock accounts. Pairing it with a dedicated login-throttling
  plugin (e.g. one that actually rate-limits or blocks) is worth
  considering for sites that need active protection, not just
  visibility.

== Notes on SettingsLogger ==

* Does NOT log every wp_options change — wp_options is written to
  constantly (transients, cron locks, caches) and logging all of it
  would drown the log in noise within hours. Instead it watches a
  curated allowlist of meaningfully important settings (site title,
  URL, admin email, permalink structure, WooCommerce currency, etc.),
  filterable via the `sal_watched_options` filter so a site can watch
  more without editing plugin files:

    add_filter( 'sal_watched_options', function( $options ) {
        $options[] = 'my_custom_option';
        return $options;
    } );

* Plugin/theme lifecycle uses core WordPress hooks (activated_plugin,
  deactivated_plugin, deleted_plugin, upgrader_process_complete,
  switch_theme, delete_theme) — these are stable, long-standing core
  hooks, not third-party API guesses like the Dokan integration, so
  confidence here is high. Still worth a real test pass (activate/
  deactivate a plugin, install/update one, switch themes, change a
  watched setting) before relying on it for an audit, same as every
  other logger in this plugin so far.
* Setting-change log messages include a short before/after value for
  scalar settings (e.g. admin email, permalink structure) but just
  note "(complex value)" for arrays/objects, to keep entries readable
  — none of the watched settings are secrets (passwords, API keys),
  so showing old/new values is safe.

== Notes on OrderLogger ==

* Uses WooCommerce's own abstracted hooks (woocommerce_new_order,
  woocommerce_order_status_changed, woocommerce_trash_order,
  woocommerce_untrash_order, woocommerce_delete_order) rather than
  post-type hooks, so it should work whether the store uses legacy
  post-based order storage or HPOS (custom order tables) — these are
  fired by WC_Order/the data store either way.
* Only registers if WooCommerce is active (OrderLogger::is_woocommerce_active()),
  matching the Dokan-gating pattern used in the Store Doctor plugin.
* Confidence varies by hook: woocommerce_new_order and
  woocommerce_order_status_changed are long-standing, well-documented
  WooCommerce hooks I'm confident about. woocommerce_trash_order /
  woocommerce_untrash_order / woocommerce_delete_order are real
  WooCommerce data-store hooks but were written from documentation
  knowledge rather than verified against a live site — same caveat as
  ProductLogger and the Dokan integration in Store Doctor: test
  against a real store (create an order, change its status through a
  few values including cancelled/refunded, trash it, restore it,
  delete it permanently) before relying on this for an audit.

== Notes on ProductLogger ==

* Uses a single hook (transition_post_status) for create/edit/trash/
  restore/unpublish, since WordPress fires it on every save regardless
  of whether the status actually changed — this avoids double-logging
  against a separate save_post hook. Permanent deletion uses
  before_delete_post separately, since transition_post_status doesn't
  fire when a post is actually removed from the database.
* Autosaves, revisions, and the throwaway 'auto-draft' placeholder
  WordPress creates before a post's first real save are filtered out
  so they don't create noise entries.
* Not yet tested against a live site with real product edits — the
  logic was written carefully against documented WordPress hook
  behavior, but given what happened testing Store Doctor, treat this
  as unverified until it's actually exercised on a real WooCommerce
  store (create a product, edit it, trash it, restore it, delete it
  permanently, and confirm all five show up correctly).

== Notes ==

* Capability required to view logs or export CSV: manage_options
  (activity logs, especially failed-login attempts and IP addresses,
  are sensitive — kept admin-only rather than manage_woocommerce or
  editor-level).
* CSV export streams every row matching the current filter, not just
  the current page, so it's actually useful for an audit rather than
  a 50-row sample.
* IP detection checks Cloudflare/X-Forwarded-For headers before
  REMOTE_ADDR as a best-effort improvement behind proxies/CDNs — it
  is NOT spoof-proof without a trusted-proxy allowlist, so treat it
  as a record, not a security control on its own.
