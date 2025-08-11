#!/bin/bash
"""
Vicidial Support System - Development Server Starter

This script starts the PHP development server for the Vicidial Support System.
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

# Function to check dependencies
check_dependencies() {
    print_color $BLUE "Checking dependencies..."
    
    # Check if PHP is available
    if ! command -v php &> /dev/null; then
        print_color $RED "Error: PHP is required but not installed"
        exit 1
    fi
    
    # Check PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
    REQUIRED_VERSION="8.1"
    
    if [ "$(printf '%s\n' "$REQUIRED_VERSION" "$PHP_VERSION" | sort -V | head -n1)" != "$REQUIRED_VERSION" ]; then
        print_color $RED "Error: PHP $REQUIRED_VERSION+ is required, found $PHP_VERSION"
        exit 1
    fi
    
    print_color $GREEN "✓ PHP $PHP_VERSION found"
    
    # Check if Composer is available
    if ! command -v composer &> /dev/null; then
        print_color $YELLOW "Warning: Composer not found. Some features may not work."
    else
        print_color $GREEN "✓ Composer found"
    fi
    
    # Check if vendor directory exists
    if [ ! -d "vendor" ]; then
        print_color $YELLOW "Warning: vendor directory not found. Run 'composer install' to install dependencies."
    else
        print_color $GREEN "✓ Dependencies installed"
    fi
    
    # Check if database configuration exists
    if [ ! -f "config/database.php" ]; then
        print_color $YELLOW "Warning: Database configuration not found. Create config/database.php"
    else
        print_color $GREEN "✓ Database configuration found"
    fi
}

# Function to show help
show_help() {
    cat << EOF
Vicidial Support System - Development Server

Usage: ./start-server.sh [OPTIONS]

Options:
    -h, --help              Show this help message
    -p, --port PORT         Set server port (default: 8000)
    -H, --host HOST         Set server host (default: localhost)
    -d, --document-root     Set document root (default: public)
    -c, --check             Check dependencies only
    -v, --verbose           Verbose output
    --no-browser            Don't open browser automatically

Examples:
    ./start-server.sh                    # Start server on localhost:8000
    ./start-server.sh -p 8080           # Start server on port 8080
    ./start-server.sh -H 0.0.0.0        # Start server on all interfaces
    ./start-server.sh --check            # Check dependencies only

Environment Variables:
    VICIDIAL_PORT          Server port (default: 8000)
    VICIDIAL_HOST          Server host (default: localhost)
    VICIDIAL_DOC_ROOT      Document root (default: public)
    VICIDIAL_OPEN_BROWSER  Open browser (default: true)
EOF
}

# Function to show version
show_version() {
    echo "Vicidial Support System Development Server v1.0.0"
    echo "PHP-based development server for Vicidial Support System"
}

# Function to start server
start_server() {
    local port=$1
    local host=$2
    local doc_root=$3
    local verbose=$4
    
    print_color $BLUE "Starting Vicidial Support System Development Server..."
    print_color $BLUE "Server: http://$host:$port"
    print_color $BLUE "Document Root: $doc_root"
    print_color $BLUE "Press Ctrl+C to stop the server"
    echo ""
    
    # Create logs directory if it doesn't exist
    mkdir -p logs
    
    # Start PHP development server
    if [ "$verbose" = "true" ]; then
        php -S "$host:$port" -t "$doc_root" server.php
    else
        php -S "$host:$port" -t "$doc_root" server.php > logs/server.log 2>&1 &
        SERVER_PID=$!
        
        print_color $GREEN "Server started with PID: $SERVER_PID"
        print_color $BLUE "Logs: logs/server.log"
        
        # Wait a moment for server to start
        sleep 2
        
        # Check if server is running
        if curl -s "http://$host:$port/health" > /dev/null 2>&1; then
            print_color $GREEN "✓ Server is running and responding"
            
            # Open browser if requested
            if [ "$OPEN_BROWSER" = "true" ]; then
                print_color $BLUE "Opening browser..."
                if command -v open &> /dev/null; then
                    open "http://$host:$port"
                elif command -v xdg-open &> /dev/null; then
                    xdg-open "http://$host:$port"
                elif command -v start &> /dev/null; then
                    start "http://$host:$port"
                fi
            fi
            
            print_color $GREEN "Server is ready! Visit http://$host:$port"
            print_color $YELLOW "Press Ctrl+C to stop the server"
            
            # Wait for interrupt
            wait $SERVER_PID
        else
            print_color $RED "✗ Server failed to start"
            kill $SERVER_PID 2>/dev/null
            exit 1
        fi
    fi
}

# Function to stop server
stop_server() {
    print_color $YELLOW "Stopping server..."
    
    # Find and kill PHP server processes
    pkill -f "php -S" 2>/dev/null
    
    print_color $GREEN "Server stopped"
}

# Main script logic
main() {
    # Default values
    PORT=${VICIDIAL_PORT:-8000}
    HOST=${VICIDIAL_HOST:-localhost}
    DOC_ROOT=${VICIDIAL_DOC_ROOT:-public}
    OPEN_BROWSER=${VICIDIAL_OPEN_BROWSER:-true}
    VERBOSE=false
    CHECK_ONLY=false
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            --version)
                show_version
                exit 0
                ;;
            -p|--port)
                PORT="$2"
                shift 2
                ;;
            -H|--host)
                HOST="$2"
                shift 2
                ;;
            -d|--document-root)
                DOC_ROOT="$2"
                shift 2
                ;;
            -c|--check)
                CHECK_ONLY=true
                shift
                ;;
            -v|--verbose)
                VERBOSE=true
                shift
                ;;
            --no-browser)
                OPEN_BROWSER=false
                shift
                ;;
            stop)
                stop_server
                exit 0
                ;;
            *)
                print_color $RED "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
    
    # Check dependencies
    check_dependencies
    
    if [ "$CHECK_ONLY" = "true" ]; then
        print_color $GREEN "Dependencies check completed"
        exit 0
    fi
    
    # Check if document root exists
    if [ ! -d "$DOC_ROOT" ]; then
        print_color $RED "Error: Document root '$DOC_ROOT' does not exist"
        exit 1
    fi
    
    # Check if server.php exists
    if [ ! -f "server.php" ]; then
        print_color $RED "Error: server.php not found in current directory"
        exit 1
    fi
    
    # Check if port is available
    if lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_color $YELLOW "Warning: Port $PORT is already in use"
        read -p "Do you want to continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
    
    # Start server
    start_server "$PORT" "$HOST" "$DOC_ROOT" "$VERBOSE"
}

# Handle Ctrl+C
trap 'print_color $YELLOW "\nStopping server..."; stop_server; exit 0' INT

# Run main function
main "$@" 