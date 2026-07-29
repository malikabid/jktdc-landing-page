#!/bin/bash
#
# One-time setup so Git uses the repo's tracked hooks in .githooks/.
# Every teammate runs this once after cloning:
#
#     bash scripts/setup-git-hooks.sh
#
# (Git does not auto-enable repo hooks on clone, for security — this points
#  core.hooksPath at the tracked .githooks/ directory.)

set -e
cd "$(git rev-parse --show-toplevel)"

git config core.hooksPath .githooks
chmod +x .githooks/* 2>/dev/null || true

echo "✅ Git hooks enabled (core.hooksPath = .githooks)"
echo ""
echo "What happens now:"
echo "  • When you commit changes to pub/css or pub/js, the pre-commit hook"
echo "    stamps a fresh ?v= cache-buster into the HTML files and includes them"
echo "    in the same commit — no extra commit, no dirty working tree."
echo ""
echo "Note: this runs locally only. Edits made directly on GitHub's web UI do"
echo "not run hooks, so change CSS/JS from a local clone to get versioning."
