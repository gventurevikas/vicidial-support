#!/bin/bash
"""
Vicidial Support System - Status Check Script

This script checks the current status of the Vicidial Support System.
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

# Function to check if server is running
check_server() {
    if curl -s http://localhost:8000/health > /dev/null 2>&1; then
        print_color $GREEN "✓ Server is running on http://localhost:8000"
        return 0
    else
        print_color $RED "✗ Server is not running"
        return 1
    fi
}

# Function to check database connection
check_database() {
    if [ -f "config/database.php" ]; then
        print_color $GREEN "✓ Database configuration exists"
        return 0
    else
        print_color $YELLOW "⚠ Database configuration missing"
        return 1
    fi
}

# Function to check dependencies
check_dependencies() {
    if [ -d "vendor" ]; then
        print_color $GREEN "✓ Composer dependencies installed"
    else
        print_color $YELLOW "⚠ Composer dependencies not installed (using basic autoloader)"
    fi
    
    if command -v php > /dev/null 2>&1; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null)
        print_color $GREEN "✓ PHP $PHP_VERSION found"
    else
        print_color $RED "✗ PHP not found"
        return 1
    fi
    
    if command -v composer > /dev/null 2>&1; then
        print_color $GREEN "✓ Composer found"
    else
        print_color $YELLOW "⚠ Composer not found"
    fi
}

# Function to check files
check_files() {
    local required_files=(
        "server.php"
        "autoload.php"
        "start-server.sh"
        "setup.sh"
        "composer.json"
        "public/css/app.css"
        "public/js/app.js"
    )
    
    local missing_files=()
    
    for file in "${required_files[@]}"; do
        if [ -f "$file" ]; then
            print_color $GREEN "✓ $file"
        else
            print_color $RED "✗ $file (missing)"
            missing_files+=("$file")
        fi
    done
    
    if [ ${#missing_files[@]} -eq 0 ]; then
        return 0
    else
        return 1
    fi
}

# Function to check directories
check_directories() {
    local required_dirs=(
        "src"
        "views"
        "config"
        "logs"
        "public"
        "tests"
    )
    
    local missing_dirs=()
    
    for dir in "${required_dirs[@]}"; do
        if [ -d "$dir" ]; then
            print_color $GREEN "✓ $dir/"
        else
            print_color $RED "✗ $dir/ (missing)"
            missing_dirs+=("$dir")
        fi
    done
    
    if [ ${#missing_dirs[@]} -eq 0 ]; then
        return 0
    else
        return 1
    fi
}

# Function to show server info
show_server_info() {
    print_color $BLUE "Server Information:"
    echo "  URL: http://localhost:8000"
    echo "  Health Check: http://localhost:8000/health"
    echo "  API Base: http://localhost:8000/api"
    echo ""
}

# Function to show usage
show_usage() {
    print_color $BLUE "Available Commands:"
    echo "  ./start-server.sh          # Start the server"
    echo "  ./start-server.sh stop     # Stop the server"
    echo "  ./setup.sh                 # Setup the project"
    echo "  ./pytest                   # Run tests"
    echo ""
}

# Main function
main() {
    print_color $BLUE "Vicidial Support System - Status Check"
    print_color $BLUE "======================================"
    echo ""
    
    # Check server status
    print_color $BLUE "Server Status:"
    if check_server; then
        SERVER_RUNNING=true
    else
        SERVER_RUNNING=false
    fi
    echo ""
    
    # Check dependencies
    print_color $BLUE "Dependencies:"
    check_dependencies
    echo ""
    
    # Check files
    print_color $BLUE "Required Files:"
    check_files
    echo ""
    
    # Check directories
    print_color $BLUE "Required Directories:"
    check_directories
    echo ""
    
    # Check database
    print_color $BLUE "Database:"
    check_database
    echo ""
    
    # Show server info if running
    if [ "$SERVER_RUNNING" = true ]; then
        show_server_info
    fi
    
    # Show usage
    show_usage
    
    # Summary
    print_color $BLUE "Summary:"
    if [ "$SERVER_RUNNING" = true ]; then
        print_color $GREEN "✓ System is ready and running!"
        echo "  You can access the application at http://localhost:8000"
    else
        print_color $YELLOW "⚠ System is set up but server is not running"
        echo "  Run './start-server.sh' to start the server"
    fi
    echo ""
}

# Run main function
main "$@" 