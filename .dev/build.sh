#!/usr/bin/env bash
#
# Build a clean, distributable plugin zip.
#
# Copies only the files WordPress needs into a staging directory, then zips it.
# Development tooling (tests, docs, docker, composer, witdiff) is excluded.
#
# Usage: .dev/build.sh
# Output: dist/footnotes-<version>.zip
#
set -euo pipefail

cd "$(dirname "$0")/.."

VERSION=$(grep -m1 '^\s*\*\s*Version:' footnotes.php | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')
if [[ -z "${VERSION}" ]]; then
	echo "!! Could not determine version from footnotes.php" >&2
	exit 1
fi

STAGE="dist/footnotes"
ZIP="dist/footnotes-${VERSION}.zip"

echo ">> Building footnotes ${VERSION}"

rm -rf dist
mkdir -p "${STAGE}"

# Files and directories that make up the plugin.
PLUGIN_PATHS=(
	footnotes.php
	index.php
	uninstall.php
	README.txt
	LICENSE.txt
	wpml-config.xml
	admin
	includes
	public
	languages
)

for path in "${PLUGIN_PATHS[@]}"; do
	if [[ ! -e "${path}" ]]; then
		echo "!! Missing expected plugin path: ${path}" >&2
		exit 1
	fi
	cp -R "${path}" "${STAGE}/"
done

# Strip anything that should not ship even if it lives inside a plugin dir.
find "${STAGE}" \( -name '.DS_Store' -o -name '.!*' -o -name '*.map' \) -delete

echo ">> Zipping"
( cd dist && zip -qr "$(basename "${ZIP}")" footnotes )

# Report contents so the exclusion rules are visible, not assumed.
echo ">> Contents of ${ZIP} (top-level entries):"
unzip -Z1 "${ZIP}" | sed 's|^footnotes/||' | awk -F/ 'NF>0 && $1!="" {print $1}' | sort -u | sed 's/^/     /'

echo
echo ">> Done: ${ZIP}"
