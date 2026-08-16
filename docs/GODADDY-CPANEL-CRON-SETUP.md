# GoDaddy cPanel - Tender Auto-Closure Cron Job Setup

## Overview

This guide shows you how to schedule the tender closure command in GoDaddy cPanel without using SSH. The cron job will automatically close tenders that have passed their closing date every day at 12:01 AM.

## Steps to Add Cron Job in GoDaddy cPanel

### Step 1: Log into cPanel

1. Go to your **GoDaddy cPanel** dashboard
2. You can access it at:
   - `https://cPanel.godaddy.com` (or your cPanel URL)
   - Or through GoDaddy's hosting dashboard → "Manage" → "cPanel"

### Step 2: Find "Cron Jobs"

1. In cPanel, search/find **"Cron Jobs"** 
2. Or navigate to: **Advanced** → **Cron Jobs**

### Step 3: Add New Cron Job

Click **"Add New Cron Job"**

### Step 4: Configure the Cron Job

**In the "Add a New Cron Job" form, fill in:**

#### **Common Settings**

| Field | Value |
|-------|-------|
| **Email** | your-email@example.com (leave empty if you don't want logs) |
| **Minute** | `1` |
| **Hour** | `0` |
| **Day** | `*` |
| **Month** | `*` |
| **Weekday** | `*` |

This means: **Run at 12:01 AM every day**

#### **Command** (most important!)

Choose the command based on your setup:

**OPTION A: Using PHP CLI (Recommended)**
```
/usr/local/bin/php /home/yourusername/public_html/admin/close-tenders.php
```

**OPTION B: Using PHP from specific version**
```
/opt/alt/php83/usr/bin/php /home/yourusername/public_html/admin/close-tenders.php
```

**OPTION C: Using curl (if PHP CLI doesn't work)**
```
curl -X POST "https://yourdomain.com/admin/api/tenders/auto-close" -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Finding Your Correct Paths

### Step A: Find Your Username

1. In cPanel, look at the **top-right corner** → You'll see your username (e.g., `user12345`)
2. Or go to **Account Information** to find it

### Step B: Find Your Public HTML Path

1. In cPanel, go to **File Manager**
2. Navigate to the root directory
3. You should see a `public_html` folder
4. Your path will be: `/home/yourusername/public_html`

### Step C: Verify PHP Version

1. In cPanel, go to **Select PHP Version**
2. Check which version is installed (8.0+)
3. If using a specific version (e.g., 8.3), use the path: `/opt/alt/php83/usr/bin/php`

---

## Example Configurations

### Example 1: Standard GoDaddy Setup
```
/usr/local/bin/php /home/gd_user123/public_html/admin/close-tenders.php
```

### Example 2: With Error Logging
```
/usr/local/bin/php /home/gd_user123/public_html/admin/close-tenders.php >> /home/gd_user123/cron_tender_closure.log 2>&1
```

### Example 3: Different PHP Version
```
/opt/alt/php83/usr/bin/php /home/gd_user123/public_html/admin/close-tenders.php
```

---

## Testing Your Cron Job

### Option 1: Run Manually via Browser

Once deployed, you can test by visiting:
```
https://yourdomain.com/admin/close-tenders.php
```

You should see output like:
```
✓ Closed: [1] Tender Title
✓ Closed: [2] Another Tender

✓ Completed: 2 tender(s) closed.
```

### Option 2: Check Logs

1. In cPanel, go to **File Manager**
2. Navigate to: `public_html/admin/storage/logs/`
3. Open `tender-cron.log`
4. Look for recent entries

### Option 3: Test Cron Schedule Immediately

To test if it works, temporarily change the schedule to run in 2 minutes:
- Minute: `(current minute + 2)`
- Hour: `(current hour)`
- Save
- Check logs after 2 minutes
- Change back to 1 0 * * * (12:01 AM daily)

---

## Alternative: Using the API Endpoint

If the CLI script doesn't work, create a manual trigger via the API:

### Create a Helper Script

Create `admin/trigger-close-tenders.php`:

```php
<?php
// Simple wrapper for cron
// This bypasses auth for cron purposes (use sparingly)
define('CRON_TOKEN', $_ENV['CRON_SECRET_TOKEN'] ?? '');

if ($cron_token = $_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '') {
    if ($cron_token === CRON_TOKEN) {
        require_once 'close-tenders.php';
    } else {
        http_response_code(403);
        die('Forbidden');
    }
} else {
    http_response_code(403);
    die('Forbidden');
}
```

### Add to `.env`
```env
CRON_SECRET_TOKEN=your_super_secret_token_here_change_this
```

### Cron Command
```
curl -X GET "https://yourdomain.com/admin/trigger-close-tenders.php?token=your_super_secret_token_here_change_this"
```

---

## Troubleshooting

### Issue: Cron job not running

**Solution:**
1. Check if PHP CLI is available: Go to cPanel → **Select PHP Version** → Check Advanced Options
2. Try `/usr/bin/php` instead of `/usr/local/bin/php`
3. Check cPanel logs: **Metrics** → **Error Log**

### Issue: "Permission Denied"

**Solution:**
```
chmod 755 /home/yourusername/public_html/admin/close-tenders.php
```

In cPanel File Manager:
1. Right-click `close-tenders.php`
2. Select "Change Permissions"
3. Set to `755`

### Issue: "File Not Found"

**Solution:**
1. Verify the full path is correct
2. Check that `admin/close-tenders.php` exists on the server
3. Use File Manager to navigate and confirm

### Issue: Database connection errors

**Solution:**
1. Verify `.env` file has correct database credentials
2. Check that database/user exists in cPanel MySQL Databases
3. Verify user has ALL privileges granted

### Issue: No logs are being created

**Solution:**
1. Check folder permissions:
   ```
   chmod 755 /home/yourusername/public_html/admin/storage/logs/
   ```
2. Create log file manually:
   ```
   touch /home/yourusername/public_html/admin/storage/logs/tender-cron.log
   chmod 644 /home/yourusername/public_html/admin/storage/logs/tender-cron.log
   ```

---

## Monitoring Cron Jobs

### Daily Email Reports

GoDaddy can email you cron output. In the cron setup:
1. Enter your email in the "Email" field
2. cPanel will email you the output daily
3. Only receives output if there's an error or message

### View Cron History

1. In cPanel, go back to **Cron Jobs**
2. You can see when jobs ran and their status
3. Check **Metrics** → **Error Log** for any issues

---

## Disabling the Cron Job

If you need to pause the cron job:

1. Go to **Cron Jobs** in cPanel
2. Find your tender closure job
3. Click **Delete** or **Disable**
4. Confirm

---

## Advanced: Running Multiple Cron Jobs

You can set up multiple closure times:

1. First job: `1 0 * * *` (12:01 AM) - Closes tenders daily
2. Optional second: `1 12 * * *` (12:01 PM) - Extra check at noon

---

## Testing with curl

If using the API endpoint, test with:

```bash
# Test with token
curl -X POST "https://yourdomain.com/admin/api/tenders/auto-close" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN"

# From command line in cPanel (SSH)
curl -X POST "http://localhost:8080/admin/api/tenders/auto-close" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN"
```

---

## Summary

| Step | Action |
|------|--------|
| 1 | Open GoDaddy cPanel |
| 2 | Go to **Cron Jobs** |
| 3 | Click **Add New Cron Job** |
| 4 | Set schedule: `1 0 * * *` |
| 5 | Paste command (see above examples) |
| 6 | Click **Add New Cron Job** |
| 7 | Monitor logs: `admin/storage/logs/tender-cron.log` |
| 8 | Test manually: Visit `/admin/close-tenders.php` in browser |

Your tenders will now automatically close every day at 12:01 AM! 🎉
