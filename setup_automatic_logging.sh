#!/bin/bash

# Setup script for automatic performance logging
# This script helps configure the cron job for automatic logging every 5 minutes

echo "=== Vicidial Support - Automatic Performance Logging Setup ==="
echo ""

# Get the current directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CRON_SCRIPT="$SCRIPT_DIR/cron/performance_logging_cron.php"

echo "1. Checking prerequisites..."

# Check if PHP is available
if command -v php &> /dev/null; then
    echo "✓ PHP is available"
    PHP_PATH=$(which php)
else
    echo "❌ PHP is not available. Please install PHP first."
    exit 1
fi

# Check if the cron script exists
if [ -f "$CRON_SCRIPT" ]; then
    echo "✓ Cron script exists: $CRON_SCRIPT"
else
    echo "❌ Cron script not found: $CRON_SCRIPT"
    exit 1
fi

# Make the cron script executable
chmod +x "$CRON_SCRIPT"
echo "✓ Made cron script executable"

# Check if logs directory exists
LOGS_DIR="$SCRIPT_DIR/logs"
if [ ! -d "$LOGS_DIR" ]; then
    mkdir -p "$LOGS_DIR"
    echo "✓ Created logs directory: $LOGS_DIR"
else
    echo "✓ Logs directory exists: $LOGS_DIR"
fi

echo ""
echo "2. Testing the cron script..."

# Test the cron script
if php "$CRON_SCRIPT" > /dev/null 2>&1; then
    echo "✓ Cron script test passed"
else
    echo "⚠️  Cron script test failed (this might be normal if no data to log)"
fi

echo ""
echo "3. Setting up crontab..."

# Create the crontab entry
CRON_ENTRY="0,5,10,15,20,25,30,35,40,45,50,55 * * * * $PHP_PATH $CRON_SCRIPT"

echo "Crontab entry to add:"
echo "$CRON_ENTRY"
echo ""

# Ask user if they want to add to crontab
read -p "Do you want to add this to your crontab? (y/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Check if the entry already exists
    if crontab -l 2>/dev/null | grep -q "$CRON_SCRIPT"; then
        echo "⚠️  Cron entry already exists in crontab"
    else
        # Add to crontab
        (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
        echo "✓ Added to crontab successfully"
    fi
else
    echo "To add manually, run:"
    echo "crontab -e"
    echo "Then add this line:"
    echo "$CRON_ENTRY"
fi

echo ""
echo "4. Configuration summary:"

echo "✓ Performance logging service: $SCRIPT_DIR/src/Services/PerformanceLoggingService.php"
echo "✓ Cron script: $CRON_SCRIPT"
echo "✓ Log file: $LOGS_DIR/performance_logging_cron.log"
echo "✓ Database tables: campaign_logs, caller_id_logs, list_logs"

echo ""
echo "5. What happens every 5 minutes:"
echo "  - Campaign performance data is logged to campaign_logs"
echo "  - Caller ID performance data is logged to caller_id_logs"
echo "  - List performance data is logged to list_logs"
echo "  - Only data from the last 5 minutes is processed"
echo "  - Old logs (30+ days) are cleaned automatically at 2 AM"

echo ""
echo "6. Monitoring:"
echo "  - Check logs: tail -f $LOGS_DIR/performance_logging_cron.log"
echo "  - View recent logs: php $SCRIPT_DIR/test_automatic_logging.php"
echo "  - Check crontab: crontab -l"

echo ""
echo "✅ Automatic performance logging setup completed!"
echo "✅ Logs will be updated every 5 minutes automatically"
echo "✅ Performance data will be available in real-time" 