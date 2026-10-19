# Order sync history (WooCommerce first phase, Shortcut 40516)

Order editors have a read-only **Mailchimp order sync history** panel in both
the legacy order editor and the HPOS order editor. It records decisions from
`MailChimp_WooCommerce_Single_Order`; it does not change sync, consent, retry,
or queue behavior. Customer-level history and the Shopify widget are separate
follow-up scope. This is a merchant/admin feature, not Tower internal docs.

The panel shows up to 20 recent events, newest first, with the site-local time,
the outcome, the reason, the WooCommerce status at that attempt, and whether it
was an initial or live sync. Refresh the order page after an attempt.

Covered decisions include configuration, excluded order types/user roles,
existing-members-only checks, update-only requests without a remote order,
checkout drafts, order status rules, invalid email, Amazon/privacy restrictions,
subscribers-only settings, financial status restrictions, empty API responses,
accepted order creates/updates, scheduled rate-limit retries, API/server errors,
and customer-replacement recovery. An accepted order followed by an error is
recorded as separate events; an attempt error does not prove the order is absent
from Mailchimp. If an order cannot be loaded, no order metadata can be written.

Reasons describe the actual branch taken. `processing` is not universally
excluded by current code. Double opt-in alone is not an order skip rule; order
sync and audience subscription are distinct. This panel does not poll Mailchimp
or certify the current audience status, automation delivery, or queue health.
No record means no recorded evidence, not that an order never synced. Old
attempts are not backfilled from raw plugin logs.

## Storage and access

Each event is a separate private `_mailchimp_woocommerce_sync_history` order
metadata row, written through WooCommerce CRUD APIs for either storage mode.
While history is persisted, a request-local guard prevents the recorded order's
HPOS metadata-save hook from queuing another Mailchimp sync (sc-52738). The guard
is per order, survives nested history writes, and clears even on an exception.
On WooCommerce 8.8+, the HPOS save-after-meta-change filter also prevents an
unnecessary full order save and modified-date change. Each write owns its filter
callback, preserving outer writes and filters installed by other callers. Older
WooCommerce versions can still perform the full save; the per-order guard
prevents it from creating a new Mailchimp job. Later edits and status
changes, and updates to other orders, still sync normally.
Pruning removes the oldest rows by metadata ID; the panel always displays at
most 20. Concurrent attempts can temporarily retain more than 20 rows until a
later write prunes them. This is bounded recent diagnostic history, not an
immutable audit ledger. Order deletion removes its metadata; uninstall alone
does not delete retained order history. No new table, migration, REST endpoint,
browser script, scheduled task, or additional Mailchimp request is introduced.

Only users with order-edit permissions can see the panel, with an additional
per-order permission check before loading history. Events contain fixed reason
codes and order status/mode/time only: no customer emails, names, addresses,
member IDs, request/response bodies, credentials, or raw exception messages.
Output is escaped, and malformed records show an explicit unavailable entry.
History-load failures show an error rather than an empty history. Storage
failures are logged without exception content and must not fail the sync job.

## Verification and release acceptance

Run the credential-free regression harness:

```sh
php tests/regression/order-sync-history.php
```

It exercises the real order job with fixture API/order adapters, including skip,
success, error/recovery, preservation of return values/exceptions/retries,
retention, permission denial, corrupt data, and persistence failure. It makes
no external API requests. This does not replace real WooCommerce CRUD/UI QA.

The real CRUD/permission fixture script can be run only on an unconfigured
disposable localhost site, with an explicit environment guard:

```sh
MC_WOO_HISTORY_LOCAL_QA=1 wp eval-file tests/integration/order-sync-history.php
```

It creates local fixture orders and role users, blocks email sending in that
invocation, and leaves fixtures for browser inspection. It never connects a
Mailchimp account.

Run the queue-loop integration test on the same disposable site, in separate
WP-CLI invocations for HPOS and legacy storage (and HPOS compatibility sync):

```sh
MC_WOO_HISTORY_LOCAL_QA=1 wp eval-file tests/integration/order-sync-history-loop.php
```

This uses real WooCommerce orders, persisted job payloads and Action Scheduler.
It ages the order's modified date and expires the enqueue debounce before
executing the job, so same-second behavior cannot hide the HPOS loop. A normal
order-type exclusion exercises the sync job's history path without contacting
Mailchimp; a test API adapter rejects any attempted network call. It checks
that history inserts/pruning do not requeue the order, genuine edits and status
changes still queue, another order can sync during recording, nested writes
retain the outer guard and save filter, existing filters survive cleanup, and
a persistence exception does not suppress subsequent edits. Fixtures are removed
afterwards.
It does not validate Mailchimp acceptance or delivery. The GitHub workflow runs
this on WooCommerce 8.2.0/PHP 7.4 and WooCommerce 11.2.1/PHP 8.2.
Compatibility mode checks cover the queue loop and unrelated backfilled metadata.
WooCommerce 8.2 can omit history rows from its compatibility postmeta copy,
including after repeated pruning; this was also reproduced with plain
WooCommerce CRUD without this guard. This change does not repair that older backfill behavior;
the authoritative HPOS history still retains 20 entries.

Before releasing the plugin, verify both HPOS and legacy order storage in a
disposable WordPress/WooCommerce installation: metadata persistence and pruning,
rendering of the order panel, admin/shop-manager access, customer denial, an
empty history, and an unconfigured-plugin attempt. Then Jordan should run
connected QA against a disposable Mailchimp audience for an accepted order,
a blocked order, a retry/error, and the recorded reason matching the actual
outcome. A Tower Vapor deployment does not release this plugin feature.
