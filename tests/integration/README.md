# Live-hook eligibility / Shortcut 52062

`X-Object-Notified-At` is the first intended `eligible_at` Unix timestamp for live-hook work: enqueue time plus the configured delay, before queue-priority backdating. It excludes the initial intentional delay. It does not measure original-event-to-receipt latency.

The serialized job retains this value across retries and reschedules. A new live event replacing a pending payload receives its own first eligibility time. Category fan-out and cart/order product-repair jobs retain their parent's value. POST, PUT, PATCH, and DELETE carry the header during that job's execution; GET does not. Initial sync, manual jobs, old serialized jobs, and invalid metadata omit it. Immediate live-hook mutations use their call time because they have no queue delay. The context is restored after nested execution, failures, and exceptions.

## Regression checks

Run with PHP 7.4+ and the cURL extension:

```sh
for test in tests/regression/*.php; do php "$test" || exit; done
node tests/regression/pixel-cart-identity.js
```

The eligibility regressions exercise queue serialization/replacement, filtered delays, backdating, retry preservation, legacy and malformed payloads, dispatcher cleanup, nested/exception context, existing headers/authentication, and child-job repairs.

## WordPress / WooCommerce integration

Use only a disposable local WordPress installation with WooCommerce and this plugin active. Set `WP_ENVIRONMENT_TYPE` to `local` and disable automatic WP Cron. The test writes test options/jobs and restores the options/removes its pending jobs afterward; completed Action Scheduler records remain in the disposable database.

Start the loopback receiver in one terminal:

```sh
php -S 127.0.0.1:18062 /path/to/plugin/tests/integration/eligibility-receiver.php
```

Then run:

```sh
wp --path=/path/to/wordpress eval-file /path/to/plugin/tests/integration/live-hook-eligibility.php
```

The test uses the installed WooCommerce hook callbacks, real `wp_mailchimp_jobs` storage, and the bundled Action Scheduler store/runner. It exercises retry and pending-action replacement, verifies successful action completion and row deletion, and sends real cURL requests to the loopback receiver to inspect headers, authentication, and payloads. It requires no Mailchimp account. Keep external network access blocked in the test environment.

Delays greater than five and thirty minutes are simulated using known past eligibility values, then run through the real scheduler; tests do not sleep for thirty minutes.

### Compatibility coverage

- PHP 7.4: WordPress 6.2 / WooCommerce 8.2.0 (declared minimums).
- PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, 8.5: WordPress 7.0 / WooCommerce 11.0.0 (declared tested upper versions).
- All plugin PHP files syntax-checked and all PHP regression scripts run on each of those seven PHP versions.
- PHPCompatibilityWP against `7.4-8.5` for changed PHP files.

The repository's Composer installer dependency targets Composer 1 / PHP 7. With modern Composer, install `phpcompatibility/phpcompatibility-wp` and `squizlabs/php_codesniffer` in a separate tooling directory, register the PHPCompatibility, PHPCompatibilityParagonie and PHPCompatibilityWP standards, then run PHPCS using this repository's ruleset and `--runtime-set testVersion 7.4-8.5`. No dependency change is needed in the plugin.

These checks validate runtime compatibility and local transport. Mailchimp receiver/observability QA remains separate: trigger a controlled live event, inspect the same header on its retries, and calculate `received_at - eligible_at` in Unix seconds. Confirm initial/manual sync requests omit it. This telemetry must never reject a delayed job.
