# Output escaping for account details and checkout consent text (sc-53128)

Follow-up to the QIT triage in `qit-security-51970.md` (rows 160, 163, 183,
185, 186, 235, 273). These were source-level escaping gaps; no exploitable XSS
was shown. Every value involved is set by an administrator, by plugin code, or
by the merchant's own Mailchimp audience.

## Disposition per output

| Output | Source of the value | Now |
| --- | --- | --- |
| `admin/v2/templates/confirmation/header.php` admin email | Plugin/WordPress admin email | `esc_html()` |
| `admin/v2/templates/confirmation/tabs/advanced.php` admin email | Same | `esc_html()`; a stray `</a>` after it was removed |
| `admin/v2/templates/connect-accounts/header.php` site name | `blogname` | `esc_html()` on the name; the heading is a translatable string with a `%s` placeholder and only its two `<br>` survive (`wp_kses`) |
| `admin/v2/templates/confirmation/tabs/store-info.php` checkout links | Checkout page permalink / fixed docs URL | `esc_url()`, quoted, whole sentence through `wp_kses_post()`; translation strings unchanged |
| `admin/v2/templates/confirmation/tabs/store-info.php` opt-in option labels | `mailchimp_checkout_opt_in_options` filter | `esc_html()` — labels are plain text |
| `includes/class-mailchimp-woocommerce-newsletter.php` newsletter label | Merchant setting `newsletter_label` | `wp_kses()` with `mailchimp_newsletter_label_allowed_html()` |
| Same file, marketing-permission headline, ids and text | Translation / Mailchimp audience | `esc_html__()`, `esc_attr()`, `esc_html()` |

## Intentionally trusted HTML

- **Newsletter label.** Merchants put privacy-policy links and a line break in
  it. The settings screen already filters it to `<a href title target>` and
  `<br>` on save; the same allowlist (one shared helper) is now applied when
  it is printed. Plain-text escaping would strip those links from every store.
- **`mailchimp_woocommerce_newsletter_field` and
  `mailchimp_woocommerce_sms_consent_field` filter results.** The final markup
  contains form inputs (and, for SMS, an inline script) and is handed to
  site code through a filter. It is printed as returned: a filter callback is
  code running on the site, not merchant or provider text.
- **SMS consent markup.** Not changed. The label, phone value and disclaimer
  (which carries the audience name) are already escaped piece by piece with
  `esc_html()` / `esc_attr()`; the rest is fixed markup.

## Not covered

- The checkout **blocks** print the block text and marketing-permission text
  with `dangerouslySetInnerHTML`. They are outside the flagged rows and
  changing them needs a block rebuild; not touched here.
- The SMS disclaimer still builds the audience name into its translatable
  string, so it cannot be translated. Left alone because the text is
  compliance wording.

## Verification

```sh
php tests/regression/checkout-output-escaping.php
```

Fails on the previous newsletter code. Also rendered on the local store
(`woocommerce.test`, values injected in-process, nothing saved) with a label
of `Subscribe <b>now</b><script>alert(1)</script><br>See our <a href="…"
target="_blank" onclick="x()">privacy policy</a>` and a permission text of
`Email <img src=x onerror=alert(1)> & post`: the link and line break rendered,
the script, `onclick` and `<b>` did not, and the permission text showed as
literal text. The admin expressions were rendered the same way with a site
name, email and URL containing markup.

Still for QA on a connected store: classic checkout and My Account show the
label with link and line break, permission choices show, and ticking the box
subscribes. Block checkout should be unchanged.
