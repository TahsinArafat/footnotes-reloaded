#!/usr/bin/env bash
#
# Test runner for WitDiff verification.
#
# WitDiff executes the test command inside a temporary worktree that contains
# only committed files, so `vendor/` (gitignored) is absent there. This wrapper
# installs dependencies if needed, then delegates to PHPUnit.
#
# Usage: .dev/run-tests.sh [extra phpunit args...]
#
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -x vendor/bin/phpunit ]]; then
	composer install --no-interaction --quiet --no-progress
fi

exec vendor/bin/phpunit --no-coverage "$@"
