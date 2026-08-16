#!/bin/bash
#
# Cache-buster stamper.
#
# Rewrites the ?v=... query string on every pub/css and pub/js asset link in all
# tracked HTML files. The version is derived from the CURRENT (staged) content of
# the CSS/JS assets, so it changes only when an asset actually changes and never
# depends on the not-yet-created commit hash (which caused an off-by-one before).
#
# Used by the pre-commit hook (.githooks/pre-commit) and can also be run by hand.

# Short, stable hash of all CSS/JS blob contents in the index (staged state).
VERSION=$(git ls-files -s -- 'pub/css/*.css' 'pub/js/*.js' 2>/dev/null \
  | git hash-object --stdin 2>/dev/null | cut -c1-8)
[ -z "$VERSION" ] && VERSION="dev$(date +%s)"

echo "🔄 Stamping asset versions -> ?v=$VERSION"

while IFS= read -r f; do
  [ -f "$f" ] || continue
  # Matches both "pub/css/style.css?v=..." and "/pub/css/style.css?v=..." (css & js)
  sed -i.bak -E "s#((/)?pub/(css|js)/[^\"']+\.(css|js))\?v=[^\"']+#\1?v=$VERSION#g" "$f"
  rm -f "$f.bak"
done < <(git ls-files '*.html')

echo "✅ Asset versions updated to $VERSION"
