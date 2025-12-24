#!/bin/bash

echo "🔧 Setting up Git hooks for automatic versioning..."
echo ""

# Check if we're in a Git repository
if [ ! -d ".git" ]; then
  echo "❌ Error: Not a Git repository. Run this script from the project root."
  exit 1
fi

# Create pre-commit hook
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash

# Get list of staged CSS/JS files
STAGED_CSS_JS=$(git diff --cached --name-only --diff-filter=ACM | grep -E 'pub/(css|js)/.*\.(css|js)$')

if [ -n "$STAGED_CSS_JS" ]; then
  echo "🔄 CSS/JS files detected, updating versions..."
  echo "   Changed files:"
  echo "$STAGED_CSS_JS" | sed 's/^/   - /'
  
  # Run the version update script
  bash update-versions.sh
  
  # Check if HTML files were modified
  HTML_MODIFIED=$(git diff --name-only | grep -E '\.html$')
  
  if [ -n "$HTML_MODIFIED" ]; then
    echo "📝 Adding updated HTML files to commit..."
    git add index.html coming-soon.html organizational-chart.html 2>/dev/null
    echo "✅ Versions updated and added to commit"
  else
    echo "ℹ️  No HTML changes needed"
  fi
else
  echo "ℹ️  No CSS/JS changes detected, skipping version update"
fi

exit 0
EOF

# Make the hook executable
chmod +x .git/hooks/pre-commit

echo "✅ Pre-commit hook installed successfully!"
echo ""
echo "📋 What this does:"
echo "   • Automatically detects CSS/JS file changes in commits"
echo "   • Runs update-versions.sh to update cache-busting versions"
echo "   • Adds updated HTML files to your commit"
echo ""
echo "🎯 Usage:"
echo "   Just commit normally! The hook runs automatically:"
echo "   git add pub/css/style.css"
echo "   git commit -m 'feat: update styles'"
echo ""
echo "💡 Note:"
echo "   GitHub Actions will also update versions on push as a safety net."
echo "   This hook is for local development convenience."
echo ""
echo "🗑️  To remove this hook:"
echo "   rm .git/hooks/pre-commit"
echo ""
