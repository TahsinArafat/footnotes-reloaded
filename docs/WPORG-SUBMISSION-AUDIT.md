# WordPress.org Submission Audit

Pre-submission review against the WordPress.org [Plugin Review Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
and [Plugin Handbook](https://developer.wordpress.org/plugins/).

**Verdict: not ready to submit.** Two blockers and several required fixes.

---

## 🔴 Blockers — would likely fail review

### B1. Text domain does not match the plugin slug

`footnotes.php:23` declares `Text Domain: footnotes`, but the plugin is
`footnotes-reloaded`.

WordPress.org requires the text domain to match the plugin's **slug** (the
directory name) for translations to load from translate.wordpress.org. A mismatch
means the plugin cannot be translated through the official system, which the
review team treats as a defect.

**Fix:** either
- (a) rename the text domain to `footnotes-reloaded` throughout (large: every
  `__()`/`_e()` call, ~200+ strings, plus the `.pot`), **or**
- (b) submit under the slug `footnotes-reloaded` and keep the domain as
  `footnotes` only if reviewed and accepted — this is not the recommended path.

Also note the slug `footnotes` itself is permanently closed, so the submitted
slug must be new.

### B2. `load_plugin_textdomain()` is called, and on `init`

`includes/class-i18n.php:39` + `includes/class-core.php:228`.

Since WordPress 4.6, plugins hosted on WordPress.org **must not** call
`load_plugin_textdomain()` — translations are loaded automatically. The handbook
says to remove it. It is not fatal, but reviewers routinely flag it.

Note: we deliberately moved this hook from `plugins_loaded` to `init` to fix the
WordPress 6.7 notice. **Removing the call entirely is the correct fix** and would
also make that whole class of bug moot.

---

## 🟠 Required fixes

### R1. Unescaped output in the settings UI

`admin/layout/class-engine.php:148–149`:
```php
href="?page=<?php echo Init::MAIN_MENU_SLUG; ?>&t=<?php echo $section_slug; ?>">
<?php echo $section->get_title(); ?>
```
- `$section_slug` should be `esc_attr()`.
- `$section->get_title()` should be `esc_html()`.
- `Init::MAIN_MENU_SLUG` is a class constant (safe) but should still be escaped
  for consistency.

`admin/layout/class-settings-page.php:111`:
```php
echo $args['description'] . '</td><td>';
```
Unescaped. Should be `wp_kses_post( $args['description'] )` (descriptions contain
intentional markup).

**Note:** several other `echo` sites flagged by a naive scan are *deliberate*
(`class-parser.php` emits generated HTML/CSS; `$template->get_content()` emits
rendered templates). These need review, not blind escaping.

### R2. Settings save has no explicit nonce verification

`admin/layout/class-engine.php` carries
`// phpcs:disable WordPress.Security.NonceVerification.*` and a standing
`@todo Review nonce verification`. The form does call `settings_fields( 'footnotes' )`,
which emits a nonce — but `save_settings()` never verifies it, and the settings
are **not** registered against the `'footnotes'` group (each setting registers
under its option-group slug, e.g. `footnotes_storage`), so the nonce emitted by
`settings_fields( 'footnotes' )` may not correspond to anything.

This needs a deliberate fix: add `check_admin_referer()` (or
`wp_verify_nonce()`), and confirm the group passed to `settings_fields()` matches
what is actually registered. **This is the highest-risk item in the audit** —
a settings-write path with an unverified nonce.

### R3. `$_POST` values stored without sanitisation

`admin/layout/class-engine.php:578`:
```php
$new_settings[ $setting_key ] = array_key_exists( $setting_key, $_POST ) ? wp_unslash( $_POST[ $setting_key ] ) : '';
```
`wp_unslash()` is not sanitisation. Values are written straight to the options
table. Custom CSS in particular is user-supplied and later `echo`ed
(`class-parser.php:714`) — this warrants an explicit `sanitize_*` /
`wp_kses` pass per setting type, or a documented justification for stored-raw
(which is defensible for custom CSS, but must be reviewed and stated).

### R4. `uninstall.php` does not clean up plugin data

Available for inspection; the plugin stores options
(`footnotes_storage`, `footnotes_storage_custom`, `footnotes_storage_expert`,
`footnotes_storage_custom_css`) and a widget (`widget_footnotes_widget`).
Uninstall should delete them, and ideally offer a keep-data setting.

### R5. Missing direct-access guards

Every class file under `admin/`, `includes/`, `public/` lacks an
`if ( ! defined( 'ABSPATH' ) ) exit;` guard. Reviewers flag this as a standard
requirement.

### R6. Inconsistent text domain in strings

Strings use the `footnotes` domain throughout. If B1 is fixed by renaming the
slug, every call site must change. Verify none use a wrong/variable domain.

---

## 🟡 Should fix before submitting

### Y1. Stale `.pot` file
`languages/footnotes.pot` has `POT-Creation-Date: 2014-10-18`. It predates
essentially all current strings. Regenerate it.

### Y2. Shipping compiled `.mo` files
`languages/*.mo` (5 locales) are committed. WordPress.org serves translations
from translate.wordpress.org; shipping `.mo` files is unnecessary and they can
drift out of sync with the `.pot`. Consider excluding `.mo` from the release zip
(keep `.pot`).

### Y3. Version-string provenance
`Plugin URI` and `Author URI` still point at `cheret.tech` / the original author.
That is correct for attribution, but `Plugin URI` is documented as the plugin's
**own** homepage. Consider pointing `Plugin URI` at the GitHub repo and keeping
attribution in `Author`/`Author URI`.

### Y4. `Requires at least: 5.4` with `Requires PHP: 8.0`
Not an error, but WP.org surfaces both. Confirm 5.4 is genuinely the floor given
the code uses PHP 8 features (`declare(strict_types=1)`, typed properties,
`?Type` nullable types, enum-like constants). A modern WP would be a more honest
floor.

### Y5. Known unfixed bug shipping in 2.8.0
Manual excerpts leak raw `((...))` under all three excerpt settings
(documented as TRIAGE pending in `docs/BUG-TRIAGE.md`). Reviewers do not inspect
for this, but users will hit it, and the readme changelog does not mention it.

---

## ✅ Clean

- Plugin header present and complete; all required fields parse
- GPLv3 licence declared, `LICENSE.txt` present, compatible with the original
- No bundled premium/locked features
- No phone-home / external tracking
- No obfuscated code; no minified-only source (`.min.css`/`.min.js` ship with
  unminified development counterparts in the repo)
- `readme.txt` has all required headers and is internally consistent
- No `eval()`, no base64-decoded payloads, no remote code execution
- SQL: no raw queries; all storage via the Options API
- AJAX handlers use `check_ajax_referer()`

---

## Recommended order

1. **B2** — remove `load_plugin_textdomain()` (also kills the 6.7 bug class)
2. **B1** — decide slug and align the text domain
3. **R2 / R3** — settings save: nonce + sanitisation (highest security risk)
4. **R1 / R5** — escaping and access guards
5. **R4** — uninstall cleanup
6. **Y1 / Y2** — regenerate `.pot`, stop shipping `.mo`
7. **Y3 / Y4** — metadata honesty
8. Re-run the WP.org readme validator, then submit

## Caveat on this audit

This is a static review. It used pattern scans, not the official
[Plugin Check](https://wordpress.org/plugins/plugin-check/) tool or a wp-env
review sandbox. **Before submitting, run Plugin Check** — it is the same
automated ruleset reviewers see first, and it will catch items this scan missed.
