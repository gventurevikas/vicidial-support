# Vicidial Support System - Automatic Synchronization

## Overview

The Vicidial Support System now includes comprehensive automatic synchronization capabilities that allow you to automatically sync campaigns, caller IDs, and lists from your Vicidial instances without manual entry.

## Features

### 🔄 Automatic Synchronization
- **Real-time sync**: Data is automatically synchronized every 5 minutes via cron job
- **Manual sync**: One-click sync from the web interface
- **Selective sync**: Sync specific instances or all instances at once
- **Comprehensive logging**: All sync operations are logged with detailed statistics

### 📊 Sync Statistics
- Track sync success/failure rates
- Monitor records created, updated, and failed
- View sync history and performance metrics
- Real-time sync status indicators

### 🛡️ Error Handling
- Robust error handling with detailed logging
- Graceful failure recovery
- Partial sync support (some records may fail while others succeed)
- Automatic retry mechanisms

## How It Works

### 1. Automatic Sync (Cron Job)
The system runs a cron job every 5 minutes that:
- Connects to all active Vicidial instances
- Syncs campaigns, caller IDs, lists, and performance data
- Logs all operations with detailed statistics
- Handles errors gracefully and continues with other instances

### 2. Manual Sync (Web Interface)
Users can trigger manual synchronization:
- **Sync All**: Synchronize all instances at once
- **Sync Instance**: Synchronize a specific instance
- **Real-time feedback**: See sync progress and results immediately

### 3. Data Synchronization Process

#### Campaigns Sync
- Fetches active campaigns from Vicidial `vicidial_campaigns` table
- Creates new campaigns in the support system
- Updates existing campaigns with current status
- Maintains mapping between Vicidial campaign IDs and support system IDs

#### Caller IDs Sync
- Extracts unique caller IDs from Vicidial call logs
- Associates caller IDs with campaigns
- Tracks performance metrics (answer rate, conversion rate)
- Supports automatic rotation based on performance

#### Lists Sync
- Syncs Vicidial lists associated with campaigns
- Tracks list performance and status
- Maintains list-to-campaign relationships

#### Performance Data Sync
- Collects call performance data from Vicidial logs
- Calculates answer rates, conversion rates, and call durations
- Updates caller ID performance metrics
- Maintains historical performance data

## Setup Instructions

### 1. Database Setup
Run the updated database schema to create the sync_logs table:

```sql
-- The sync_logs table is already included in database-files.sql
-- Run the complete database setup script
mysql -u your_user -p < database-files.sql
```

### 2. Cron Job Setup
Add the monitoring cron job to your system:

```bash
# Edit crontab
crontab -e

# Add this line to run every 5 minutes
*/5 * * * * /usr/bin/php /path/to/vicidial-support/cron/monitoring-cron.php
```

### 3. Configuration
Ensure your Vicidial instances are properly configured in the database:

```sql
INSERT INTO vicidial_instances (
    instance_name, 
    vicidial_db_host, 
    vicidial_db_name, 
    vicidial_db_user, 
    vicidial_db_password
) VALUES (
    'Your Vicidial Instance',
    'localhost',
    'vicidial',
    'vicidial_user',
    'your_password'
);
```

## Usage

### Web Interface

1. **Navigate to Campaigns Page**
   - Go to `/campaigns` in your browser
   - You'll see a "Sync from Vicidial" button

2. **Manual Sync**
   - Click "Sync from Vicidial" to sync all instances
   - Or select a specific instance from the filter and sync that instance only
   - Watch real-time progress and results

3. **View Sync Status**
   - Check the sync status API endpoint: `/api/sync-status`
   - View detailed sync logs in the database

### API Endpoints

#### Sync Single Instance
```http
POST /api/sync-instance
Content-Type: application/json

{
    "instance_id": 1
}
```

#### Sync All Instances
```http
POST /api/sync-all-instances
Content-Type: application/json
```

#### Get Sync Status
```http
GET /api/sync-status
```

### Command Line Testing

Run the test script to verify synchronization:

```bash
php test_sync.php
```

## Monitoring and Logging

### Sync Logs
All sync operations are logged in the `sync_logs` table with:
- Instance ID and sync type
- Success/failure status
- Detailed statistics (records synced, created, updated, failed)
- Error messages for failed operations
- User who initiated the sync (for manual operations)

### Performance Monitoring
- Track sync duration and performance
- Monitor success rates across instances
- Identify problematic instances or data sources
- Generate reports on sync efficiency

### Error Handling
- Failed sync operations are logged with detailed error messages
- Partial failures are tracked (some records succeed while others fail)
- Automatic retry mechanisms for transient failures
- Alert system for critical sync failures

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Verify Vicidial database credentials
   - Check network connectivity to Vicidial servers
   - Ensure proper database permissions

2. **Sync Timeout Issues**
   - Large datasets may cause timeouts
   - Consider breaking sync into smaller batches
   - Increase PHP execution time limits

3. **Missing Data**
   - Verify Vicidial tables exist and contain data
   - Check Vicidial instance status
   - Review sync logs for specific error messages

### Debug Mode
Enable detailed logging by checking the sync logs:

```sql
SELECT * FROM sync_logs 
WHERE instance_id = 1 
ORDER BY created_at DESC 
LIMIT 10;
```

## Performance Optimization

### For Large Datasets
- Sync operations are optimized for performance
- Batch processing for large datasets
- Incremental updates to minimize processing time
- Parallel processing where possible

### Memory Management
- Efficient memory usage during sync operations
- Automatic cleanup of temporary data
- Configurable batch sizes for large operations

## Security Considerations

- All database connections use prepared statements
- User authentication required for manual sync operations
- Audit logging for all sync activities
- Secure credential storage and transmission

## Future Enhancements

- Real-time sync notifications
- Advanced filtering and selective sync options
- Sync scheduling and automation
- Performance analytics dashboard
- Integration with external monitoring systems

## Support

For issues or questions about the synchronization system:
1. Check the sync logs in the database
2. Review the error messages in the application logs
3. Test individual components using the test script
4. Contact system administrator for database connectivity issues

---

**Note**: The automatic synchronization system is designed to work seamlessly with existing Vicidial installations. It reads data from Vicidial databases without modifying the original data, ensuring data integrity and system stability. 