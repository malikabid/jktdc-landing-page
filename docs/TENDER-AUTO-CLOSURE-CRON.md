# Tender Auto-Closure Cron Setup

## Overview

This cron job automatically closes tenders when their closing date has passed (past midnight on the closing date). For example, if a tender has a closing date of February 23, 2026, it will be automatically closed on February 24, 2026 at 12:01 AM.

## Files

- **[admin/close-tenders.php](../admin/close-tenders.php)** - Main PHP CLI script that handles the logic
- **[admin/src/Commands/CloseTendersCommand.php](../admin/src/Commands/CloseTendersCommand.php)** - Command class for closing tenders
- **[scripts/cron/close-tenders.sh](../scripts/cron/close-tenders.sh)** - Shell script wrapper (called by crontab)
- **[storage/logs/tender-cron.log](../admin/storage/logs/tender-cron.log)** - Log file for cron execution

## How It Works

1. **Trigger**: Runs daily at 12:01 AM (or your configured time)
2. **Query**: Finds all tenders where:
   - `closing_date <= today`
   - `status` is `draft`, `active`, or `extended` (not already closed/cancelled)
3. **Action**: Updates each expired tender's status to `closed`
4. **Logging**: Records all actions in `storage/logs/tender-cron.log`

## Installation

### Step 1: Make the shell script executable

```bash
chmod +x /path/to/DOTK/scripts/cron/close-tenders.sh
```

### Step 2: Add to crontab

Edit your crontab file:

```bash
crontab -e
```

Add this line to run the script at 12:01 AM every day:

```cron
1 0 * * * /bin/bash /path/to/DOTK/scripts/cron/close-tenders.sh
```

### Step 3 (Docker only): Setup in Docker container

If running in Docker, add to your `docker-compose.yml`:

```yaml
services:
  php:
    # ... existing config ...
    volumes:
      - /path/to/DOTK/scripts/cron/close-tenders.sh:/usr/local/bin/close-tenders.sh
    # ... rest of config ...
```

Or use the host's crontab to run the Docker command:

```cron
1 0 * * * docker exec dotk_php /usr/bin/php /var/www/admin/close-tenders.php
```

## Cron Schedule Formats

Choose the schedule that suits your needs:

| Schedule | Cron Expression | Description |
|----------|-----------------|-------------|
| Every day at 12:01 AM | `1 0 * * *` | **Recommended** - Closes tenders past midnight |
| Every day at 1:00 AM | `0 1 * * *` | Alternative time |
| Every 6 hours | `0 */6 * * *` | More frequent checks |
| Every hour | `0 * * * *` | Very frequent (not recommended) |

## Manual Testing

You can manually run the script to test it:

```bash
cd /path/to/DOTK/admin
php close-tenders.php
```

Expected output:
```
✓ Closed: [1234] Tender Title 1
✓ Closed: [5678] Tender Title 2

✓ Completed: 2 tender(s) closed.
```

Or if no tenders need closing:
```
✓ No expired tenders found.
```

## Monitoring

### View logs

```bash
# View all logs
tail -f /path/to/DOTK/admin/storage/logs/tender-cron.log

# View last 50 lines
tail -n 50 /path/to/DOTK/admin/storage/logs/tender-cron.log

# Search for errors
grep "ERROR" /path/to/DOTK/admin/storage/logs/tender-cron.log
```

### Verify cron job is scheduled

```bash
# View your crontab
crontab -l

# List all running cron jobs (macOS/Linux)
ps aux | grep cron
```

## Troubleshooting

### Cron job not running

1. **Verify cron daemon is running**:
   ```bash
   # macOS
   sudo launchctl list | grep cron
   
   # Linux
   sudo systemctl status cron
   ```

2. **Check crontab permissions**:
   ```bash
   ls -la /var/spool/cron/
   ```

3. **Enable cron logs** (macOS):
   ```bash
   log stream --predicate 'process == "cron"' --level debug
   ```

### Script not executable

```bash
chmod +x /path/to/DOTK/scripts/cron/close-tenders.sh
```

### PHP not found

Find PHP path:
```bash
which php
# or
which php-fpm
```

Update the script with the correct path.

### Database connection errors

Ensure:
- `.env` file exists in `/path/to/DOTK/admin/`
- Database credentials are correct
- Database server is running

## Log Output Examples

### Successful execution

```
[2026-02-24T12:01:00+00:00] INFO: Starting tender closure check
[2026-02-24T12:01:00+00:00] INFO: Closed tender #1234: Supply of Office Equipment (Closing date: 2026-02-23)
[2026-02-24T12:01:01+00:00] INFO: Closed tender #5678: Construction Services (Closing date: 2026-02-22)
[2026-02-24T12:01:01+00:00] INFO: Tender closure check completed. 2 tender(s) closed.
```

### No tenders to close

```
[2026-02-24T12:01:00+00:00] INFO: Starting tender closure check
[2026-02-24T12:01:00+00:00] INFO: No expired tenders found to close
```

### Error example

```
[2026-02-24T12:01:00+00:00] INFO: Starting tender closure check
[2026-02-24T12:01:00+00:00] ERROR: Failed to close tender #1234: Connection refused
[2026-02-24T12:01:01+00:00] ERROR: Fatal error in tender closure command: Database connection failed
```

## Email Notifications (Optional)

To receive email when tenders are closed, modify the shell script:

```bash
#!/bin/bash
set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ADMIN_DIR="$( cd "$SCRIPT_DIR/../../admin" && pwd )"
cd "$ADMIN_DIR"

OUTPUT=$(/usr/bin/php close-tenders.php 2>&1)
EXIT_CODE=$?

# Email on error
if [ $EXIT_CODE -ne 0 ]; then
    echo "$OUTPUT" | mail -s "⚠️ Tender Auto-Closure Failed" admin@example.com
fi

exit $EXIT_CODE
```

## Disabling the Cron Job

Remove from crontab:

```bash
crontab -e
# Delete the close-tenders line, save and exit
```

Or comment it out:

```cron
# 1 0 * * * /bin/bash /path/to/DOTK/scripts/cron/close-tenders.sh
```
