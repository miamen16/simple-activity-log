# Professional WordPress Plugin Audit

## Scope

Reviewed the `main` branch of Simple Activity Log across bootstrap/autoloading, database lifecycle, logging, security analysis, admin pages, templates, WooCommerce integration, retention, uninstall, and WordPress.org readiness.

## Findings and status

### High priority — fixed

1. **Timezone cutoff inconsistency — FIXED.** `created_at` is stored in the WordPress site timezone, so retention and security lookback cutoffs now use `wp_date()` in the same timezone instead of mixing site-local timestamps with `gmdate()`.
2. **Log-query indexing — FIXED.** Added composite indexes for `(action, created_at)`, `(ip_address, created_at)`, and `(username, created_at)`, and bumped the database schema version to `1.0.1` so existing installations receive the migration through `dbDelta()`.

### Medium priority — pending release preparation

3. **Release metadata is incomplete for WordPress.org** — the main plugin header does not declare `Requires at least` or a GPL-compatible `License`, while `Plugin URI` still points to an example domain.
4. **readme.txt is incomplete for a public directory submission** — it lacks the standard WordPress.org fields such as `Requires at least`, `Tested up to`, `License`, and `License URI`.
5. **No automated CI/test suite is present** — code-level review cannot replace running PHP lint, PHPCS/WordPress Coding Standards, Plugin Check, and functional tests against supported WordPress/WooCommerce versions.

### Low priority / maintainability

6. The plugin is still marked `1.0.0-alpha`; release metadata should be finalized only after testing on a declared support matrix.
7. CSV export is now bounded by batches, but it still uses OFFSET pagination; a future keyset/cursor export would scale better for very large audit tables.

## Security review

- Admin log/security/settings pages require `manage_options`.
- Settings and CSV mutations use nonces.
- Read-only GET filters are sanitized and do not perform state changes.
- Dynamic SQL identifiers are constrained/whitelisted where applicable.
- User-agent and IP data are validated before persistence.
- Output in the reviewed admin templates is escaped.
- Password values are not logged.
- WooCommerce order logging uses WooCommerce APIs and is gated on WooCommerce being active.

## Functional review

- Product metadata auditing uses WordPress's pre-update post-meta hook and compares old/new values.
- Product lifecycle logging avoids autosave/revision noise.
- User, plugin, theme, settings, authentication, and WooCommerce order events are routed through the central Logger.
- Uninstall removes the custom log table/options and scheduled cleanup hook.

## Recommended validation before release

1. `php -l` against every PHP file.
2. PHPCS with WordPress Coding Standards.
3. WordPress Plugin Check.
4. PHP 7.4 and current supported PHP versions.
5. Current WordPress plus at least one supported previous major version.
6. WooCommerce legacy order storage and HPOS.
7. Multisite activation/deactivation behavior.
8. Large-log performance test and CSV export test.
9. Security regression tests for admin capability/nonce boundaries.
10. WordPress.org readme/header validation.
