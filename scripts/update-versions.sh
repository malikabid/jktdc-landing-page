#!/bin/bash

# Automated Cache-Busting Version Updater
# This script automatically updates version numbers in HTML files based on Git commit hash
# Run this script before deploying to production

# Get short Git commit hash (7 characters)
VERSION=$(git rev-parse --short=7 HEAD 2>/dev/null || echo "dev-$(date +%s)")

echo "🔄 Updating versions to: $VERSION"

# Files to update
HTML_FILES=(
  "index.html"
  "coming-soon.html"
  "organizational-chart.html"
)

# Update version parameters in HTML files
for file in "${HTML_FILES[@]}"; do
  if [ -f "$file" ]; then
    echo "   Updating $file..."
    
    # Update CSS files
    sed -i.bak -E "s|(pub/css/[^\"']+\.css)\?v=[^\"']+|\1?v=$VERSION|g" "$file"
    
    # Update JS files
    sed -i.bak -E "s|(pub/js/[^\"']+\.js)\?v=[^\"']+|\1?v=$VERSION|g" "$file"
    
    # Remove backup files
    rm -f "$file.bak"
    
    echo "   ✅ Updated $file"
  else
    echo "   ⚠️  Warning: $file not found"
  fi
done

echo ""
echo "✨ Version update complete!"
echo "📌 New version: $VERSION"
echo ""
echo "Next steps:"
echo "1. Review changes with: git diff"
echo "2. Commit changes: git add . && git commit -m 'Update asset versions to $VERSION'"
echo "3. Deploy: git push"
