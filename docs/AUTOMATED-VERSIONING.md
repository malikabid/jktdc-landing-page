# Automated Versioning System

## Overview

This project now uses **automated cache-busting** based on Git commit hashes. Version numbers are automatically updated when CSS/JS files change, eliminating manual version management.

## How It Works

### Automatic Versioning (GitHub Actions)
When you push changes to `pub/css/` or `pub/js/` files, a GitHub Action automatically:
1. Detects the changes
2. Generates a version based on the Git commit hash (e.g., `abc1234`)
3. Updates all HTML files with the new version
4. Commits and pushes the updated HTML files

**No manual intervention required!**

### Manual Versioning (Local Script)
For local development or manual deployments, run:

```bash
bash update-versions.sh
```

This script:
- Uses the current Git commit hash as the version
- Updates all version parameters in HTML files: `style.css?v=abc1234`
- Works offline (falls back to timestamp if not in a Git repo)

## Version Format

**Old System (Manual):**
```html
<link rel="stylesheet" href="pub/css/style.css?v=1.0.0" />
```

**New System (Automated):**
```html
<link rel="stylesheet" href="pub/css/style.css?v=a1b2c3d" />
```

Version is now a 7-character Git commit hash, ensuring uniqueness for every change.

## Files Automatically Versioned

**CSS Files:**
- `pub/css/style.css`
- `pub/css/theme.css`

**JS Files:**
- `pub/js/header.js`
- `pub/js/slider.js`
- `pub/js/theme-switcher.js`
- `pub/js/notifications-scroll.js`
- `pub/js/service-cards-loader.js`

**HTML Files Updated:**
- `index.html`
- `coming-soon.html`
- `organizational-chart.html`

## Workflows

### Git Hooks (Local Development - Optional)
**Automatic versioning on every commit!**

Run the setup script once to install a pre-commit hook:

```bash
bash setup-git-hooks.sh
```

**What it does:**
- Automatically detects CSS/JS changes in your commits
- Runs `update-versions.sh` before commit completes
- Adds updated HTML files to the same commit
- Zero manual work needed!

**Usage:**
```bash
# Just commit normally!
git add pub/css/style.css
git commit -m "feat: update header styles"
# Hook automatically runs and updates versions ✨

git push
# GitHub Actions provides safety net if hook missed anything
```

**To remove the hook:**
```bash
rm .git/hooks/pre-commit
```

**Note:** Git hooks are local to your machine. Other team members need to run `setup-git-hooks.sh` separately. GitHub Actions always runs as a backup!

### GitHub Actions (Recommended)
1. Make changes to CSS/JS files
2. Commit: `git add . && git commit -m "feat: update styles"`
3. Push: `git push`
4. GitHub Action automatically updates versions and commits HTML changes
5. Render auto-deploys with new versions

### Local Script
1. Make changes to CSS/JS files
2. Run: `bash update-versions.sh`
3. Review changes: `git diff`
4. Commit: `git add . && git commit -m "feat: update styles"`
5. Push: `git push`

### Emergency Manual Update
If you need to force a version update without changing files:

```bash
# Run the update script
bash update-versions.sh

# Or trigger GitHub Action manually
# Go to Actions tab → Update Asset Versions → Run workflow
```

## Benefits

✅ **No More Manual Updates**: Forget about incrementing version numbers  
✅ **Automatic Cache-Busting**: Every change gets a unique version  
✅ **Git-Based**: Versions tied to commits for easy tracking  
✅ **Zero Build Tools**: Pure bash script, no npm/webpack required  
✅ **CI/CD Compatible**: Works seamlessly with GitHub Actions + Render  
✅ **Local Git Hooks**: Optional pre-commit hook for instant local updates  
✅ **Multi-Layer Safety**: Git hooks + GitHub Actions ensure nothing is missed

## Conf Hooks Setup
The setup script creates a pre-commit hook at `.git/hooks/pre-commit` that:
- Runs only when CSS/JS files are staged
- Updates version numbers using `update-versions.sh`
- Automatically adds updated HTML files to the commit

To customize the hook behavior, edit `.git/hooks/pre-commit` directly.

### Gitiguration

### GitHub Actions Setup
The workflow file is at `.github/workflows/update-versions.yml` and triggers on:
- Pushes to `main` branch that modify `pub/css/**` or `pub/js/**`
- Manual workflow dispatch (Actions tab)

### Customization
To change which files are versioned, edit `update-versions.sh`:

```bash
# Add more HTML files
HTML_FILES=(
  "index.html"
  "coming-sGit hook not running  
**Solution**: Run `bash setup-git-hooks.sh` to install the hook, or check if `.git/hooks/pre-commit` exists and is executable

**Issue**: Hook runs but versions not updating  
**Solution**: Ensure `update-versions.sh` is executable: `chmod +x update-versions.sh`

**Issue**: Hook causes commit to fail  
**Solution**: Temporarily disable by renaming: `mv .git/hooks/pre-commit .git/hooks/pre-commit.disabled`

**Issue**: oon.html"
  "organizational-chart.html"
  "new-page.html"  # Add here
)
```

## Troubleshooting

**Issue**: Script doesn't update versions  
**Solution**: Ensure you're in a Git repository with committed changes

**Issue**: GitHub Action not running  
**Solution**: Check that you've committed `.github/workflows/update-versions.yml`

**Issue**: Versions not updating on Render  
**Solution**: Clear Render's cache or force a new deployment

## Migration from Manual Versioning

The automated system is fully backward compatible. Your existing `v=1.0.0` versions will be replaced with Git hashes on the next run of `update-versions.sh` or when the GitHub Action triggers.

**No code changes required!**
