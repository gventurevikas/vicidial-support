# Vicidial Support System

A comprehensive PHP and MySQL-based monitoring and management system for multiple Vicidial instances. This system provides real-time monitoring, automated caller ID rotation, list management, and campaign performance tracking across multiple Vicidial systems from a single web interface.

## Features

### 🔧 **Multi-Instance Management**
- Manage multiple Vicidial systems from a single panel
- Real-time data synchronization from Vicidial databases
- Instance-specific monitoring and alerts

### 📊 **Real-time Monitoring**
- Server performance metrics (CPU, Memory, Disk, Network)
- Caller ID health monitoring and performance tracking
- Campaign performance analytics
- List rotation and health monitoring

### 🔄 **Automated Systems**
- Automatic caller ID rotation based on performance thresholds
- List rotation strategies
- Server health monitoring with alerts
- Performance-based optimization

### 🚨 **Alert System**
- Multi-level alerts (Critical, Warning, Info)
- Real-time notifications
- Alert history and resolution tracking
- Email and webhook notifications

### 📈 **Reporting & Analytics**
- Performance dashboards with charts
- Historical data analysis
- Automated report generation
- Export capabilities (CSV, PDF)

## System Requirements

- **PHP**: 8.1 or higher
- **MySQL**: 8.0 or higher
- **Web Server**: Apache/Nginx
- **Extensions**: PDO, JSON, SSH2
- **Memory**: Minimum 512MB RAM
- **Storage**: 10GB+ for logs and data

## Installation

### 1. Clone the Repository
```bash
git clone https://github.com/your-repo/vicidial-support.git
cd vicidial-support
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Set Up Database
```bash
# Import the database schema
mysql -u root -p < database-files.sql
```

### 4. Configure Environment
```bash
# Copy and edit the configuration file
cp config/database.php.example config/database.php
```

Edit `config/database.php` with your database credentials:
```php
return [
    'connections' => [
        'vicidial_support' => [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'vicidial_support',
            'username' => 'your_username',
            'password' => 'your_password',
        ],
    ],
];
```

### 5. Set Up Web Server

#### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName vicidial-support.local
    DocumentRoot /path/to/vicidial-support/public
    
    <Directory /path/to/vicidial-support/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/vicidial-support_error.log
    CustomLog ${APACHE_LOG_DIR}/vicidial-support_access.log combined
</VirtualHost>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name vicidial-support.local;
    root /path/to/vicidial-support/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 6. Set Up Cron Jobs
```bash
# Add to crontab (crontab -e)
*/5 * * * * /usr/bin/php /path/to/vicidial-support/cron/monitoring-cron.php
0 2 * * * /usr/bin/php /path/to/vicidial-support/cron/cleanup-cron.php
```

### 7. Create Required Directories
```bash
mkdir -p logs reports
chmod 755 logs reports
```

## Configuration

### Adding Vicidial Instances

1. **Access the Admin Panel**
   - Login with default credentials: `admin` / `admin123`
   - Navigate to Instances section

2. **Add New Instance**
   ```
   Instance Name: Production Vicidial
   Database Host: 192.168.1.100
   Database Name: vicidial
   Database User: vicidial_user
   Database Password: your_password
   Web Server URL: http://192.168.1.100/vicidial
   ```

3. **Configure Server Monitoring**
   - Add server details with SSH credentials
   - Set monitoring thresholds
   - Configure alert settings

### User Management

#### User Roles
- **Admin**: Full system access
- **Manager**: Instance and campaign management
- **Support**: Monitoring and basic operations
- **Viewer**: Read-only access

#### Adding Users
```sql
INSERT INTO users (username, email, password_hash, first_name, last_name, role) 
VALUES ('support_user', 'support@company.com', '$2y$10$...', 'John', 'Doe', 'support');
```

## Usage

### Dashboard Overview

The dashboard provides:
- **Real-time Statistics**: Active instances, campaigns, caller IDs, alerts
- **Performance Charts**: Campaign performance and server health
- **Recent Alerts**: Latest system alerts and notifications
- **Instance Status**: Health status of all Vicidial instances

### Caller ID Management

#### Manual Rotation
1. Navigate to Caller IDs section
2. Select caller ID to rotate
3. Click "Rotate" button
4. Provide rotation reason
5. System automatically activates next available caller ID

#### Automatic Rotation
- System monitors answer rates and block rates
- Automatically rotates caller IDs when thresholds are exceeded
- Configurable rotation frequency per campaign

### Campaign Monitoring

#### Performance Metrics
- Answer Rate: Percentage of answered calls
- Conversion Rate: Percentage of successful transfers
- Total Calls: Number of calls made
- Average Call Duration: Average call length

#### Optimization
- Time-of-day optimization
- Geographic targeting
- Demographic analysis
- Performance trend analysis

### Server Monitoring

#### Metrics Tracked
- **CPU Usage**: Real-time CPU utilization
- **Memory Usage**: RAM consumption
- **Disk Usage**: Storage utilization
- **Network**: Inbound/outbound traffic
- **Database Connections**: Active connections

#### Alerts
- High CPU usage (>80%)
- High memory usage (>85%)
- High disk usage (>90%)
- Database connection issues

### List Management

#### List Health Monitoring
- Contact quality assessment
- Response rate tracking
- Conversion rate analysis
- List fatigue detection

#### Rotation Strategies
- Performance-based rotation
- Time-based rotation
- Volume-based rotation
- Quality-based rotation

## API Endpoints

### Authentication
All API endpoints require authentication via session cookies.

### Available Endpoints

#### Instances
- `GET /api/instances` - List all instances
- `POST /api/instances` - Add new instance
- `PUT /api/instances/{id}` - Update instance

#### Campaigns
- `GET /api/campaigns` - List campaigns
- `GET /api/campaigns?instance_id={id}` - List campaigns for instance
- `POST /api/campaigns` - Add new campaign

#### Caller IDs
- `GET /api/caller-ids` - List caller IDs
- `GET /api/caller-ids?campaign_id={id}` - List caller IDs for campaign
- `POST /api/rotate-caller-id` - Rotate caller ID

#### Server Metrics
- `GET /api/server-metrics` - Get server metrics
- `GET /api/server-metrics?server_id={id}` - Get metrics for specific server

#### Alerts
- `GET /api/alerts` - List active alerts
- `POST /api/alerts/{id}/resolve` - Resolve alert

## Monitoring Scripts

### Automated Monitoring
The system includes several automated scripts:

#### `cron/monitoring-cron.php`
- Runs every 5 minutes
- Collects server metrics
- Monitors caller ID health
- Syncs data from Vicidial instances
- Checks for automatic rotations
- Generates reports

#### `cron/cleanup-cron.php`
- Runs daily at 2 AM
- Cleans old performance logs
- Removes resolved alerts
- Archives old data

## Troubleshooting

### Common Issues

#### Database Connection Issues
```bash
# Check database connectivity
mysql -u vicidial_user -p -h your_vicidial_host

# Verify credentials in config
php -r "require 'config/database.php'; print_r(\$config);"
```

#### SSH Connection Issues
```bash
# Test SSH connectivity
ssh username@server_ip

# Check SSH extension
php -m | grep ssh2
```

#### Permission Issues
```bash
# Fix directory permissions
chmod 755 logs reports
chown www-data:www-data logs reports
```

### Log Files

#### Application Logs
- `logs/monitoring.log` - Monitoring script logs
- `logs/cron.log` - Cron job logs
- `logs/error.log` - Application errors

#### Web Server Logs
- Apache: `/var/log/apache2/vicidial-support_error.log`
- Nginx: `/var/log/nginx/vicidial-support_error.log`

### Performance Optimization

#### Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_performance_campaign_time ON performance_logs(campaign_id, recorded_at);
CREATE INDEX idx_alerts_instance_type ON alerts(instance_id, alert_type);
```

#### PHP Optimization
```ini
; php.ini settings
memory_limit = 512M
max_execution_time = 300
opcache.enable = 1
opcache.memory_consumption = 128
```

## Security Considerations

### Authentication
- Session-based authentication
- Role-based access control
- Password hashing with bcrypt
- Session timeout configuration

### Data Protection
- Database encryption at rest
- HTTPS for all communications
- Input validation and sanitization
- SQL injection prevention

### Network Security
- Firewall configuration
- VPN access for remote management
- SSH key-based authentication
- Regular security updates

## Backup and Recovery

### Database Backup
```bash
# Daily backup script
mysqldump -u root -p vicidial_support > backup_$(date +%Y%m%d).sql
```

### File Backup
```bash
# Backup configuration and logs
tar -czf vicidial-support-backup-$(date +%Y%m%d).tar.gz \
    config/ logs/ reports/ views/
```

### Recovery Procedure
1. Restore database from backup
2. Restore configuration files
3. Restart web server
4. Verify system functionality

## Support and Maintenance

### Regular Maintenance Tasks

#### Daily
- Review active alerts
- Check system performance
- Monitor disk space usage

#### Weekly
- Review performance reports
- Update caller ID pools
- Analyze campaign performance

#### Monthly
- Review and update thresholds
- Clean up old data
- Update system documentation

### Monitoring Checklist

- [ ] All instances are accessible
- [ ] Server metrics are being collected
- [ ] Caller ID rotations are working
- [ ] Alerts are being generated
- [ ] Reports are being generated
- [ ] Cron jobs are running
- [ ] Disk space is adequate
- [ ] Database performance is good

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Email: support@vicidial-support.com
- Documentation: https://docs.vicidial-support.com
- Issues: https://github.com/your-repo/vicidial-support/issues

---

**Note**: This system is designed for managing multiple Vicidial instances. Ensure you have proper authorization to access and monitor the Vicidial systems you're connecting to. 