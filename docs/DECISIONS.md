# Decision Log

## D1 — Canonical base: `3.0`-derived tree

**Date:** 2025
**Status:** Accepted

**Decision:** The local `footnotes/` tree (upstream `3.0` branch, flattened to plugin
root, plus hand-applied fixes) is the canonical base for the relaunch.

**Rationale:**
- It is the more modern codebase: namespaced (`footnotes\`), `declare(strict_types=1)`,
  typed properties, PHP 8.
- It contains a settings group/section architecture absent from `main`.
- It already contains fixes upstream never shipped (bootstrap hooks, parser loop
  termination, AJAX nonce verification, AMP styling).
- `main` last advanced in 2023-06-15 and was the line that was *closed*, not improved.

**Alternatives considered:**
- **Rebase onto `main` 2.7.3** — rejected: older PHP, no namespaces, fewer features,
  and our tree already incorporates the fixes that matter.

**Consequences:**
- Upstream `3.0` was never released, so there is no user base on it and no upgrade path
  to preserve for it — but there *is* a 2.7.3 user base to migrate from (see Stage 3.3).
- The plugin header version stays `2.8.0p` until Stage 3.1 formalises it.

---

## D2 — Version number

**Status:** Provisional (default: `2.8.0`)
To be finalised in Stage 3.1. Rationale for `2.8.0`: strictly greater than the last
published `2.7.3`, communicates "continuation not rewrite", matches the `3.0` branch's
own self-declared `2.8.0d`.

---

## D3 — Plugin slug / name

**Status:** Open — blocking Stage 4.1
Default: new slug `footnotes-reloaded`. The original `footnotes` slug is permanently
closed by author request and cannot be reused without transferring ownership.

---

## D4 — Distribution

**Status:** Provisional (default: both WP.org and GitHub releases)

---

## D5 — Scope

**Status:** Provisional (default: fix confirmed bugs first, re-evaluate after M2)
