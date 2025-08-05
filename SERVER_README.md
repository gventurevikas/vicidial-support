# Vicidial Support System - Development Server

A comprehensive PHP development server setup for the Vicidial Support System with routing, authentication, and API endpoints.

## Features

- 🚀 **Built-in Router**: Handles all application routes
- 🔐 **Authentication**: Session-based authentication system
- 📡 **API Endpoints**: RESTful API for frontend communication
- 🛡️ **Security**: CSRF protection, input validation, security headers
- 📊 **Error Handling**: Comprehensive error logging and display
- 🎨 **Static Files**: Serves CSS, JS, and image files
- 🔍 **Health Check**: Built-in health monitoring endpoint

## Quick Start

### 1. Start the Server

```bash
# Start with default settings (localhost:8000)
./start-server.sh

# Start on custom port
./start-server.sh -p 8080

# Start on all interfaces
./start-server.sh -H 0.0.0.0

# Start with verbose output
./start-server.sh -v

# Check dependencies only
./start-server.sh --check
```

### 2. Access the Application

- **Main Application**: http://localhost:8000
- **Health Check**: http://localhost:8000/health
- **API Base**: http://localhost:8000/api

### 3. Stop the Server

```bash
# Stop the server
./start-server.sh stop

# Or use Ctrl+C in the terminal
```

## Server Architecture

### File Structure

```
vicidial-support/
├── server.php              # Main server script
├── start-server.sh         # Server startup script
├── public/                 # Static files
│   ├── css/
│   │   └── app.css        # Main stylesheet
│   ├── js/
│   │   └── app.js         # Main JavaScript
│   └── images/            # Image assets
├── views/                  # PHP view files
├── src/                    # Application source code
├── config/                 # Configuration files
└── logs/                   # Server logs
```

### Server Components

#### 1. Main Server Script (`server.php`)

The main server script handles:

- **Request Routing**: Routes HTTP requests to appropriate handlers
- **Authentication**: Session-based user authentication
- **API Endpoints**: RESTful API for frontend communication
- **Static Files**: Serves CSS, JS, and image files
- **Error Handling**: Comprehensive error logging and display
- **Security**: CSRF protection and security headers

#### 2. Startup Script (`start-server.sh`)

The startup script provides:

- **Dependency Checking**: Verifies PHP, Composer, and configuration
- **Port Management**: Checks for port availability
- **Process Management**: Starts and stops the server
- **Browser Integration**: Automatically opens browser
- **Logging**: Redirects output to log files

## Configuration

### Environment Variables

You can configure the server using environment variables:

```bash
# Server configuration
export VICIDIAL_PORT=8000
export VICIDIAL_HOST=localhost
export VICIDIAL_DOC_ROOT=public
export VICIDIAL_OPEN_BROWSER=true

# Start server
./start-server.sh
```

### Database Configuration

The server requires a database configuration file at `config/database.php`:

```php
<?php
return [
    'default' => 'vicidial_support',
    'connections' => [
        'vicidial_support' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'database' => $_ENV['DB_DATABASE'] ?? 'vicidial_support',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ],
    ],
];
```

## API Endpoints

### Authentication

- `POST /login` - User login
- `GET /logout` - User logout
- `GET /health` - Health check

### Data Endpoints

- `GET /api/instances` - Get all Vicidial instances
- `GET /api/campaigns` - Get all campaigns
- `GET /api/caller-ids` - Get all caller IDs
- `GET /api/lists` - Get all lists
- `GET /api/servers` - Get all servers
- `GET /api/alerts` - Get all alerts
- `GET /api/reports` - Generate reports

### Example API Usage

```javascript
// Get instances
const response = await fetch('/api/instances');
const instances = await response.json();

// Get reports
const reports = await fetch('/api/reports?type=performance&days=30');
const reportData = await reports.json();
```

## Web Routes

### Main Pages

- `/` or `/dashboard` - Main dashboard (requires authentication)
- `/login` - Login page
- `/instances` - Instance management
- `/campaigns` - Campaign management
- `/caller-ids` - Caller ID management
- `/lists` - List management
- `/servers` - Server monitoring
- `/alerts` - Alert management
- `/reports` - Report generation

### Static Files

The server automatically serves static files from the `public/` directory:

- CSS files: `/css/app.css`
- JavaScript files: `/js/app.js`
- Images: `/images/logo.png`

## Security Features

### Security Headers

The server automatically sets security headers:

```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
```

### CORS Support

For API requests, CORS headers are automatically set:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### Authentication

Session-based authentication with automatic redirect:

```php
function requireAuth() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: /login');
        exit(0);
    }
}
```

## Error Handling

### Error Types

1. **Database Errors**: Connection failures and query errors
2. **Authentication Errors**: Invalid login attempts
3. **API Errors**: Invalid endpoints or parameters
4. **File Errors**: Missing static files or views

### Error Responses

- **API Errors**: JSON response with error details
- **Web Errors**: HTML error pages with navigation
- **Logging**: All errors are logged to `logs/server.log`

### Example Error Response

```json
{
    "success": false,
    "error": "Database connection failed",
    "timestamp": "2024-01-01 12:00:00"
}
```

## Development Workflow

### 1. Start Development

```bash
# Start server
./start-server.sh

# Make changes to files
# Server automatically serves updated files
```

### 2. View Logs

```bash
# View server logs
tail -f logs/server.log

# View PHP errors
tail -f logs/php_errors.log
```

### 3. Debug Mode

```bash
# Start with verbose output
./start-server.sh -v

# Check server health
curl http://localhost:8000/health
```

## Production Deployment

### 1. Apache Configuration

For production, use Apache with mod_rewrite:

```apache
<VirtualHost *:80>
    ServerName vicidial-support.local
    DocumentRoot /var/www/vicidial-support/public
    
    <Directory /var/www/vicidial-support/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/vicidial_error.log
    CustomLog ${APACHE_LOG_DIR}/vicidial_access.log combined
</VirtualHost>
```

### 2. Nginx Configuration

For Nginx:

```nginx
server {
    listen 80;
    server_name vicidial-support.local;
    root /var/www/vicidial-support/public;
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

### 3. Environment Setup

```bash
# Set production environment variables
export VICIDIAL_ENV=production
export VICIDIAL_DEBUG=false

# Set database credentials
export DB_HOST=localhost
export DB_DATABASE=vicidial_support
export DB_USERNAME=vicidial_user
export DB_PASSWORD=secure_password
```

## Monitoring and Logging

### Health Check

The server provides a health check endpoint:

```bash
curl http://localhost:8000/health
```

Response:
```json
{
    "status": "healthy",
    "timestamp": "2024-01-01 12:00:00",
    "version": "1.0.0",
    "database": "connected"
}
```

### Log Files

- `logs/server.log` - Server access and error logs
- `logs/php_errors.log` - PHP error logs
- `logs/api.log` - API request logs

### Monitoring Commands

```bash
# Monitor server logs
tail -f logs/server.log

# Check server status
curl -s http://localhost:8000/health | jq

# Monitor PHP processes
ps aux | grep php
```

## Troubleshooting

### Common Issues

1. **Port Already in Use**
   ```bash
   # Check what's using the port
   lsof -i :8000
   
   # Kill the process
   kill -9 <PID>
   
   # Or use a different port
   ./start-server.sh -p 8080
   ```

2. **Database Connection Failed**
   ```bash
   # Check database configuration
   cat config/database.php
   
   # Test database connection
   php -r "require 'config/database.php';"
   ```

3. **Permission Denied**
   ```bash
   # Make script executable
   chmod +x start-server.sh
   
   # Check file permissions
   ls -la start-server.sh
   ```

4. **PHP Not Found**
   ```bash
   # Install PHP
   sudo apt-get install php8.1 php8.1-mysql php8.1-curl
   
   # Or on macOS
   brew install php
   ```

### Debug Commands

```bash
# Check PHP version
php --version

# Check Composer
composer --version

# Check server status
./start-server.sh --check

# View server logs
tail -f logs/server.log

# Test database connection
php -r "
require 'vendor/autoload.php';
try {
    \$db = new PDO('mysql:host=localhost;dbname=vicidial_support', 'root', '');
    echo 'Database connection successful\n';
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . '\n';
}
"
```

## Performance Optimization

### 1. Enable OPcache

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
```

### 2. Enable Compression

```apache
# .htaccess
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

### 3. Cache Static Files

```apache
# .htaccess
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
```

## Security Best Practices

### 1. Environment Variables

Never hardcode sensitive information:

```bash
# Use environment variables
export DB_PASSWORD=secure_password
export APP_SECRET=your_secret_key
```

### 2. Input Validation

Always validate and sanitize input:

```php
// Validate input
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
```

### 3. SQL Injection Prevention

Use prepared statements:

```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
```

### 4. CSRF Protection

Implement CSRF tokens:

```php
// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validate CSRF token
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

## Support

For issues and questions:

1. Check the troubleshooting section above
2. Review server logs in `logs/` directory
3. Test the health endpoint: `curl http://localhost:8000/health`
4. Check PHP error logs: `tail -f logs/php_errors.log`
5. Verify database connection and configuration

## License

This server setup is part of the Vicidial Support System project and follows the same license terms. 