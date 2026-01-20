# DOTK Admin - GoDaddy Deployment Guide

## Prerequisites

Before deploying, ensure your GoDaddy hosting plan supports:
- **PHP 8.3 or higher**
- **MySQL 8.0** or MariaDB
- **Composer** (via SSH) or ability to upload vendor folder
- **Sufficient disk space** for dependencies (~50MB)

## Quick Deployment

### Step 1: Prepare Deployment Package

Run the deployment script:

```bash
./deploy-admin-godaddy.sh
```

This creates an `admin-deploy` folder with production-ready files.

### Step 2: Configure Database (GoDaddy cPanel)

1. Log into GoDaddy cPanel
2. Go to **MySQL Databases**
3. Create a new database:
   - Database name: `dotk_admin` (or your choice)
   - Create user: `dotk_user`
   - Set strong password
   - Grant ALL privileges to user
4. Note down:
   - Database name
   - Database username
   - Database password
   - Database host (usually `localhost`)

### Step 3: Update Environment Configuration

Edit `admin-deploy/.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=youraccount_dotk_admin  # Usually prefixed with cPanel username
DB_USERNAME=youraccount_dotk_user
DB_PASSWORD=your_secure_password

JWT_SECRET=GENERATE_RANDOM_32_CHAR_STRING_HERE
```

**Generate JWT Secret:**
```bash
openssl rand -base64 32
```

### Step 4: Upload Files via FTP

Using FileZilla or cPanel File Manager:

1. Connect to your GoDaddy FTP:
   - Host: `ftp.yourdomain.com`
   - Username: Your cPanel username
   - Password: Your cPanel password

2. Create directory structure:
   ```
   /public_html/
   └── admin/           ← Create this folder
   ```

3. Upload all files from `admin-deploy/` to `/public_html/admin/`

**Important:** The `public` folder contents should go directly in `/public_html/admin/`, OR adjust your structure based on where you want the admin accessible.

#### Option A: Admin at /admin/ (Recommended)
```
/public_html/admin/
├── .env
├── .htaccess (from public/.htaccess)
├── index.php (from public/index.php)
├── config/
├── src/
├── templates/
├── storage/
└── vendor/
```

#### Option B: Admin at root subdomain (admin.yourdomain.com)
Point subdomain document root to `admin/public/`

### Step 5: Install Composer Dependencies (If Not Pre-installed)

If you have SSH access:

```bash
ssh username@yourdomain.com
cd public_html/admin
composer install --no-dev --optimize-autoloader
```

**Without SSH:** Upload the entire `vendor` folder via FTP (slower but works).

### Step 6: Set Up Database

Upload and import SQL initialization:

1. In cPanel, go to **phpMyAdmin**
2. Select your database
3. Click **Import**
4. Upload `admin/docker/mysql-init/01-init.sql`
5. Click **Go**

### Step 7: Set File Permissions

Via FTP or cPanel File Manager:

```
.env                    → 644 or 600 (read-only)
storage/                → 775 (writable)
storage/logs/           → 775
storage/cache/          → 775
storage/uploads/        → 775
```

In cPanel File Manager:
- Right-click folder → Change Permissions
- Set to: Owner (Read, Write, Execute), Group (Read, Execute), World (Read, Execute)

### Step 8: Configure PHP Version

1. In cPanel, go to **Select PHP Version**
2. Choose **PHP 8.3** or higher
3. Enable required extensions:
   - ✓ json
   - ✓ pdo_mysql
   - ✓ mbstring
   - ✓ openssl
   - ✓ tokenizer
   - ✓ curl

### Step 9: Test Deployment

Visit: `https://yourdomain.com/admin/`

You should see the DOTK Admin welcome page.

Test endpoints:
- Health check: `https://yourdomain.com/admin/health`
- API info: `https://yourdomain.com/admin/api`

## Troubleshooting

### Error: "500 Internal Server Error"

**Check PHP error logs:**
- cPanel → **Errors** → View error_log
- Or check `storage/logs/app.log`

**Common causes:**
1. Wrong PHP version (< 8.3)
2. Missing .env file
3. Incorrect database credentials
4. Insufficient file permissions on storage/

### Error: "Database connection failed"

1. Verify database credentials in `.env`
2. Check database exists in cPanel
3. Ensure user has privileges
4. Test connection via phpMyAdmin

### Error: "Class not found" or Composer errors

1. Re-upload `vendor` folder
2. Or run `composer install` via SSH
3. Ensure `vendor/autoload.php` exists

### Blank page / No output

1. Enable error display temporarily:
   ```php
   // Add to top of public/index.php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
2. Check `storage/logs/app.log`
3. Check PHP error_log in cPanel

### .htaccess not working

1. Ensure mod_rewrite is enabled (usually is on GoDaddy)
2. Check RewriteBase in .htaccess matches your directory
3. Try alternative .htaccess rules:
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```

## Security Checklist

- [ ] Set `APP_DEBUG=false` in production
- [ ] Generate strong JWT_SECRET (32+ characters)
- [ ] Set `.env` permissions to 644 or 600
- [ ] Enable HTTPS and update APP_URL
- [ ] Set proper storage/ permissions (775)
- [ ] Remove .git folder if uploaded
- [ ] Restrict access to .env via .htaccess
- [ ] Consider adding HTTP authentication for /admin/
- [ ] Keep composer dependencies updated
- [ ] Regular database backups

## Automated Deployment (Optional)

To automate deployments via GitHub Actions, update `.github/workflows/deploy-to-godaddy.yml`:

```yaml
# Add to deploy job after static files
- name: Deploy Admin Panel
  uses: SamKirkland/FTP-Deploy-Action@v4.3.5
  with:
    server: ${{ secrets.FTP_SERVER }}
    username: ${{ secrets.FTP_USERNAME }}
    password: ${{ secrets.FTP_PASSWORD }}
    server-dir: ${{ secrets.FTP_SERVER_DIR }}/admin/
    local-dir: ./admin-deploy/
    exclude: |
      **/.git*
      **/.env.example
      **/docker/**
      **/tests/**
```

## Support

For issues specific to GoDaddy hosting:
- Contact GoDaddy support for PHP/database questions
- Check [GoDaddy PHP documentation](https://www.godaddy.com/help)

For application issues:
- Review `storage/logs/app.log`
- Check admin panel health endpoint
- Verify all dependencies are installed
