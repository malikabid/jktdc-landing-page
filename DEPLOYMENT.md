# Deployment Guide

This project supports automated deployment to GoDaddy hosting via GitHub Actions.

## Overview

When you push changes to the `main` branch, GitHub Actions automatically:
1. Updates asset versions (if CSS/JS files changed)
2. Deploys all files to GoDaddy hosting via FTP

## Setup Instructions

### 1. Get Your GoDaddy FTP Credentials

Log into your GoDaddy account and find your FTP credentials:

**Finding FTP Details in GoDaddy:**
1. Log into GoDaddy account
2. Go to **My Products** → **Web Hosting** → **Manage**
3. Find **FTP** section or go to **Settings** → **FTP/SFTP**
4. Note down the details

**What you need:**
- **FTP Server**: Usually `ftp.yourdomain.com` or an IP address like `123.45.67.89`
  - Often shown as "Server" or "Hostname" in GoDaddy
  - Do NOT include `ftp://` prefix
- **FTP Username**: Your cPanel/hosting username
  - May look like: `username@yourdomain.com` or just `username`
- **FTP Password**: Your cPanel/hosting password
  - If you don't know it, reset it in GoDaddy cPanel
- **Server Directory**: The path to your public folder
  - Usually `/public_html/` or `/httpdocs/`
  - Must start with `/` and end with `/`

### 2. Add GitHub Secrets

Add your FTP credentials as encrypted secrets in your GitHub repository:

1. Go to your repository on GitHub
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret** and add each of these:

| Secret Name | Example Value | Description |
|-------------|---------------|-------------|
| `FTP_SERVER` | `ftp.yourdomain.com` | Your GoDaddy FTP server address |
| `FTP_USERNAME` | `username@yourdomain.com` | Your FTP username |
| `FTP_PASSWORD` | `your-secure-password` | Your FTP password |
| `FTP_SERVER_DIR` | `/public_html/` | Remote directory (must end with `/`) |

**Important Formatting Rules**: 
- `FTP_SERVER`: NO `ftp://` prefix, NO trailing `/`, NO spaces
  - ✅ Correct: `ftp.yourdomain.com` or `123.45.67.89`
  - ❌ Wrong: `ftp://ftp.yourdomain.com/` or `ftp.yourdomain.com/`
- `FTP_SERVER_DIR`: MUST start with `/` and end with `/`
  - ✅ Correct: `/public_html/` or `/httpdocs/`
  - ❌ Wrong: `public_html` or `/public_html`
- Never commit FTP credentials to your code
- Use the exact secret names shown above (case-sensitive)

### 3. Test the Deployment

After adding secrets, trigger a deployment:

**Option 1: Push Changes**
```bash
git add .
git commit -m "feat: test deployment"
git push
```

**Option 2: Manual Trigger**
1. Go to **Actions** tab in GitHub
2. Select **Deploy to GoDaddy via FTP**
3. Click **Run workflow** → **Run workflow**

### 4. Monitor Deployment

Watch the deployment progress:
1. Go to **Actions** tab in GitHub
2. Click on the latest workflow run
3. View real-time logs and any errors

## Deployment Process

### What Gets Deployed

✅ **Included:**
- All HTML files (`*.html`)
- CSS files (`pub/css/**`)
- JavaScript files (`pub/js/**`)
- Images (`pub/images/**`)
- Other assets (`favicon.ico`, etc.)

❌ **Excluded:**
- Git files (`.git`, `.gitignore`)
- Documentation (`README.md`, `*.md`)
- Scripts (`update-versions.sh`)
- GitHub workflows (`.github/**`)
- macOS files (`.DS_Store`)

### Deployment Flow

```
1. Push to main branch
   ↓
2. Version Update Workflow runs (if CSS/JS changed)
   ↓
3. Deployment Workflow runs
   ↓
4. Files uploaded to GoDaddy via FTP
   ↓
5. Website live on GoDaddy hosting
```

## Workflow Files

### [.github/workflows/deploy-to-godaddy.yml](.github/workflows/deploy-to-godaddy.yml)
- Handles FTP deployment to GoDaddy
- Runs on every push to `main` branch
- Can be triggered manually

### [.github/workflows/update-versions.yml](.github/workflows/update-versions.yml)
- Updates CSS/JS version parameters
- Runs before deployment when assets change

## Troubleshooting

### Common Issues

**Issue**: `Error: getaddrinfo ENOTFOUND` or "Could not connect to server"
- **Solution**: Your `FTP_SERVER` value is incorrect or has formatting issues
- **Solution**: Remove any protocol prefixes - use `ftp.yourdomain.com` NOT `ftp://ftp.yourdomain.com`
- **Solution**: Remove any trailing slashes from the server address
- **Solution**: Check for extra spaces or special characters
- **Solution**: Try using the IP address instead of hostname (find in GoDaddy cPanel)

**How to fix:**
1. Go to GitHub → Settings → Secrets → Actions
2. Delete the `FTP_SERVER` secret
3. Add it again with just the hostname (e.g., `ftp.yourdomain.com` or `123.45.67.89`)
4. Ensure no spaces, no `ftp://`, no trailing `/`

**Issue**: "Permission denied" or "550 error"
- **Solution**: Verify `FTP_USERNAME` and `FTP_PASSWORD` are correct
- **Solution**: Check `FTP_SERVER_DIR` path exists and has correct permissions
- **Solution**: Ensure the directory path ends with `/`

**Issue**: Files upload but site shows old version
- **Solution**: Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)
- **Solution**: Check if GoDaddy has server-side caching enabled
- **Solution**: Verify the correct directory is set in `FTP_SERVER_DIR`

**Issue**: Workflow doesn't run
- **Solution**: Check that secrets are added correctly (case-sensitive)
- **Solution**: Verify workflow file exists in `.github/workflows/`
- **Solution**: Check Actions tab for error messages

### Testing FTP Connection Locally

Test your FTP credentials before setting up GitHub Actions:

```bash
# Using FTP client (Mac/Linux)
ftp ftp.yourdomain.com
# Enter username and password when prompted

# Or using lftp (more features)
lftp -u your-username ftp.yourdomain.com
```

### Viewing Deployment Logs

1. Go to GitHub repository
2. Click **Actions** tab
3. Click on the workflow run
4. Expand **Deploy to GoDaddy via FTP** step
5. Review detailed logs

## Manual Deployment (Fallback)

If GitHub Actions fails, deploy manually using an FTP client:

**Recommended FTP Clients:**
- **FileZilla** (Windows/Mac/Linux) - Free
- **Cyberduck** (Mac) - Free
- **Transmit** (Mac) - Paid

**Steps:**
1. Connect to your FTP server using credentials
2. Navigate to your public folder (e.g., `/public_html`)
3. Upload all project files except `.git/`, `.github/`, and documentation files
4. Refresh your website

## Security Best Practices

✅ **Do:**
- Use GitHub Secrets for all credentials
- Enable 2FA on your GitHub account
- Use strong, unique passwords for FTP
- Regularly rotate FTP passwords
- Limit FTP access to specific directories

❌ **Don't:**
- Commit FTP credentials to code
- Share secrets in pull request comments
- Use weak or reused passwords
- Give FTP access to root directory

## Alternative Deployment Methods

If you prefer not to use FTP, consider:

- **SFTP**: More secure than FTP (modify workflow to use SFTP action)
- **Git Deployment**: If GoDaddy supports Git-based deployment
- **cPanel API**: Use GoDaddy's cPanel API for deployments
- **Render/Netlify/Vercel**: Consider migrating from GoDaddy to modern hosting

## Performance Tips

### Faster Deployments
- FTP-Deploy-Action only uploads changed files (not everything)
- Uses `.git-ftp-include` and `.git-ftp-ignore` for fine-grained control
- Parallel uploads for better performance

### Cache Busting
- Automated versioning system ensures browsers load latest assets
- Version parameters update automatically: `style.css?v=a1b2c3d`
- No need for manual cache clearing on GoDaddy

## Support

**Need Help?**
- Check GitHub Actions logs for specific errors
- Review GoDaddy's FTP documentation
- Contact GoDaddy support for hosting issues
- Check the [FTP-Deploy-Action documentation](https://github.com/SamKirkland/FTP-Deploy-Action)
