# footnotes — Relaunch Roadmap

**Purpose:** take the abandoned `footnotes` plugin from an unshipped `3.0`-derived tree to a
maintained, published, working release — in verifiable stages.

**Companion docs:** `GAP-ANALYSIS.md` (current state), `forum-topics.tsv` (user-reported issues).

---

## Guiding principles

1. **Prove, don't assume.** A bug is only "fixed" when a test or a manual reproduction shows it.
2. **One reversible step at a time.** Every stage leaves the tree working and committed.
3. **No rewriting for its own sake.** The `3.0` parser is ugly but functional; fix it before replacing it.
4. **Ship early, ship often.** A working 2.8.0 relaunch beats a perfect 3.0 that never lands.

---

## Stage 0 — Foundations  *(prerequisite for everything)*

**Goal:** a reproducible environment, a runnable plugin, and a known-good baseline.

| # | Task | Deliverable | Exit criteria | State |
|---|---|---|---|---|
| 0.1 | Decide canonical base (recommend: this `3.0`-derived tree) | Decision recorded in `docs/DECISIONS.md` | Written & committed | ✅ done |
| 0.2 | Local WordPress via Docker Compose (`wordpress` + `mysql` + `wp-cli`) | `docker-compose.yml` in repo | `docker compose up` serves a WP site on localhost | ✅ done — `localhost:8080` |
| 0.3 | Symlink/mount plugin into `wp-content/plugins` | plugin appears in WP admin | Plugin activates without error | ✅ done — activates cleanly, no fatal |
| 0.4 | Seed fixture content: post with 1 / few / many footnotes, multi-paragraph note, nested shortcode, excerpt | WP import or `wp` CLI seed script | Fixtures reproducible from scratch | ✅ done — `dev/setup.sh`, `dev/seed.sh` |
| 0.5 | Establish PHPUnit + WP test suite | `phpunit.xml`, `tests/` running | `composer test` executes (even if few tests) | ⬜ not started |
| 0.6 | Adopt toolchain configs from `3.0` branch | `phpcs.xml.dist`, `rector.php`, `.phpcs` | Linters run; baseline violations recorded | ⬜ not started |

**Estimate:** 1–2 working sessions. **Actual:** core of 0.1–0.4 completed in one session;
0.5–0.6 deferred.
**Risk:** WP test suite bootstrap can be fiddly; timebox it and fall back to manual testing if needed.

---

## Stage 1 — Triage the unknown  *(the highest-value stage)*

**Goal:** turn the ❓ rows in `GAP-ANALYSIS.md` §3 into facts, by *running* the plugin.

Five bugs cannot be resolved by reading code: **#18, #19, #21, #81, #132**. Plus the ❌
open bugs **#20, #77, #151** and the ⚠️ partials **#47, #65, #103**.

| # | Task | Method | Output |
|---|---|---|---|
| 1.1 | Reproduce #20 taxonomy terms | Put a footnote on a category/tag archive | Confirmed yes/no + stack trace |
| 1.2 | Reproduce #77 encoding (`'` → `â€™`) | Post with curly quotes under non-UTF8 declaration | Confirmed + minimal case |
| 1.3 | Reproduce #151 nested shortcodes | `((note with ((inner)) inside))` | Confirmed + parser trace |
| 1.4 | Reproduce #103 paragraph separation | Multi-paragraph footnote | Confirmed + where it truncates |
| 1.5 | Verify #47 / #65 excerpt behaviour | Manual excerpt vs auto excerpt, all 3 settings | Behaviour matrix |
| 1.6 | Verify #15 / #193 QTags guard | Classic editor, text mode, plugin ordering | Confirmed / not |
| 1.7 | Test theme conflicts #21 / #81 | Install Hueman theme, check widget areas | Confirmed / not |
| 1.8 | Test #19 collapsed container | Set collapse-default, load page | Confirmed / not |
| 1.9 | Test AMP endpoints | AMP plugin, view post | Confirm AMP fixes work |
| 1.10 | Memory/scale (#forum) | Post with 200+ footnotes, measure peak memory | Baseline number |

**Deliverable:** `docs/BUG-TRIAGE.md` — every item marked reproduced / not / N-A with evidence.
**Exit criteria:** no ❓ rows remain; the Stage 2 list is final and evidence-backed.

---

## Stage 2 — Fix the confirmed bugs

**Goal:** close every reproduced bug. Ordered by severity, not by issue number.

| Wave | Items | Rationale |
|---|---|---|
| 2A — correctness | #20 taxonomy, #151 nesting, #103 paragraphs | Parser drops or corrupts output — data loss |
| 2B — encoding | #77 | Silent text corruption affects all content |
| 2C — excerpts | #47, #65 (per 1.5 matrix) | Common real-world complaint |
| 2D — editor | #15 / #193 QTags guard | Admin-side JS error |
| 2E — theme compat | #21, #81, #132 (+ #18, #19 if confirmed) | Ship `!important`-free / namespaced CSS |
| 2F — AMP/SEO | AMP re-verify, "links not crawlable" | Forum top concerns |

**Rules for this stage:**
- **One bug, one commit**, with a regression test where feasible.
- No opportunistic refactoring — that goes in Stage 5.
- After each wave: re-run fixtures + full test suite; commit.

**Exit criteria:** all Stage 1 confirmed bugs fixed and regression-covered.

---

## Stage 3 — Release readiness

**Goal:** make the plugin legitimately publishable.

| # | Task | Detail |
|---|---|---|
| 3.1 | Version bump & single source of truth | Decide `2.8.0` vs `3.0.0`; stop hard-coding version in 3 places (header, `PLUGIN_VERSION`, JS) |
| 3.2 | `readme.txt` rewrite | Truthful `Requires at least` / `Tested up to` / `Requires PHP`; accurate changelog; contributors preserved |
| 3.3 | Settings migration safety | Confirm defaults + upgrade path from 2.7.3 option names |
| 3.4 | `wpml-config.xml` refresh (#190) | Cover all current settings keys |
| 3.5 | `uninstall.php` review | Ensure clean removal incl. widgets/options |
| 3.6 | Asset cleanup | Drop `jquery.tools.min.js` if unused (#44/#85); verify `.min` files current (#88) |
| 3.7 | Security pass | Final audit: nonces, caps, escaping, `$wpdb`, direct-access guards |
| 3.8 | a11y pass | Reference container semantics, contrast (#upstream bug reports) |
| 3.9 | Reproducible build | `.zip` build script: exclude dev files, verify contents |

**Exit criteria:** a `.zip` that a stranger can install on a clean WP and use without error.

---

## Stage 4 — Publish

**Goal:** get it in front of users.

| # | Task | Notes / decisions needed |
|---|---|---|
| 4.1 | **Resolve the slug** | `footnotes` is permanently closed by author request. Options: (a) new slug like `footnotes-reloaded`; (b) contact original author + WP plugin team to reopen. **Blocking decision.** |
| 4.2 | Decide hosting | WP.org (free, discoverable) vs. GitHub releases vs. both |
| 4.3 | Public repo | Push to GitHub with preserved history/credits, GPLv3, `LICENSE`, `CONTRIBUTING` |
| 4.4 | Release notes | Honest: what's fixed vs. upstream, what's inherited |
| 4.5 | Submit / publish | WP.org review queue (can take weeks) |
| 4.6 | Announce | Reply on the "Plugin is Abandoned" / "Alternatives to footnotes?" forum threads |

**Blocker:** 4.1 is a human decision, not a coding task. Everything else can proceed in parallel.

---

## Stage 5 — Sustainability

**Goal:** never have to do archaeology like this again.

| # | Task | Maps to |
|---|---|---|
| 5.1 | CI: PHPCS + PHPUnit on push | #31, #198 |
| 5.2 | Expand test coverage (parser unit tests, fixtures) | #119 |
| 5.3 | Release automation | #117, #89 |
| 5.4 | `readme.txt` validation in CI | #140 |
| 5.5 | Minification in build pipeline | #88 |
| 5.6 | Consider parser DOM rewrite | #121 — only after stable |
| 5.7 | Issue template + triage process | #201 |
| 5.8 | Translation workflow (`languages/`, `.pot`) | #128, #147 |

---

## Dependency graph

```
Stage 0 (foundations)
   │
   ▼
Stage 1 (triage) ────► Stage 2 (fix bugs)
                            │
                            ▼
                       Stage 3 (release prep)
                            │
                            ▼
                       Stage 4 (publish)  ◄── needs slug decision (4.1)
                            │
                            ▼
                       Stage 5 (sustainability, parallel-able earlier)
```

**Critical path:** 0 → 1 → 2 → 3 → 4. Stage 5 can start any time after Stage 1.
**Hard external dependency:** 4.1 (slug) — start the conversation early, it may have lead time.

---

## Milestones

| Milestone | Meaning | Stages | State |
|---|---|---|---|
| **M0** | "It runs" | Stage 0 | ✅ **reached** — plugin runs on `localhost:8080`, fixtures seeded |
| **M1** | "We know what's broken" | Stage 1 | 🟡 **in progress** — 10 items triaged, 3 remaining |
| **M2** | "Bugs are fixed" | Stage 2 | ⬜ |
| **M3** | "Installable" | Stage 3 | ⬜ |
| **M4** | "Published" | Stage 4 | ⬜ |

---

## Suggested immediate next actions

1. **0.1 + 0.2** — record the base decision and stand up Docker WP. *(unblocks everything)*
2. **Start 4.1 informally** — a forum/email inquiry about the slug runs in the background.
3. Then Stage 1 triage, item 1.1 first.

---

## Open decisions (owner input required)

| # | Decision | Blocking | Default if no input |
|---|---|---|---|
| D1 | Canonical base: `3.0`-tree vs `main`? | Stage 0 | Use current `3.0`-tree |
| D2 | Version number for relaunch | Stage 3 | `2.8.0` |
| D3 | Plugin slug / name | Stage 4 | `footnotes-reloaded` |
| D4 | WP.org vs GitHub distribution | Stage 4 | Both |
| D5 | Scope: bugs only, or full backlog? | Stage 2 | Bugs first, re-evaluate after M2 |
