#!/bin/bash
set -e

# Start cron in the background
echo "Starting cron daemon..."
cron

# Execute the main command (php-fpm)
exec "$@"

