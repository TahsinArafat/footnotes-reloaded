# footnotes — Relaunch Gap Analysis

**Status of this document:** initial reconnaissance, 2025.
**Codebase analysed:** `footnotes/` at commit `6912543` (baseline import, `2.8.0p`).

---

## 1. Background

| Fact | Detail |
|---|---|
| WordPress.org slug | `footnotes` |
| WP.org status | **CLOSED 2022-11-14**, reason *"Author Request"*, closure **permanent**, not downloadable |
| Upstream repo | https://github.com/markcheret/footnotes |
| Last `main` commit | 2023-06-15 (v2.7.3) |
| Open issues / PRs upstream | **0 / 0** (89 issues + 141 PRs all closed) |
| Revive attempt | **PR #250** (`2.8.0`, closed 2026-01-23) — version-string bump only, no fixes |
| Our base | upstream **`3.0` branch** (2021-08-12, `2.8.0d`) — the namespace rewrite upstream *never shipped* — flattened to plugin root + hand-applied fixes |

**Key insight:** the `3.0` branch is a more modern codebase (namespaced, PHP 8, typed) than
`main`, but it is also *older in wall-clock time* and was never released. Our local tree is
`3.0` + fixes. Two upstream lines therefore exist, and **we must decide which is authoritative**.

---

## 2. What is already fixed in our baseline

Verified by diffing local files against the `3.0` branch:

| Area | Fix | Evidence |
|---|---|---|
| Bootstrap | Activation/deactivation hooks actually register; `run_footnotes()` no longer called eagerly on load | `footnotes.php:123` uses `'footnotes\run_footnotes'` string callback |
| Bootstrap | Upstream `3.0` had both `register_activation_hook` calls commented out as broken | removed TODO block |
| Parser | Footer loop that could never terminate | `while ( 1 === preg_match(...) )` (was `0 !==`) |
| Parser | Infinite-loop guard in footnote replacement (position always advances) | `$pos_start += max( strlen($repl), $orig_len )` |
| Parser | Incorrect boolean `strpos()` checks | `false === strpos(...)` / `false !== strpos(...)` |
| Parser | Arrow-setting array index | `?? 0` (was `|| 0`) — closes #168 |
| Security | AJAX `get_plugin_meta_information`: nonce check, `sanitize_text_field`, `wp_die()`, HTTP status check | `admin/layout/class-init.php` |
| Security | Exposed `wp_ajax_nopriv_footnotes_get_plugin_info` endpoint removed | `class-init.php:88` deleted |
| Security | Editor nonce localised for JS | `admin/class-admin.php:117` |
| AMP | `<style amp-custom>` emitted in AMP mode; `onclick` suppressed | `public/class-parser.php` |

These are real, non-trivial fixes. They are the reason our tree is worth keeping.

---

## 3. Upstream bug issues — status vs. our code

20 issues carry the `bug` label. Assessment against our current tree:

| # | Title | Status in our tree | Notes |
|---|---|---|---|
| #15 | "QTags is not defined" | ⚠️ **Uncertain** | `editor-button.html` still tests global `QTags` (`if ( QTags )`). No guard against undefined global → can still throw. |
| #18 | Invisible wall on Plugins menu | ❓ Unverified | CSS/z-index issue; needs runtime test. |
| #19 | Collapsed References Container | ❓ Unverified | Collapse logic present (`class-parser.php:2160+`) but not tested. |
| #20 | Footnotes don't work with taxonomy terms | ❌ **Still open** | No `is_tax`/`is_archive`/`is_category` handling found in `public/` or `includes/`. |
| #21 | Plugin breaks mobile menu | ❓ Unverified | Theme-CSS conflict; needs runtime test. |
| #33 | Fatal error from PHP file load order | ✅ **N/A** | `3.0` uses explicit `require_once`; no glob-based autoloading. |
| #46 | Dashboard reports wrong version | ✅ **Likely fixed** | `class-core.php:99` reads `PLUGIN_VERSION` with fallback. |
| #47 | Footnotes shown in excerpts | ⚠️ **Partial** | Excerpt settings exist (`class-excerptssettingsgroup.php`, 3-way Yes/No/Manual). Default & actual behaviour unverified. |
| #55 | Broken dev version released | ✅ **N/A** | Historical packaging incident. |
| #65 | Auto-excerpts even when manual defined | ⚠️ **Partial** | `search_in_excerpt` has a `manual` mode; whether it fully honours manual excerpts unverified. |
| #71 | `undefined method Task::wp_head()` | ✅ **N/A** | `Task` class gone in `3.0` architecture. |
| #77 | Encoding turns `'` into `â€™` | ❌ **Still open** | No `utf8`/`mb_convert`/entity handling in parser. Real risk on mis-declared charsets. |
| #81 | Breaks widget areas (Hueman theme) | ❓ Unverified | 16 `!important` rules in shipped CSS; conflict plausible. |
| #103 | Premature end of note on paragraph break | ⚠️ **Partial** | Upstream PR #118 targeted this; our parser derived from that line, but multi-paragraph handling unverified. |
| #132 | Conflicts with PickPlugins Accordions | ❓ Unverified | Needs runtime test. |
| #151 | Breaks on nested footnote shortcodes | ❌ **Still open** | No nesting protection found in parser. |
| #168 | `string` passed as array index | ✅ **Fixed** | `?? 0` at `class-parser.php:1728`. |
| #189 | Plugin initialisation on `plugins_loaded` | ✅ **Fixed** | Correct hook string callback. |
| #190 | Update `wpml-config.xml` | ⚠️ **Partial** | Config exists but lists only 2 keys; likely stale vs. current settings. |
| #193 | Fix "QTags is not defined" | ⚠️ **Same as #15** | See #15. |

**Tally:** ✅ fixed/N-A: 6 · ❌ still open: 3 · ⚠️ partial/uncertain: 6 · ❓ unverified: 5.

The ❓ rows are the critical gap — they cannot be resolved by reading code, only by running it.

---

## 4. Engineering / quality issues (from closed issues, still relevant)

These are the upstream team's own backlog items; several remain true of our tree:

| # | Item | State |
|---|---|---|
| #121 | Refactor parser from RegEx to DOM parsing | ❌ Parser is 2,246 LOC with 23 `preg_*` calls. ReDoS/robustness risk remains. |
| #44 / #85 | Replace jQuery / jQuery Tools | ❌ `public/js/jquery.tools.min.js` still shipped; jQuery dependency optional but present. |
| #31 / #119 / #198/#199 | Automated testing + coverage | ❌ Only `tests/exampleTest.php` exists. No CI here. |
| #140 | Auto-validate `readme.txt` | ❌ Not set up. |
| #88 / #96 | Automate CSS/JS minification; separate code | ⚠️ `.min.` assets are committed; no build pipeline in our tree. |
| #169 | Use proper env values | ⚠️ `PRODUCTION_ENV` still hard-coded `true` (upstream TODO notes env-file). |
| #140/#142/#117 | Release workflow / SVN assets / changelogs | ❌ None; no `.github/` workflows locally. |

---

## 5. Live WordPress.org forum complaints (153 topics collected)

Themes reported by real users, ranked by frequency/recurrence:

1. **Plugin is abandoned** — the single most common thread; the relaunch itself answers this.
2. **AMP pages broken** — *"Not working properly on AMP pages"*. Our tree has AMP `<style amp-custom>` fixes — needs verification.
3. **"Links are not crawlable"** — SEO/crawlability of reference links. Not addressed.
4. **Footnotes appear as literal text on mobile** — parser/JS not running on some mobile setups.
5. **jQuery dependency / "jQuery text"** — ties to #44/#85.
6. **PHP memory limit with many footnotes** — parser scalability (#121).
7. **GravityView / bbPress / custom fields** — footnotes not processed on non-`the_content` surfaces.
8. **Two sections / widget + end-of-post** — feature requests.
9. **CSS layout nits** — wrapping numbers, horizontal lines, backlink symbol placement.

Full list: see `docs/forum-topics.tsv`.

---

## 6. Licensing & identity

- Plugin is **GPLv3**. Relaunch must preserve upstream credits (Mark Cheret et al.).
- The `footnotes` WP.org slug is **permanently closed** and cannot be reused.
  A relaunch needs **a new slug** (e.g. `footnotes-reloaded`) unless ownership can be
  transferred to the original author and they reopen it — this is a conversation with
  the plugin team, not a technical task.
- Any relaunch that reuses the name must be clear about its relationship to the original.

---

## 7. Recommended priority order

**P0 — can't ship without:**
1. Decide authoritative codebase (recommend: this `3.0`-based tree) and pin it.
2. Stand up a local WordPress to *run* the plugin. Five of the bugs above are unverifiable by reading.
3. Resolve the ❌/⚠️ parser bugs: #20 taxonomy, #77 encoding, #151 nesting, #103 paragraphs.
4. Confirm excerpt behaviour (#47/#65) with real content.

**P1 — ship-quality:**
5. `readme.txt`: truthful "Tested up to", current WP/PHP minimums, changelog.
6. `wpml-config.xml` refresh (#190).
7. Remove/replace jQuery Tools (#44/#85).
8. Resolve QTags global guard (#15/#193).

**P2 — sustainability:**
9. Test suite + CI (#31/#198).
10. Parser robustness / possible DOM rewrite (#121).
11. Release automation (#117/#140).

---

## 8. Open questions for the maintainer

1. **Which base is canonical** — our `3.0`-derived tree, or rebase onto `main` 2.7.3?
2. **Plugin name/slug** — new slug, or pursue reopening `footnotes`?
3. **Testing environment** — Docker, `wp-env`, or an existing site?
4. **Scope of "fix all the issues"** — bugs only, or also the P1/P2 backlog?
