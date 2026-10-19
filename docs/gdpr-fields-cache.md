# Checkout GDPR field cache

Checkout reads field definitions locally. A cache miss schedules one unique
Action Scheduler refresh for the audience (WP-Cron is the fallback if Action
Scheduler is unavailable). The remote members request never runs in the reader.
Plugin settings views and audience setup use the same reader to prime the cache.
Jobs for disconnected stores or a former audience are ignored.

A successful refresh keeps a non-autoloaded, audience-specific last-known-good
copy and a fresh transient. The default freshness is five minutes; the existing
`mailchimp_checkout_overwrite_gdpr_cache_minutes` filter applies to refreshes.
After expiry, checkout renders the last-known-good definitions while refreshing
in the background. API failures or malformed responses retain that copy and
back off for 60 seconds. A later read requests another refresh. Refreshes depend
on the site's scheduler running normally.

With no successful cache, the getter returns `null`, distinct from a successful
empty array. Classic checkout and the newsletter Block default the newsletter
checkbox to unchecked in that case and render no invented GDPR permissions.
Customers can still explicitly choose the newsletter checkbox. Known empty
responses restore the merchant's normal newsletter default. Hidden newsletter
fields do not schedule GDPR requests. Existing subscriber suppression is retained.

The sampled member's permission choices are never used as checkout defaults:
cached definitions contain `enabled: false`. The shopper must select their own
GDPR permissions. Existing account-profile code can still display that account's
own permission choices separately. This change does not alter consent submission
or infer consent from a cache miss.

## Verification

```sh
php tests/regression/gdpr-fields-cache.php
MC_WOO_GDPR_LOCAL_QA=1 wp eval-file tests/integration/gdpr-fields-checkout.php
```

The integration requires an unconfigured disposable localhost WordPress site,
WooCommerce and this plugin, and no external object cache. It invokes the real
classic checkout renderer, newsletter Block data provider, shared admin priming,
SQL-backed Action Scheduler and cache APIs. Only the Mailchimp response is
substituted: a slow fixture proves checkout makes zero API calls while the worker
handles success/failure. Fixtures are cleaned up. CI covers WordPress 6.2 /
WooCommerce 8.2 / PHP 7.4 and WordPress 7.0 / WooCommerce 11.2.1 / PHP 8.2.
Live Mailchimp performance and third-party checkout customizations require staging QA.
