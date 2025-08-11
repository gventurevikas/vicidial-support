<?php
/**
 * Vicidial Support System - Development Server
 * 
 * This script provides a simple development server for the Vicidial Support System.
 * It handles routing, authentication, and serves the application.
 */

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Define application paths
define('APP_ROOT', __DIR__);
define('PUBLIC_DIR', APP_ROOT . '/public');
define('SRC_DIR', APP_ROOT . '/src');
define('VIEWS_DIR', APP_ROOT . '/views');
define('CONFIG_DIR', APP_ROOT . '/config');

// Autoloader
require_once APP_ROOT . '/autoload.php';

// Load configuration
$config = require CONFIG_DIR . '/database.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Handle CORS for API requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    exit(0);
}

// Get request URI and method
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Remove query string from URI
$requestUri = parse_url($requestUri, PHP_URL_PATH);

// Remove base path if running in subdirectory
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/') {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Default to root if empty
if (empty($requestUri)) {
    $requestUri = '/';
}

// Initialize database connection
try {
    $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    echo "Database connection failed. Please check your configuration.";
    exit(1);
}

// Route handling function
function route($path, $method = 'GET', $handler = null) {
    global $requestUri, $requestMethod;
    
    if ($requestUri === $path && $requestMethod === $method) {
        if (is_callable($handler)) {
            return $handler();
        } else {
            return $handler;
        }
    }
    return false;
}

// Authentication middleware
function requireAuth() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: /login');
        exit(0);
    }
}

// API response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit(0);
}

// Error handler
function handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error = [
        'type' => 'Error',
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    
    error_log("PHP Error: " . json_encode($error));
    
    if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
        jsonResponse(['error' => 'Internal server error'], 500);
    } else {
        http_response_code(500);
        echo "An error occurred. Please check the logs.";
    }
    
    return true;
}

// Set error handler
set_error_handler('handleError');

// Exception handler
function handleException($exception) {
    $error = [
        'type' => get_class($exception),
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ];
    
    error_log("PHP Exception: " . json_encode($error));
    
    if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
        jsonResponse(['error' => 'Internal server error'], 500);
    } else {
        http_response_code(500);
        echo "An exception occurred. Please check the logs.";
    }
}

// Set exception handler
set_exception_handler('handleException');

// API Routes
if (strpos($requestUri, '/api/') === 0) {
    // API authentication middleware
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    
    // API routes
    switch ($requestUri) {
        case '/api/instances':
            if ($requestMethod === 'GET') {
                $controller = new \VicidialSupport\Controllers\InstanceController($dbManager->getSupportConnection());
                $result = $controller->getAll();
                jsonResponse($result);
            }
            break;
            
        case '/api/campaigns':
            if ($requestMethod === 'GET') {
                $controller = new \VicidialSupport\Controllers\CampaignController($dbManager->getSupportConnection());
                $result = $controller->getAll();
                jsonResponse($result);
            }
            break;
            
        case '/api/caller-ids':
            if ($requestMethod === 'GET') {
                $controller = new \VicidialSupport\Controllers\CallerIdController($dbManager->getSupportConnection());
                $result = $controller->getAll();
                jsonResponse($result);
            }
            break;
            
        case '/api/lists':
            if ($requestMethod === 'GET') {
                $controller = new \VicidialSupport\Controllers\ListController($dbManager->getSupportConnection());
                $result = $controller->getAll();
                jsonResponse($result);
            }
            break;
            
        case '/api/servers':
            if ($requestMethod === 'GET') {
                $controller = new \VicidialSupport\Controllers\ServerController($dbManager->getSupportConnection());
                $result = $controller->getAll();
                jsonResponse($result);
            }
            break;
            
        case '/api/alerts':
            if ($requestMethod === 'GET') {
                $controller = new \VicidialSupport\Controllers\AlertController($dbManager->getSupportConnection());
                $result = $controller->getAll();
                jsonResponse($result);
            }
            break;
            
        case '/api/reports':
            if ($requestMethod === 'GET') {
                $controller = new \VicidialSupport\Controllers\ReportController($dbManager->getSupportConnection());
                $type = $_GET['type'] ?? 'performance';
                $days = $_GET['days'] ?? 30;
                $result = $controller->generateCustomReport($type, ['days' => $days]);
                jsonResponse($result);
            }
            break;
            
        default:
            jsonResponse(['error' => 'API endpoint not found'], 404);
    }
}

// Web Routes
switch ($requestUri) {
    case '/':
    case '/dashboard':
        requireAuth();
        include VIEWS_DIR . '/dashboard.php';
        break;
        
    case '/login':
        if ($requestMethod === 'POST') {
            $controller = new \VicidialSupport\Controllers\AuthController($dbManager->getSupportConnection());
            $result = $controller->login();
            
            if ($result['success']) {
                header('Location: /dashboard');
                exit(0);
            } else {
                $error = $result['error'];
                include VIEWS_DIR . '/login.php';
            }
        } else {
            include VIEWS_DIR . '/login.php';
        }
        break;
        
    case '/logout':
        $controller = new \VicidialSupport\Controllers\AuthController($dbManager->getSupportConnection());
        $controller->logout();
        header('Location: /login');
        exit(0);
        break;
        
    case '/instances':
        requireAuth();
        include VIEWS_DIR . '/instances.php';
        break;
        
    case '/campaigns':
        requireAuth();
        include VIEWS_DIR . '/campaigns.php';
        break;
        
    case '/caller-ids':
        requireAuth();
        include VIEWS_DIR . '/caller-ids.php';
        break;
        
    case '/lists':
        requireAuth();
        include VIEWS_DIR . '/lists.php';
        break;
        
    case '/servers':
        requireAuth();
        include VIEWS_DIR . '/servers.php';
        break;
        
    case '/alerts':
        requireAuth();
        include VIEWS_DIR . '/alerts.php';
        break;
        
    case '/reports':
        requireAuth();
        include VIEWS_DIR . '/reports.php';
        break;
        
    case '/health':
        // Health check endpoint
        $health = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'database' => 'connected'
        ];
        jsonResponse($health);
        break;
        
    default:
        // Check if it's a static file
        $staticFile = PUBLIC_DIR . $requestUri;
        if (file_exists($staticFile) && is_file($staticFile)) {
            $extension = pathinfo($staticFile, PATHINFO_EXTENSION);
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'ico' => 'image/x-icon',
                'svg' => 'image/svg+xml'
            ];
            
            if (isset($mimeTypes[$extension])) {
                header('Content-Type: ' . $mimeTypes[$extension]);
            }
            
            readfile($staticFile);
            exit(0);
        }
        
        // 404 Not Found
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
        echo "<p>The requested page '$requestUri' could not be found.</p>";
        echo "<p><a href='/'>Go to Dashboard</a></p>";
        break;
} 