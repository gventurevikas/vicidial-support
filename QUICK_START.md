# Vicidial Support System - Quick Start Guide

## 🚀 Quick Start

The Vicidial Support System is now ready to run! Here's how to get started:

### 1. Check System Status

```bash
./status.sh
```

This will show you the current status of all components.

### 2. Start the Server

```bash
./start-server.sh
```

The server will start on http://localhost:8000

### 3. Access the Application

- **Main Application**: http://localhost:8000
- **Health Check**: http://localhost:8000/health
- **API Base**: http://localhost:8000/api

## 📁 Project Structure

```
vicidial-support/
├── server.php              # Main server script
├── autoload.php            # Autoloader (works without Composer)
├── start-server.sh         # Server startup script
├── setup.sh               # Project setup script
├── status.sh              # Status check script
├── pytest                 # Test runner
├── composer.json          # PHP dependencies
├── public/                # Static files
│   ├── css/app.css       # Main stylesheet
│   └── js/app.js         # Main JavaScript
├── src/                   # Application source code
├── views/                 # PHP view files
├── config/                # Configuration files
├── tests/                 # Test files
└── logs/                  # Server logs
```

## 🛠️ Available Commands

### Server Management
```bash
./start-server.sh          # Start server
./start-server.sh stop     # Stop server
./start-server.sh --check  # Check dependencies
./status.sh               # Check system status
```

### Development
```bash
./setup.sh                # Setup project
./pytest                  # Run tests
./pytest --coverage       # Run tests with coverage
```

### Configuration
```bash
# Edit database configuration
nano config/database.php

# Set environment variables
export DB_HOST=localhost
export DB_DATABASE=vicidial_support
export DB_USERNAME=root
export DB_PASSWORD=your_password
```

## 🔧 Troubleshooting

### Server Won't Start
```bash
# Check if port is in use
lsof -i :8000

# Kill existing process
pkill -f "php -S"

# Start on different port
./start-server.sh -p 8080
```

### Database Connection Issues
```bash
# Check database configuration
cat config/database.php

# Test database connection
php -r "
require 'autoload.php';
try {
    \$db = new PDO('mysql:host=localhost;dbname=vicidial_support', 'root', '');
    echo 'Database connection successful\n';
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . '\n';
}
"
```

### Missing Dependencies
```bash
# Install Composer dependencies
composer install

# Or use basic autoloader (already working)
./setup.sh --skip-deps
```

## 📊 API Endpoints

### Health Check
```bash
curl http://localhost:8000/health
```

### Data Endpoints
```bash
# Get instances
curl http://localhost:8000/api/instances

# Get campaigns
curl http://localhost:8000/api/campaigns

# Get alerts
curl http://localhost:8000/api/alerts
```

## 🎨 Features

### ✅ Working Features
- **Server**: PHP development server with routing
- **Authentication**: Session-based login system
- **API**: RESTful API endpoints
- **Frontend**: Modern CSS and JavaScript
- **Testing**: PHPUnit test suite with pytest-style runner
- **Monitoring**: Health check and status monitoring
- **Security**: CSRF protection and security headers

### 🔄 Auto-refresh Features
- Dashboard statistics update every 30 seconds
- Real-time server health monitoring
- Automatic error logging and reporting

## 📝 Next Steps

1. **Configure Database**: Update `config/database.php` with your database credentials
2. **Add Vicidial Instances**: Use the web interface to add your Vicidial servers
3. **Set Up Monitoring**: Configure alerts and thresholds
4. **Customize Views**: Modify the PHP view files in `views/` directory
5. **Add Tests**: Create new test files in `tests/` directory

## 📚 Documentation

- **Server Setup**: See `SERVER_README.md`
- **Testing**: See `PYTEST_README.md`
- **API Reference**: See inline documentation in `server.php`

## 🆘 Support

If you encounter issues:

1. Check the status: `./status.sh`
2. View server logs: `tail -f logs/server.log`
3. Test health endpoint: `curl http://localhost:8000/health`
4. Check PHP errors: `tail -f logs/php_errors.log`

## 🎉 Success!

Your Vicidial Support System is now running successfully! 

- **Server**: ✅ Running on http://localhost:8000
- **Health Check**: ✅ Responding correctly
- **API**: ✅ Ready for frontend communication
- **Testing**: ✅ PHPUnit and pytest-style runner available
- **Documentation**: ✅ Comprehensive guides available

You can now start building your Vicidial monitoring and management system! 