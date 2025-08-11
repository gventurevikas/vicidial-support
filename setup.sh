#!/bin/bash
"""
Vicidial Support System - Setup Script

This script sets up the development environment for the Vicidial Support System.
"""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_color() {
    local color=$1
    shift
    echo -e "${color}$*${NC}"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check PHP version
check_php_version() {
    if command_exists php; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
        REQUIRED_VERSION="8.1"
        
        if [ "$(printf '%s\n' "$REQUIRED_VERSION" "$PHP_VERSION" | sort -V | head -n1)" != "$REQUIRED_VERSION" ]; then
            print_color $RED "Error: PHP $REQUIRED_VERSION+ is required, found $PHP_VERSION"
            return 1
        fi
        
        print_color $GREEN "✓ PHP $PHP_VERSION found"
        return 0
    else
        print_color $RED "Error: PHP is not installed"
        return 1
    fi
}

# Function to install Composer
install_composer() {
    if command_exists composer; then
        print_color $GREEN "✓ Composer found"
        return 0
    fi
    
    print_color $YELLOW "Installing Composer..."
    
    if command_exists curl; then
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    elif command_exists wget; then
        wget -O - https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    else
        print_color $RED "Error: Neither curl nor wget is available"
        return 1
    fi
    
    if command_exists composer; then
        print_color $GREEN "✓ Composer installed successfully"
        return 0
    else
        print_color $RED "Error: Failed to install Composer"
        return 1
    fi
}

# Function to create directory structure
create_directories() {
    print_color $BLUE "Creating directory structure..."
    
    local directories=(
        "src/Database"
        "src/Models"
        "src/Controllers"
        "src/Services"
        "views"
        "config"
        "logs"
        "public/css"
        "public/js"
        "public/images"
        "tests/Unit/Models"
        "tests/Unit/Controllers"
        "tests/Integration"
        "tests/Feature"
        "reports"
    )
    
    for dir in "${directories[@]}"; do
        if [ ! -d "$dir" ]; then
            mkdir -p "$dir"
            print_color $GREEN "✓ Created $dir"
        else
            print_color $YELLOW "⚠ Directory $dir already exists"
        fi
    done
}

# Function to create basic files
create_basic_files() {
    print_color $BLUE "Creating basic files..."
    
    # Create .htaccess if it doesn't exist
    if [ ! -f ".htaccess" ]; then
        cat > .htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ server.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Prevent access to sensitive files
<Files "*.env">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.json">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.lock">
    Order allow,deny
    Deny from all
</Files>
EOF
        print_color $GREEN "✓ Created .htaccess"
    fi
    
    # Create .gitignore if it doesn't exist
    if [ ! -f ".gitignore" ]; then
        cat > .gitignore << 'EOF'
# Dependencies
/vendor/
/node_modules/

# Environment files
.env
.env.local
.env.production

# Logs
/logs/*
!/logs/.gitkeep

# Cache
/cache/
*.cache

# IDE files
.vscode/
.idea/
*.swp
*.swo

# OS files
.DS_Store
Thumbs.db

# Test coverage
/tests/coverage/
/tests/junit.xml
/tests/testdox.html
/tests/testdox.txt

# Reports
/reports/*
!/reports/.gitkeep

# Database
*.sqlite
*.db

# Temporary files
*.tmp
*.temp
EOF
        print_color $GREEN "✓ Created .gitignore"
    fi
    
    # Create .env.example if it doesn't exist
    if [ ! -f ".env.example" ]; then
        cat > .env.example << 'EOF'
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=vicidial_support
DB_USERNAME=root
DB_PASSWORD=

# Application Configuration
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Server Configuration
VICIDIAL_PORT=8000
VICIDIAL_HOST=localhost
VICIDIAL_DOC_ROOT=public
VICIDIAL_OPEN_BROWSER=true

# Security
APP_SECRET=your-secret-key-here
EOF
        print_color $GREEN "✓ Created .env.example"
    fi
}

# Function to install dependencies
install_dependencies() {
    print_color $BLUE "Installing dependencies..."
    
    if command_exists composer; then
        composer install --no-dev --optimize-autoloader
        if [ $? -eq 0 ]; then
            print_color $GREEN "✓ Dependencies installed successfully"
        else
            print_color $YELLOW "⚠ Some dependencies failed to install, but basic autoloader will work"
        fi
    else
        print_color $YELLOW "⚠ Composer not found, using basic autoloader"
    fi
}

# Function to set permissions
set_permissions() {
    print_color $BLUE "Setting file permissions..."
    
    chmod +x start-server.sh 2>/dev/null
    chmod +x setup.sh 2>/dev/null
    chmod +x pytest 2>/dev/null
    
    chmod 755 logs 2>/dev/null
    chmod 755 reports 2>/dev/null
    
    print_color $GREEN "✓ Permissions set"
}

# Function to test setup
test_setup() {
    print_color $BLUE "Testing setup..."
    
    # Test PHP
    if php -r "echo 'PHP is working';" 2>/dev/null; then
        print_color $GREEN "✓ PHP test passed"
    else
        print_color $RED "✗ PHP test failed"
        return 1
    fi
    
    # Test autoloader
    if php -r "require 'autoload.php'; echo 'Autoloader is working';" 2>/dev/null; then
        print_color $GREEN "✓ Autoloader test passed"
    else
        print_color $RED "✗ Autoloader test failed"
        return 1
    fi
    
    # Test server script
    if php -r "require 'server.php'; echo 'Server script is working';" 2>/dev/null; then
        print_color $GREEN "✓ Server script test passed"
    else
        print_color $YELLOW "⚠ Server script test failed (this is normal if database is not configured)"
    fi
    
    return 0
}

# Function to show help
show_help() {
    cat << EOF
Vicidial Support System - Setup Script

Usage: ./setup.sh [OPTIONS]

Options:
    -h, --help              Show this help message
    --skip-deps             Skip dependency installation
    --skip-tests            Skip setup tests
    --force                 Force setup even if directories exist

Examples:
    ./setup.sh              # Full setup
    ./setup.sh --skip-deps  # Setup without installing dependencies
    ./setup.sh --skip-tests # Setup without running tests

This script will:
1. Check PHP version and install Composer if needed
2. Create directory structure
3. Create basic configuration files
4. Install dependencies (if Composer is available)
5. Set file permissions
6. Test the setup
EOF
}

# Main function
main() {
    print_color $BLUE "Vicidial Support System - Setup Script"
    print_color $BLUE "======================================"
    echo ""
    
    # Parse arguments
    SKIP_DEPS=false
    SKIP_TESTS=false
    FORCE=false
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            --skip-deps)
                SKIP_DEPS=true
                shift
                ;;
            --skip-tests)
                SKIP_TESTS=true
                shift
                ;;
            --force)
                FORCE=true
                shift
                ;;
            *)
                print_color $RED "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
    
    # Check PHP version
    if ! check_php_version; then
        exit 1
    fi
    
    # Install Composer if needed
    if ! install_composer; then
        print_color $YELLOW "Warning: Composer installation failed, but basic setup will continue"
    fi
    
    # Create directories
    create_directories
    
    # Create basic files
    create_basic_files
    
    # Install dependencies
    if [ "$SKIP_DEPS" = false ]; then
        install_dependencies
    else
        print_color $YELLOW "⚠ Skipping dependency installation"
    fi
    
    # Set permissions
    set_permissions
    
    # Test setup
    if [ "$SKIP_TESTS" = false ]; then
        if test_setup; then
            print_color $GREEN "✓ Setup completed successfully!"
        else
            print_color $YELLOW "⚠ Setup completed with warnings"
        fi
    else
        print_color $YELLOW "⚠ Skipping setup tests"
    fi
    
    echo ""
    print_color $GREEN "Setup completed!"
    echo ""
    print_color $BLUE "Next steps:"
    echo "1. Configure your database in config/database.php"
    echo "2. Start the server: ./start-server.sh"
    echo "3. Access the application: http://localhost:8000"
    echo ""
    print_color $YELLOW "For more information, see SERVER_README.md"
}

# Run main function
main "$@" 