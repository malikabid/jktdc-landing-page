#!/bin/bash

#
# Cron wrapper script for closing expired tenders
# 
# Install in crontab with:
# crontab -e
# 
# Add this line to run at 12:01 AM every day:
# 1 0 * * * /bin/bash /path/to/scripts/cron/close-tenders.sh
#

set -e

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ADMIN_DIR="$( cd "$SCRIPT_DIR/../../admin" && pwd )"

# Change to admin directory
cd "$ADMIN_DIR"

# Run the PHP CLI script
/usr/bin/php close-tenders.php

# Exit with the PHP script's exit code
exit $?
