# Store URL registration (#51985)

Store creation and updates send the host from WordPress `home_url()` to the
Mailchimp e-commerce API. A Bedrock site with Site Address `https://example.org`
and WordPress Address `https://example.org/wp` registers `example.org`.
[Mailchimp's store API](https://mailchimp.com/developer/marketing/api/e-commerce/create-store)
uses a domain for Connected Sites.

Existing store IDs are reused and updated in place on the next store-settings
save. There is no disconnect/reconnect or automatic migration on plugin update.
When an ID must be recovered, lookup accepts the public host, its HTTP/HTTPS
root URL, and the exact legacy home/installation URLs. It does not collapse
other paths or `www` hosts into the same store. Recovery checks the remote
store's WooCommerce platform; conflict recovery retains its original audience.

This change does not modify WordPress URL options or filters, admin/login
addresses, OAuth service handshake fields, or Tower telemetry. Those service
payloads are separate from the e-commerce store domain. Public storefronts
actually served under a subfolder still have the Connected Sites subfolder
limitation; removing the WordPress core directory is not a promise of support
for path-specific Connected Sites.

## Automated verification

On an unconfigured, disposable localhost WordPress site with WooCommerce and
this plugin activated, without an external object cache:

```sh
MC_WOO_STORE_URL_LOCAL_QA=1 wp eval-file tests/integration/store-url-registration.php
```

The test exercises real WordPress URL functions/options, WooCommerce country
data, admin store synchronization, API serialization, and create/update
selection. Only remote responses are substituted; external HTTP and mail are
blocked. It covers normal, Bedrock, separate public/core hosts, subfolder/port,
filtered public URLs, legacy identity recovery, duplicate conflicts, unrelated
platforms/paths/hosts, API failures, and invalid public addresses.

CI runs WordPress 6.2 / WooCommerce 8.2.0 / PHP 7.4 and WordPress 7.0 /
WooCommerce 11.2.1 / PHP 8.2.

## Connected staging QA before release

- On a Bedrock-style site, complete connection/setup and verify the registered
  domain is the public host and Mailchimp can detect the frontend snippet.
- On an existing connection, save store settings and verify the store ID and
  audience remain unchanged, the domain is corrected, and no extra store is
  created. Do not disconnect to perform this check.
- Repeat on an ordinary root installation; check frontend, admin, and login
  URLs still work. Verify OAuth start/finish on the split-directory setup.
- Live Mailchimp verification and a complete browser OAuth flow are not covered
  by the automated fixture API.
