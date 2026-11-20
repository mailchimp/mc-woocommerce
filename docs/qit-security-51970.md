# QIT report triage and Woo API-key authorization (#51970)

Compared `mailchimp-6.2-qit-security-findings.csv` from Shortcut #51970 against
6.4 commit `d380f1b0cd333cdd929f7712da09139b41953b87` on 2026-10-19.
Report SHA-256: `91b203591cfcf335857712301d326dad71b044d8d35368d372d8a3267b370f31`.

## Report disposition

The report contains 313 scanner findings: 298 PHPCS and 15 Semgrep. The numeric
scanner severities are not exploitability scores. A whitespace-normalized
comparison of every reported code snippet found:

| Source comparison | Findings | Meaning |
| --- | ---: | --- |
| Original source file removed | 136 | Seven legacy `admin/partials/` files no longer exist; replacement UI still needs its own review. |
| Original snippet still present | 174 | Requires context review; presence alone does not establish a vulnerability. |
| Original snippet changed | 3 | Cart lookup/save code in the service changed; do not treat old line numbers as current findings. |

The sole severity-10 Semgrep finding is `WP_User_Query` in the private
`get_customer_count()` method in the REST class. There are no callers or route
bindings to that method in 6.4. A reachable email-search leak was not established;
adding a search restriction to dead code would not address a demonstrated issue.

The 14 informational public-route findings require reviewing handler-level
authorization. Several handlers already enforce Tower/webhook tokens. Ping and
session pixel routes are intentionally public. The three Woo-key routes share
an authorization function that checked credentials but not key scope or owner
capability. This is the highest-priority issue reproduced during this pass.

Other examples requiring context: the current log viewer escapes log text,
breadcrumb attributes are escaped before output, and SQL values flagged by the
scanner are often already passed through `$wpdb->prepare()`. These observations
are not a blanket clearance of the other findings. Remaining output escaping,
input validation, redirects, SQL, debug-code and route findings still require
triage. The full hosted QIT suite was not rerun; this PR fixes one confirmed
permission-boundary issue, not the entire report.

## Confirmed issue and fix

On unchanged 6.4, a real administrator-owned **read-only** WooCommerce API key
received HTTP 200 from the remote-support toggle and reached the outbound Tower
request. The disposable test intercepted HTTP, so no real support service was
changed. Key owners' current plugin capabilities were also not checked.

Authorization now runs in REST permission callbacks before the handlers:

| Route | Required key scope |
| --- | --- |
| `tower/get_store_id` (GET) | `read` or `read_write` |
| `tower/subscriber_stats` (POST, read-only operation) | `read` or `read_write` |
| `tower/toggle_remote_support` (POST) | `write` or `read_write` |

Every route additionally requires the key owner to retain the plugin's allowed
capability. The existing shop-manager setting and capability filter still apply.
The capability helper accepts an optional user ID so API authorization does not
use or replace the caller's logged-in user. Existing callers without an ID keep
their current-user behavior.

Secrets are compared exactly with `hash_equals()` after looking up the hashed
consumer key, avoiding case-insensitive SQL secret comparisons. Missing or
invalid credentials return 401; valid credentials with insufficient authority
return 403. Bearer `consumer_key:consumer_secret` and existing `auth` query-token
transport remain supported. No credentials are logged. Other Tower/webhook token
contracts are unchanged. Permission callbacks return `WP_Error` through normal
[WordPress REST error handling](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#permissions-callback).

## Verification and remaining QA

Run only on an unconfigured disposable localhost WordPress/WooCommerce site
without an external object cache:

```sh
MC_WOO_QIT_LOCAL_QA=1 wp eval-file tests/integration/tower-woo-key-permissions.php
```

The 63-check integration regression uses real REST dispatch, WooCommerce API-key
rows, WordPress users/capabilities/options, and local subscriber queries. Only
remote responses are substituted; all external HTTP and mail are blocked.
Fixtures are removed and previous options/current user are restored afterward.
Coverage includes read/write/read-write scopes, configured shop-manager access,
customer and demoted/orphaned owners, revocation, custom capability filters,
missing/malformed/wrong-case secrets, both token transports, and rejected writes
leaving local/remote support state untouched.

The regression fails on unchanged 6.4. It passes locally on WordPress 6.2 /
WooCommerce 8.2.0 / PHP 7.4 and WordPress 7.0 / WooCommerce 11.2.1 / PHP 8.4.
CI covers those WordPress/WooCommerce pairs on PHP 7.4 and 8.2.

Before release, exercise Tower's existing connection with a write-capable key
owned by a user allowed to manage the plugin. Verify enabling/disabling support
and read endpoints still work, then confirm a read-only key cannot toggle
support. Integrations whose key owner lost permissions will now receive 403;
repair the intended owner/scope rather than widening unrelated users' access.
Live Tower staging and a hosted QIT rerun remain unverified.
