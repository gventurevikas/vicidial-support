<?php
/**
 * Simple Autoloader for Vicidial Support System
 * 
 * This file provides a basic autoloader when Composer is not available.
 * It automatically loads classes from the src/ directory.
 */

// Prevent multiple inclusions
if (defined('VICIDIAL_AUTOLOADER_LOADED')) {
    return;
}
define('VICIDIAL_AUTOLOADER_LOADED', true);

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base paths
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}
if (!defined('SRC_DIR')) {
    define('SRC_DIR', APP_ROOT . '/src');
}

// Simple autoloader function
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = SRC_DIR . '/' . str_replace('\\', '/', $class) . '.php';
    
    // Check if file exists
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});

// Mock functions for testing when dependencies are missing
if (!function_exists('password_hash')) {
    function password_hash($password, $algo) {
        return hash('sha256', $password);
    }
}

if (!function_exists('password_verify')) {
    function password_verify($password, $hash) {
        return hash('sha256', $password) === $hash;
    }
}

// Mock PDO if not available (for testing)
if (!class_exists('PDO')) {
    class PDO {
        public function __construct($dsn, $username = null, $password = null, $options = null) {
            throw new Exception('PDO not available - please install PHP PDO extension');
        }
    }
}

// Mock functions for session handling
if (!function_exists('session_start')) {
    function session_start($options = []) {
        // Mock session start
        return true;
    }
}

if (!function_exists('session_status')) {
    function session_status() {
        return PHP_SESSION_NONE;
    }
}

// Helper function to check if Composer autoloader exists
if (!function_exists('hasComposerAutoloader')) {
    function hasComposerAutoloader() {
        return file_exists(APP_ROOT . '/vendor/autoload.php');
    }
}

// Load Composer autoloader if available
if (hasComposerAutoloader()) {
    require_once APP_ROOT . '/vendor/autoload.php';
} else {
    // Create basic directory structure if it doesn't exist
    $directories = [
        SRC_DIR,
        SRC_DIR . '/Database',
        SRC_DIR . '/Models',
        SRC_DIR . '/Controllers',
        SRC_DIR . '/Services',
        APP_ROOT . '/views',
        APP_ROOT . '/config',
        APP_ROOT . '/logs',
        APP_ROOT . '/public/css',
        APP_ROOT . '/public/js',
        APP_ROOT . '/public/images'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Create basic database config if it doesn't exist
    if (!file_exists(APP_ROOT . '/config/database.php')) {
        $configContent = '<?php
return [
    "default" => "vicidial_support",
    "connections" => [
        "vicidial_support" => [
            "driver" => "mysql",
            "host" => $_ENV["DB_HOST"] ?? "localhost",
            "port" => $_ENV["DB_PORT"] ?? 3306,
            "database" => $_ENV["DB_DATABASE"] ?? "vicidial_support",
            "username" => $_ENV["DB_USERNAME"] ?? "root",
            "password" => $_ENV["DB_PASSWORD"] ?? "",
            "charset" => "utf8mb4",
            "collation" => "utf8mb4_unicode_ci",
            "prefix" => "",
            "strict" => true,
            "engine" => "InnoDB",
        ],
    ],
];';
        file_put_contents(APP_ROOT . '/config/database.php', $configContent);
    }
    
    echo "<!-- Composer autoloader not found. Using basic autoloader. -->\n";
    echo "<!-- Run 'composer install' to install dependencies. -->\n";
} 