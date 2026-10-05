#!/usr/bin/env bash
#
# Seed reproducible test fixtures for footnote triage.
# Idempotent-ish: creates a new post each run (intended — fresh IDs each time).
#
# Usage: ./dev/seed.sh
#
set -euo pipefail

cd "$(dirname "$0")/.."
WP="docker compose run --rm wpcli"

# Helper: create a published post, print its ID.
mkpost() {
  local title="$1" body="$2"
  $WP post create --post_title="$title" --post_status=publish --post_content="$body" --porcelain 2>/dev/null | tail -1
}

echo ">> Seeding fixtures..."

ID_BASIC=$(mkpost "Fixture: Basic" \
  '<p>Here is a claim. ((This is the first footnote.))</p><p>Another point. ((Second note, with a second sentence.))</p>')
echo "   basic            -> post $ID_BASIC"

ID_MULTIPARA=$(mkpost "Fixture: Multi-paragraph note" \
  '<p>A claim. ((First paragraph of the note.

Second paragraph of the same note.))</p>')
echo "   multi-paragraph  -> post $ID_MULTIPARA"

ID_ENCODING=$(mkpost "Fixture: Encoding" \
  '<p>Quote test ((It&#8217;s a note with a curly apostrophe &#8211; and an en dash.))</p>')
echo "   encoding         -> post $ID_ENCODING"

ID_NESTED=$(mkpost "Fixture: Nested (expected to break)" \
  '<p>Outer claim. ((Outer footnote containing ((an inner footnote)) inside it.))</p>')
echo "   nested           -> post $ID_NESTED"

ID_EMPTY=$(mkpost "Fixture: Empty note" \
  '<p>Claim with empty note. (())</p>')
echo "   empty note       -> post $ID_EMPTY"

ID_MULTIBLOCK=$(mkpost "Fixture: Multiple blocks" \
  '<p>One. ((Note one.))</p><ul><li>Item ((Note two in a list.))</li></ul><blockquote>Quote ((Note three in a quote.))</blockquote>')
echo "   multiple blocks  -> post $ID_MULTIBLOCK"

# Scale fixture: 250 footnotes.
BODY=$(python3 -c "print(''.join(f'<p>Claim {i}. ((Footnote {i} text.)).</p>' for i in range(1,251)))")
ID_SCALE=$(mkpost "Fixture: Scale 250" "$BODY")
echo "   scale (250)      -> post $ID_SCALE"

# Taxonomy fixture: category with a footnote in its description.
CAT_ID=$($WP term create category "Fixture Category" \
  --description='Category with a note. ((A footnote in the category description.))' --porcelain 2>/dev/null | tail -1)
echo "   category         -> term $CAT_ID"

echo ">> Fixtures seeded."
