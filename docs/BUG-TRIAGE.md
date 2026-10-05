# Bug Triage Log

Evidence-based findings from **running** the plugin in the Stage 0 environment.
Each entry: symptom, reproduction, root cause, evidence, status.

Environment: WP (php8.2-apache, MySQL 8.0) at `http://localhost:8080`,
plugin `footnotes` v`2.8.0p` active, `WP_DEBUG=true`.

---

## TRIAGE-001 — Textdomain loaded too early (WP 6.7+ warning)  🔴 CONFIRMED

**Severity:** High — emits a PHP notice on **every admin page load**, and the output
it produces corrupts page rendering (breaks `wp_redirect`/headers).

**Symptom:**
```
Notice: Function _load_textdomain_just_in_time was called incorrectly.
Translation loading for the footnotes domain was triggered too early.
This is usually an indicator for some code in the plugin or theme running too early.
Translations should be loaded at the init action or later.
(added in WP 6.7.0)
```
Followed by:
```
Warning: Cannot modify header information - headers already sent by
(output started at .../wp-includes/functions.php:6260)
```

**Reproduction:**
```bash
curl -s "http://localhost:8080/wp-admin/" | grep _load_textdomain_just_in_time
# → matches; "Translation loading for the <code>footnotes</code> domain"
```
Also reproduced on `options-general.php?page=footnotes`. Fires on **any** admin load.

**Root cause:**
`includes/class-core.php:223`
```php
$this->loader->add_action( 'plugins_loaded', $i18n, 'load_plugin_textdomain' );
```
- The handler is `i18n::load_plugin_textdomain()` (`includes/class-i18n.php`), which
  wraps `load_plugin_textdomain( 'footnotes', ... )`.
- It runs on `plugins_loaded`, which is **before** `init`.
- WP 6.7 changed behaviour: calling `__()`/translation functions before `init` now
  triggers this notice. Because the plugin's own textdomain loads on `plugins_loaded`,
  any `__()` call in admin menu registration hits it.

**Fix direction:** move i18n loading to `init` (with a priority), or at minimum ensure
translations are not touched before `init`. Verify no admin strings are evaluated
earlier than `init`.

**Status:** Confirmed. Not yet fixed.

---

## TRIAGE-002 — No default options created on activation  🟢 COSMETIC (not a functional bug)

**Symptom:** After activation, no `footnotes_*` options exist.
```
$ wp option list --search='footnote*'
option_name  option_value
(empty)
```

**Root cause:**
`includes/class-activator.php` → `Activator::activate()` is a **NOP**:
```php
public static function activate() {
    // Nothing yet.
}
```
The four option groups (`footnotes_storage`, `footnotes_storage_custom`,
`footnotes_storage_expert`, `footnotes_storage_custom_css` — `class-settings.php:51`)
are never seeded. `get_option( 'footnotes_storage' )` returns `false`.

**Impact — resolved:** Settings resolve their own defaults when the option is missing.
Verified via wp-cli eval:
```
footnote_inputfield_search_in_excerpt          = 'manual'
footnote_inputfield_expert_lookup_the_content  = true
footnote_inputfield_expert_lookup_the_excerpt  = false
```
So the plugin renders with sane defaults and no saved options. **Not a functional bug.**
Downgraded to cosmetic; may still be worth seeding on activation for clarity.

**Separate observation:** `footnote_inputfield_combine_with_identical_notes` returns `NULL`
(key mismatch — the registered key differs from the one queried). Needs a follow-up check;
may be a genuinely dead/misnamed setting.

**Status:** Confirmed behaviour; **impact resolved — cosmetic only.**

---

## TRIAGE-003 — Nested shortcodes cause total parse failure  🔴 CONFIRMED

**Severity:** High — user-visible raw markup leaks onto the page; the whole post's
footnotes fail to process.

**Reproduction:**
```
<p>Outer claim. ((Outer footnote containing ((an inner footnote)) inside it.))</p>
```

**Observed output (post 6, verbatim from page):**
```
WARNING: unbalanced footnote start tag short code found. …
Unbalanced start tag short code found before: “Outer footnote containing”
Outer claim. ((Outer footnote containing ((an inner footnote)) inside it.))
```
- Tooltip count: **0** (nothing processed).
- Raw `((` present **twice** in the rendered body.
- No reference container emitted.

**Root cause (not yet pinpointed):** the syntax-validation guard detects the imbalance
and aborts, but instead of degrading gracefully it outputs the raw shortcodes. The
parser's regex approach (`public/class-parser.php`, 23 `preg_*` calls) cannot represent
nested delimiters — this is exactly upstream issue **#121** ("Refactor footnote parsing
to use DOM parsing rather than RegEx").

**Status:** **DEFERRED — deliberately not fixed in this pass.** See decision below.

**Deferral decision (recorded):**
A narrow fix was attempted — neutralising the raw delimiters on the error path so the
visitor stops seeing literal `(( ))`. It was abandoned and reverted. Rationale:

- It modifies the **error path** of the largest and most fragile file in the plugin
  (2,246 lines, 23 `preg_*` calls) — the highest-consequence region to touch.
- The benefit is **cosmetic only**: the page is already showing a warning box; hiding the
  delimiters does not make nesting work.
- The failure mode of getting it wrong is a **fatal error on every page containing
  unbalanced shortcodes** — a strictly worse outcome than the current cosmetic leak.
- Nested shortcodes are rare, and the behaviour is **inherited from upstream** (it was
  broken before this fork).

The real fix is the regex-to-tokenizer rewrite (upstream #121), which the roadmap
(Stage 5.6) already places after stability. Until then this stays documented, not patched.

**Maps to:** upstream #151 (nested shortcodes), #121 (DOM/tokenizer refactor).

---

## TRIAGE-004 — Multi-paragraph footnotes  🟢 NOT REPRODUCED (appears fixed)

**Reproduction:** note body containing a blank line (two paragraphs).

**Observed:** both paragraphs render inside the reference container, with referrer `[1]`
and backlink. No truncation.

**Conclusion:** upstream **#103** ("Premature end of note on paragraph separation")
appears **fixed** in this baseline (inherited from upstream PR #118).

**Status:** Not reproduced → treat as fixed; add a regression test.

---

## TRIAGE-005 — Apostrophe encoding (`â€™`)  🟢 NOT REPRODUCED

**Reproduction:** note containing `&#8217;` (curly apostrophe) and `&#8211;` (en dash).

**Observed:** no mojibake; the correct `&#8217;` entity is preserved end to end.

**Caveat:** the original #77 report likely required a specific charset misconfiguration
(e.g. a database/table charset mismatch). Our test DB is correctly UTF-8. Cannot call
this "fixed" on this evidence alone — only "not reproduced in a default UTF-8 install".

**Status:** Not reproduced in default config. Needs a targeted charset test to close.

---

## TRIAGE-006 — Footnotes in taxonomy term descriptions  🟢 NOT REPRODUCED (works)

**Reproduction:** footnote shortcode inside a category description, viewed on the
category archive.

**Observed:** tooltip generated, reference container rendered, description processed.

**Root cause of the fix:** `public/class-parser.php` registers a `term_description`
filter (`register_hooks()`), added upstream in 2.5.0 specifically for this case.

**Status:** Not reproduced → appears fixed. (Upstream #20.)

---

## Verified working (no bug)

- **Front-end parsing works.** A published post containing
  `((This is the first footnote.))` renders:
  - a superscript referrer (`footnote_plugin_tooltip_4_1_1`),
  - a tooltip element, and
  - a `footnotes_reference_container` with backlink symbol.
- **Plugin activates cleanly** — no fatal on activation with WP_DEBUG on.
- The only literal `((` left in output in normal posts is WordPress's own CSS `clamp()` —
  not unparsed shortcode (false alarm, dismissed).

---

## Architecture notes (from triage)

- **Hook registration is unusual.** `includes/class-core.php` registers only 3 public
  hooks (enqueue ×2, widgets). All *content* processing hooks live in
  `public/class-parser.php::register_hooks()`, called from the `Parser` **constructor**,
  which is instantiated inside `General::__construct()`. There is a standing `@todo
  Move to General` on both.
- **Content hooks are all settings-gated** (`the_title`, `the_content`,
  `term_description`, `pum_popup_content`, `the_excerpt`, `widget_title`,
  `widget_text`). A setting returning falsy silently disables a surface — worth
  remembering when diagnosing "footnotes don't appear on X".
- `get_setting_value()` is an O(n) linear search over sections (documented `@todo`).

---

## TRIAGE-007 — QTags undefined guard is unsafe  🔴 CONFIRMED

**Severity:** Medium — JS error in Classic Editor text mode under some editor orderings.

**Location:** `admin/partials/editor-button.html:38`
```js
if ( QTags ) {
    QTags.addButton( 'MCI_Footnotes_QuickTag_button', 'footnote', MCI_Footnotes_text_editor_callback );
}
```

**Problem:** `if ( QTags )` references a bare identifier. If `QTags` is not declared
(as when the Quicktags script hasn't loaded), this throws
`Uncaught ReferenceError: QTags is not defined` — it does **not** evaluate to falsy.
The guard therefore cannot do the job its comment claims.

**Fix direction:** use `if ( typeof QTags !== 'undefined' )`.

**Note:** the same file also still uses the legacy `MCI_Footnotes_*` function names and a
hard-coded `url: '/wp-admin/admin-ajax.php'` (breaks on subdirectory installs).

**Status:** Confirmed by inspection (JS semantics are deterministic here). Maps to #15/#193.

---

## TRIAGE-008 — Collapsed reference container  🟢 WORKS

**Reproduction:** set `footnote_inputfield_collapse_references = true`, load a post.

**Observed:** the reference div renders `style="display: none;"`, a `[+]` collapse
button is emitted, and a `footnote_expand_collapse_reference_container_*` handler is
wired up. Behaves as intended.

**Note:** an earlier grep for a `collapsed` CSS class gave a false negative — the markup
uses `footnote_reference_container_collapse_button` instead. Lesson: verify by rendered
output, not by guessing class names.

**Status:** Not reproduced → appears fixed. (Upstream #19.)

**Incidental observation:** a stray `speaker-mute` class appears on
`<div class="speaker-mute footnotes_reference_container">`. Not investigated; possibly a
shortcode/formatting artifact.

---

## TRIAGE-009 — AMP compatibility mode  🟢 WORKS

**Reproduction:** set `footnotes_inputfield_amp_compatibility_enable = true`, load a post.

**Observed:**
- `<style amp-custom>` emitted (correct AMP form), **and**
- `onclick=` occurrences: **0** (fully suppressed — AMP forbids inline handlers), **and**
- footnotes still render normally.

**Status:** Works. Confirms the baseline's AMP fixes function. Needs a real AMP-plugin
pass (Stage 1.9) for full sign-off, but the mechanism is sound.

---

## TRIAGE-010 — Scale / memory  🟢 NO ISSUE AT 250 NOTES

**Reproduction:** post with **250** footnote shortcodes.

**Observed:**
```
http=200 size=390551 time=0.066653s
distinct footnotes rendered: 250   (indices 1..250, none dropped)
reference rows: 250
```

**Status:** No performance or memory problem at this scale. The forum-reported
"PHP memory limit on a post with a lot of footnotes" does not reproduce at 250.
Would need 1000+ / low-limit conditions to chase further; not a priority.

---

## Not yet investigated

| Item | Issue | Status |
|---|---|---|
| #47 / #65 excerpts | ❓ still to reproduce |
| #21 / #81 / #132 theme conflicts | ❓ still to reproduce |
| `combine_with_identical_notes` = NULL | 🟡 follow-up (see TRIAGE-002) |
| AMP plugin end-to-end (Stage 1.9) | 🟡 mechanism verified, real AMP plugin pending |

---

## Triage tally (Stage 1 progress)

| Verdict | Count | Items |
|---|---|---|
| 🔴 Confirmed broken | 3 | textdomain-too-early, nested shortcodes, QTags guard |
| 🟢 Works / not reproduced | 6 | #103 paragraphs, #77 encoding*, #20 taxonomy, #19 collapse, AMP mode, scale |
| 🟢 Cosmetic | 1 | no defaults on activation |
| 🟡 Follow-up | 2 | NULL setting key, stray `speaker-mute` class |
| ❓ Remaining | 3 | excerpts (#47/#65), theme conflicts (#21/#81/#132), AMP-plugin-e2e |

*\* not reproduced in default UTF-8 config; not yet closed.*

**Headline:** the baseline is in considerably better shape than the upstream issue list
suggests. Of the bugs expected to be open, most already work. The genuinely broken items
are the WP 6.7 textdomain notice, nested-shortcode parsing, and the QTags guard.
