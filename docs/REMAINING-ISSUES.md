# Remaining Issues

Consolidated, de-noised view of everything still outstanding — drawn from the
upstream repository's 89 issues and 141 PRs, cross-checked against this codebase.

**Why this document exists.** The upstream issue tracker is not usable as a work
queue: of 89 issues, most are workflow/documentation/dependency housekeeping, and
many of its real bugs are already fixed in this `3.0`-derived tree. This filters
to what actually matters and states each item's true status.

Sourced from `markcheret/footnotes` (all issues closed; repo dormant since 2023).

---

## Open bugs

Only these remain genuinely broken. Everything else from the upstream bug list is
either already fixed here or was never reproducible.

| # | Issue | Status | Notes |
|---|---|---|---|
| #47 | Footnotes in post excerpts | ❓ **reproduce** | Three-way setting exists (`yes`/`no`/`manual` → `generate_excerpt_with_footnotes` / `generate_excerpt` / `exec`). Never reproduced. See TRIAGE pending. |
| #65 | Auto-excerpts even when manual defined | ❓ **reproduce** | Same code path as #47. Likely the same root. |
| #77 | Encoding turns `'` into `â€™` | 🟡 **needs charset repro** | Not reproducible on a correct UTF-8 install. Needs a DB/table charset mismatch to confirm or close. |
| #132 | Conflict with PickPlugins Accordions | ❓ **reproduce** | Needs the third-party plugin installed. |
| #21 | Breaks mobile menu | ❓ **reproduce** | Theme CSS conflict; needs a themed install. |
| #81 | Breaks widget areas (Hueman theme) | ❓ **reproduce** | 16 `!important` rules ship in the CSS; conflict plausible. |
| #18 | "Invisible wall" on Plugins menu | ❓ **reproduce** | CSS/z-index; needs visual check. |
| #190 | `wpml-config.xml` stale | 🟡 **known stale** | File lists only 2 keys; does not cover current settings. Straightforward fix. |
| #151 | Nested shortcodes | ⛔ **deferred** | Documented in `BUG-TRIAGE.md`. Real fix is #121 below. |

**Already fixed here** (verified, see `BUG-TRIAGE.md`): #15/#193 QTags, #189 init
hook, #168 array index, #46 version display, #33 load order, #71 `Task::wp_head`,
#55 packaging, #103 paragraphs, #19 collapse, #20 taxonomy, #151-adjacent.

**Cosmetic**: #176 ("security: Update merge") — a stale PR title, not a real issue.

---

## Real work worth doing

### Release-blocking
| Ref | Item | Why |
|---|---|---|
| #190 | Update `wpml-config.xml` | Ships wrong config to users. Small fix. |
| — | Slug / distribution decision (D3, D4) | Cannot publish without it. |

### High value, moderate effort
| Ref | Item | Why |
|---|---|---|
| #44 / #85 | Replace jQuery / jQuery Tools | `jquery.tools.min.js` still bundled. Legacy, unmaintained dependency; the alternative plain-JS mode already exists. |
| #31 / #119 | Testing infra + coverage | Partially done: PHPUnit + WitDiff harness now exist. Needs CI. |
| #140 | Auto-validate `readme.txt` | Prevents an embarrassing WP.org submission failure. |
| #121 | DOM/tokenizer parser rewrite | Fixes nesting (#151) and hardened against regex fragility generally. Large. |
| #169 | Proper env values | `PRODUCTION_ENV` and the version are hard-coded in multiple places. |

### Nice to have
| Ref | Item |
|---|---|
| #16 / #111 | Pre-defined reference-container styles / styling setting |
| #53 | Settings reset button |
| #109 | Convert footnotes from other plugins |
| #107 / #108 | Usage stats / opt-in sharing |
| #128 | French translation |
| #161 | Declutter settings page |
| #186 | WordPress 5.8 compatibility check |
| #137 | Separate changelog file |

### Explicitly not doing (housekeeping, already satisfied or irrelevant)
`#8` Composer setup (done), `#10`/`#35`/`#41`/`#97` WPCS/docblocks (the `3.0` branch
used type declarations and namespaces already), `#11` pre-commit hook, `#99`/`#119`
partials, `#159` staging env (Docker stack now covers it), `#107` stats.

---

## Working method for the ❓ items

Every `❓ reproduce` above has the same problem: **it cannot be resolved by reading
code.** They need interactive reproduction — configuring settings in wp-admin,
installing a theme or third-party plugin, viewing the result. That is a different
kind of work from the read-test-fix loop used so far.

Recommended: one focused session per cluster (excerpts together; theme conflicts
together), with the running Docker instance, recording findings in
`BUG-TRIAGE.md` exactly as the earlier items were.
