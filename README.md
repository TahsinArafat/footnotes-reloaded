# footnotes-reloaded

A maintained continuation of the **footnotes** WordPress plugin — the all-in-one
solution for displaying an automatically-generated list of references on your
Pages and Posts.

> **Status: maintenance fork.** The original plugin was
> [closed on WordPress.org in November 2022](https://wordpress.org/plugins/footnotes/)
> at the author's request and has not been updated since. This fork carries it
> forward, focused on compatibility with current WordPress and PHP.

## Requirements

| | |
|---|---|
| WordPress | 5.4 or later |
| PHP | 8.0 or later |
| Tested up to | WordPress 6.7 |
| Licence | GPLv3 |

## Features

- Fully customizable footnote start and end shortcodes
- Styled hyperlink tooltips
- Responsive reference container with configurable position
- Reference container usable inside a widget
- A range of numbering styles
- Configurable optional backlink symbol
- Configurable footnote appearance
- Shortcode button in the post editor

## Installation

**From source:**

```bash
git clone https://github.com/TahsinArafat/footnotes-reloaded.git
```

Then place the directory in `wp-content/plugins/` and activate it.

**Building a distributable zip:** `./.dev/build.sh` produces
`dist/footnotes-<version>.zip`, containing only the files WordPress needs.

## Development

Local WordPress (WordPress + MySQL + WP-CLI via Docker):

```bash
docker compose up -d          # start
./.dev/setup.sh               # install WP, activate plugin, seed fixtures
docker compose down           # stop
docker compose down -v        # stop and wipe the database
```

Site: <http://localhost:8080> (admin / admin).

Tests and build:

```bash
./.dev/run-tests.sh           # PHPUnit
./.dev/build.sh               # produce dist/footnotes-<version>.zip
```

## Verification

Fixes in this fork are verified with [WitDiff](https://github.com/TahsinArafat/WitDiff),
which transplants changed tests onto the pre-fix revision and confirms the tests
**fail there and pass on the fix**. A test that passes on both revisions proves
nothing, so each fix carries a receipt rather than an assurance.

## Credits and licence

All credit for the original plugin belongs to **Mark Cheret** and the many
contributors listed in `README.txt`.

Licensed under the [GPLv3](LICENSE.txt), which requires — and permits — this kind
of continuation.
