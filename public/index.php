<?php
/**
 * Vicidial Support System - Main Entry Point
 */

// Load environment variables
require_once __DIR__ . '/../vendor/autoload.php';

// Start session
session_start();

// Load configuration
$config = require __DIR__ . '/../config/database.php';

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove base path if exists (only for Apache/Nginx, not for built-in server)
$basePath = '/vicidial-support';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Authentication middleware
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
}

// Route handling
switch ($path) {
    case '':
    case '/':
        requireAuth();
        include __DIR__ . '/../views/dashboard.php';
        break;
        
    case '/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handleLogin();
        } else {
            include __DIR__ . '/../views/login.php';
        }
        break;
        
    case '/logout':
        session_destroy();
        header('Location: /login');
        break;
        
    case '/dashboard':
        requireAuth();
        include __DIR__ . '/../views/dashboard.php';
        break;
        
    case '/instances':
        requireAuth();
        include __DIR__ . '/../views/instances.php';
        break;
        
    case '/campaigns':
        requireAuth();
        include __DIR__ . '/../views/campaigns.php';
        break;
        
    case '/caller-ids':
        requireAuth();
        include __DIR__ . '/../views/caller-ids.php';
        break;
        
    case '/lists':
        requireAuth();
        include __DIR__ . '/../views/lists.php';
        break;
        
    case '/servers':
        requireAuth();
        include __DIR__ . '/../views/servers.php';
        break;
        
    case '/alerts':
        requireAuth();
        include __DIR__ . '/../views/alerts.php';
        break;
        
    case '/reports':
        requireAuth();
        include __DIR__ . '/../views/reports.php';
        break;
        
    case '/api/instances':
        requireAuth();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $result = addInstance($data);
            echo json_encode($result);
        } else {
            echo json_encode(getInstances());
        }
        break;
        
    case '/api/campaigns':
        requireAuth();
        header('Content-Type: application/json');
        $instanceId = $_GET['instance_id'] ?? null;
        echo json_encode(getCampaigns($instanceId));
        break;
        
    case (preg_match('/^\/api\/campaigns\/(\d+)\/details$/', $path, $matches) ? true : false):
        requireAuth();
        header('Content-Type: application/json');
        $campaignId = $matches[1];
        echo json_encode(getCampaignDetails($campaignId));
        break;
        
    case (preg_match('/^\/api\/campaigns\/(\d+)\/performance$/', $path, $matches) ? true : false):
        requireAuth();
        header('Content-Type: application/json');
        $campaignId = $matches[1];
        echo json_encode(getCampaignPerformance($campaignId));
        break;
        
    case (preg_match('/^\/api\/caller-ids\/(\d+)\/details$/', $path, $matches) ? true : false):
        requireAuth();
        header('Content-Type: application/json');
        $callerId = $matches[1];
        echo json_encode(getCallerIDDetails($callerId));
        break;
        
    case '/api/caller-ids':
        requireAuth();
        header('Content-Type: application/json');
        $campaignId = $_GET['campaign_id'] ?? null;
        echo json_encode(getCallerIDs($campaignId));
        break;
        
    case '/api/lists':
        requireAuth();
        header('Content-Type: application/json');
        $instanceId = $_GET['instance_id'] ?? null;
        echo json_encode(getLists($instanceId));
        break;
        
    case '/api/servers':
        requireAuth();
        header('Content-Type: application/json');
        echo json_encode(getServers());
        break;
        
    case '/api/reports':
        requireAuth();
        header('Content-Type: application/json');
        $type = $_GET['type'] ?? 'campaign';
        echo json_encode(getReports($type));
        break;
        
    case '/api/server-metrics':
        requireAuth();
        header('Content-Type: application/json');
        $serverId = $_GET['server_id'] ?? null;
        echo json_encode(getServerMetrics($serverId));
        break;
        
    case '/api/alerts':
        requireAuth();
        header('Content-Type: application/json');
        echo json_encode(getAlerts());
        break;
        
    case '/api/rotate-caller-id':
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $result = rotateCallerID($data['caller_id'], $data['reason']);
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
        }
        break;
        
    case '/api/sync-instance':
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $result = syncInstance($data['instance_id']);
            header('Content-Type: application/json');
            echo json_encode($result);
        }
        break;
        
    case '/api/sync-all-instances':
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = syncAllInstances();
            header('Content-Type: application/json');
            echo json_encode($result);
        }
        break;
        
    case '/api/sync-status':
        requireAuth();
        header('Content-Type: application/json');
        echo json_encode(getSyncStatus());
        break;
        
    case '/api/test-connection':
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $result = testConnection($data['instance_id']);
            header('Content-Type: application/json');
            echo json_encode($result);
        }
        break;
        
    default:
        http_response_code(404);
        include __DIR__ . '/../views/404.php';
        break;
}

/**
 * Handle user login
 */
function handleLogin() {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Username and password are required';
        header('Location: /login');
        return;
    }
    
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        $stmt = $supportDb->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            
            // Update last login
            $stmt = $supportDb->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            header('Location: /dashboard');
        } else {
            $_SESSION['error'] = 'Invalid username or password';
            header('Location: /login');
        }
    } catch (\Exception $e) {
        $_SESSION['error'] = 'Login failed: ' . $e->getMessage();
        header('Location: /login');
    }
}

/**
 * Get instances for API
 */
function getInstances() {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $instances = $dbManager->getActiveInstances();
        return ['success' => true, 'data' => $instances];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get campaigns for API
 */
function getCampaigns($instanceId = null) {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        if ($instanceId) {
            $stmt = $supportDb->prepare("
                SELECT c.*, vi.instance_name 
                FROM campaigns c 
                JOIN vicidial_instances vi ON c.instance_id = vi.id 
                WHERE c.instance_id = ?
                ORDER BY c.campaign_name
            ");
            $stmt->execute([$instanceId]);
        } else {
            $stmt = $supportDb->prepare("
                SELECT c.*, vi.instance_name 
                FROM campaigns c 
                JOIN vicidial_instances vi ON c.instance_id = vi.id 
                ORDER BY vi.instance_name, c.campaign_name
            ");
            $stmt->execute();
        }
        
        $campaigns = $stmt->fetchAll();
        return ['success' => true, 'data' => $campaigns];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get campaign details for API
 */
function getCampaignDetails($campaignId) {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        $stmt = $supportDb->prepare("
            SELECT c.*, vi.instance_name
            FROM campaigns c
            JOIN vicidial_instances vi ON c.instance_id = vi.id
            WHERE c.id = ?
        ");
        $stmt->execute([$campaignId]);
        
        $campaign = $stmt->fetch();
        if ($campaign) {
            return ['success' => true, 'data' => $campaign];
        } else {
            return ['success' => false, 'error' => 'Campaign not found'];
        }
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get campaign performance for API
 */
function getCampaignPerformance($campaignId) {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        $stmt = $supportDb->prepare("
            SELECT 
                c.campaign_name,
                vi.instance_name,
                COUNT(ci.id) as total_caller_ids,
                COUNT(CASE WHEN ci.status = 'active' THEN 1 END) as active_caller_ids,
                COUNT(CASE WHEN ci.status = 'paused' THEN 1 END) as paused_caller_ids,
                COUNT(CASE WHEN ci.status = 'completed' THEN 1 END) as completed_caller_ids,
                COUNT(CASE WHEN ci.status = 'failed' THEN 1 END) as failed_caller_ids,
                COUNT(CASE WHEN ci.status = 'abandoned' THEN 1 END) as abandoned_caller_ids,
                COUNT(CASE WHEN ci.status = 'no_answer' THEN 1 END) as no_answer_caller_ids,
                COUNT(CASE WHEN ci.status = 'busy' THEN 1 END) as busy_caller_ids,
                COUNT(CASE WHEN ci.status = 'voicemail' THEN 1 END) as voicemail_caller_ids,
                COUNT(CASE WHEN ci.status = 'other' THEN 1 END) as other_caller_ids
            FROM campaigns c
            JOIN vicidial_instances vi ON c.instance_id = vi.id
            LEFT JOIN caller_ids ci ON c.id = ci.campaign_id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$campaignId]);
        
        $performance = $stmt->fetch();
        if ($performance) {
            return ['success' => true, 'data' => $performance];
        } else {
            return ['success' => false, 'error' => 'Campaign performance not found'];
        }
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get caller ID details for API
 */
function getCallerIDDetails($callerId) {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        $stmt = $supportDb->prepare("
            SELECT ci.*, c.campaign_name, vi.instance_name
            FROM caller_ids ci
            JOIN campaigns c ON ci.campaign_id = c.id
            JOIN vicidial_instances vi ON ci.instance_id = vi.id
            WHERE ci.id = ?
        ");
        $stmt->execute([$callerId]);
        
        $callerIdData = $stmt->fetch();
        if ($callerIdData) {
            return ['success' => true, 'data' => $callerIdData];
        } else {
            return ['success' => false, 'error' => 'Caller ID not found'];
        }
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get caller IDs for API
 */
function getCallerIDs($campaignId = null) {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        if ($campaignId) {
            $stmt = $supportDb->prepare("
                SELECT ci.*, c.campaign_name, vi.instance_name
                FROM caller_ids ci
                JOIN campaigns c ON ci.campaign_id = c.id
                JOIN vicidial_instances vi ON ci.instance_id = vi.id
                WHERE ci.campaign_id = ?
                ORDER BY ci.status, ci.answer_rate DESC
            ");
            $stmt->execute([$campaignId]);
        } else {
            $stmt = $supportDb->prepare("
                SELECT ci.*, c.campaign_name, vi.instance_name
                FROM caller_ids ci
                JOIN campaigns c ON ci.campaign_id = c.id
                JOIN vicidial_instances vi ON ci.instance_id = vi.id
                ORDER BY vi.instance_name, c.campaign_name, ci.status
            ");
            $stmt->execute();
        }
        
        $callerIds = $stmt->fetchAll();
        return ['success' => true, 'data' => $callerIds];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get lists for API
 */
function getLists($instanceId = null) {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        if ($instanceId) {
            $stmt = $supportDb->prepare("
                SELECT l.*, vi.instance_name
                FROM lists l
                JOIN vicidial_instances vi ON l.instance_id = vi.id
                WHERE l.instance_id = ?
                ORDER BY l.list_name
            ");
            $stmt->execute([$instanceId]);
        } else {
            $stmt = $supportDb->prepare("
                SELECT l.*, vi.instance_name
                FROM lists l
                JOIN vicidial_instances vi ON l.instance_id = vi.id
                ORDER BY vi.instance_name, l.list_name
            ");
            $stmt->execute();
        }
        
        $lists = $stmt->fetchAll();
        return ['success' => true, 'data' => $lists];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get servers for API
 */
function getServers() {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        $stmt = $supportDb->prepare("
            SELECT s.*, vi.instance_name
            FROM servers s
            JOIN vicidial_instances vi ON s.instance_id = vi.id
            ORDER BY vi.instance_name, s.server_name
        ");
        $stmt->execute();
        
        $servers = $stmt->fetchAll();
        return ['success' => true, 'data' => $servers];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get reports for API
 */
function getReports($type = 'campaign') {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        switch ($type) {
            case 'campaign':
                $stmt = $supportDb->prepare("
                    SELECT 
                        c.campaign_name,
                        vi.instance_name,
                        COUNT(ci.id) as total_caller_ids,
                        COUNT(CASE WHEN ci.status = 'active' THEN 1 END) as active_caller_ids,
                        COUNT(CASE WHEN ci.status = 'paused' THEN 1 END) as paused_caller_ids
                    FROM campaigns c
                    JOIN vicidial_instances vi ON c.instance_id = vi.id
                    LEFT JOIN caller_ids ci ON c.id = ci.campaign_id
                    GROUP BY c.id
                    ORDER BY vi.instance_name, c.campaign_name
                ");
                break;
                
            case 'instance':
                $stmt = $supportDb->prepare("
                    SELECT 
                        vi.instance_name,
                        COUNT(c.id) as total_campaigns,
                        COUNT(l.id) as total_lists,
                        COUNT(s.id) as total_servers
                    FROM vicidial_instances vi
                    LEFT JOIN campaigns c ON vi.id = c.instance_id
                    LEFT JOIN lists l ON vi.id = l.instance_id
                    LEFT JOIN servers s ON vi.id = s.instance_id
                    WHERE vi.status = 'active'
                    GROUP BY vi.id
                    ORDER BY vi.instance_name
                ");
                break;
                
            default:
                return ['success' => false, 'error' => 'Invalid report type'];
        }
        
        $stmt->execute();
        $reports = $stmt->fetchAll();
        return ['success' => true, 'data' => $reports];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get server metrics for API
 */
function getServerMetrics($serverId = null) {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        if ($serverId) {
            $stmt = $supportDb->prepare("
                SELECT s.*, vi.instance_name, sm.*
                FROM servers s
                JOIN vicidial_instances vi ON s.instance_id = vi.id
                LEFT JOIN server_metrics sm ON s.id = sm.server_id
                WHERE s.id = ?
                AND sm.recorded_at = (
                    SELECT MAX(recorded_at) FROM server_metrics WHERE server_id = s.id
                )
            ");
            $stmt->execute([$serverId]);
        } else {
            $stmt = $supportDb->prepare("
                SELECT s.*, vi.instance_name, sm.*
                FROM servers s
                JOIN vicidial_instances vi ON s.instance_id = vi.id
                LEFT JOIN server_metrics sm ON s.id = sm.server_id
                WHERE sm.recorded_at = (
                    SELECT MAX(recorded_at) FROM server_metrics WHERE server_id = s.id
                )
                ORDER BY vi.instance_name, s.server_name
            ");
            $stmt->execute();
        }
        
        $metrics = $stmt->fetchAll();
        return ['success' => true, 'data' => $metrics];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get alerts for API
 */
function getAlerts() {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        $stmt = $supportDb->prepare("
            SELECT a.*, vi.instance_name, u.username as resolved_by_user
            FROM alerts a
            JOIN vicidial_instances vi ON a.instance_id = vi.id
            LEFT JOIN users u ON a.resolved_by = u.id
            WHERE a.is_resolved = FALSE
            ORDER BY a.created_at DESC
            LIMIT 100
        ");
        $stmt->execute();
        
        $alerts = $stmt->fetchAll();
        return ['success' => true, 'data' => $alerts];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Rotate caller ID
 */
function rotateCallerID($callerId, $reason) {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        // Call stored procedure
        $stmt = $supportDb->prepare("CALL sp_rotate_caller_id(?, ?, ?)");
        $stmt->execute([$callerId, $reason, $_SESSION['user_id']]);
        
        return true;
    } catch (\Exception $e) {
        error_log("Caller ID rotation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Sync instance data
 */
function syncInstance($instanceId) {
    try {
        $syncService = new \VicidialSupport\Services\VicidialDataSyncService();
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $instances = $dbManager->getActiveInstances();
        
        $targetInstance = null;
        foreach ($instances as $instance) {
            if ($instance['id'] == $instanceId) {
                $targetInstance = $instance;
                break;
            }
        }
        
        if (!$targetInstance) {
            return ['success' => false, 'error' => 'Instance not found'];
        }
        
        $syncService->syncInstance($targetInstance);
        
        // Log the sync operation
        $supportDb = $dbManager->getSupportConnection();
        $stmt = $supportDb->prepare("
            INSERT INTO sync_logs (instance_id, sync_type, status, details, created_by)
            VALUES (?, 'manual', 'success', 'Manual sync completed', ?)
        ");
        $stmt->execute([$instanceId, $_SESSION['user_id']]);
        
        return ['success' => true, 'message' => 'Instance synchronized successfully'];
    } catch (\Exception $e) {
        error_log("Instance sync failed: " . $e->getMessage());
        
        // Log the error
        try {
            $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
            $supportDb = $dbManager->getSupportConnection();
            $stmt = $supportDb->prepare("
                INSERT INTO sync_logs (instance_id, sync_type, status, details, created_by)
                VALUES (?, 'manual', 'error', ?, ?)
            ");
            $stmt->execute([$instanceId, $e->getMessage(), $_SESSION['user_id']]);
        } catch (\Exception $logError) {
            error_log("Failed to log sync error: " . $logError->getMessage());
        }
        
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Sync all instances
 */
function syncAllInstances() {
    try {
        $syncService = new \VicidialSupport\Services\VicidialDataSyncService();
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $instances = $dbManager->getActiveInstances();
        
        $results = [];
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($instances as $instance) {
            try {
                $syncService->syncInstance($instance);
                $results[] = [
                    'instance_name' => $instance['instance_name'],
                    'status' => 'success',
                    'message' => 'Synchronized successfully'
                ];
                $successCount++;
                
                // Log successful sync
                $supportDb = $dbManager->getSupportConnection();
                $stmt = $supportDb->prepare("
                    INSERT INTO sync_logs (instance_id, sync_type, status, details, created_by)
                    VALUES (?, 'manual_all', 'success', 'Manual sync completed', ?)
                ");
                $stmt->execute([$instance['id'], $_SESSION['user_id']]);
                
            } catch (\Exception $e) {
                $results[] = [
                    'instance_name' => $instance['instance_name'],
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                $errorCount++;
                
                // Log error
                try {
                    $supportDb = $dbManager->getSupportConnection();
                    $stmt = $supportDb->prepare("
                        INSERT INTO sync_logs (instance_id, sync_type, status, details, created_by)
                        VALUES (?, 'manual_all', 'error', ?, ?)
                    ");
                    $stmt->execute([$instance['id'], $e->getMessage(), $_SESSION['user_id']]);
                } catch (\Exception $logError) {
                    error_log("Failed to log sync error: " . $logError->getMessage());
                }
            }
        }
        
        return [
            'success' => true,
            'message' => "Sync completed: {$successCount} successful, {$errorCount} failed",
            'results' => $results,
            'summary' => [
                'total' => count($instances),
                'successful' => $successCount,
                'failed' => $errorCount
            ]
        ];
        
    } catch (\Exception $e) {
        error_log("All instances sync failed: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get sync status
 */
function getSyncStatus() {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        // Get last sync times for each instance
        $stmt = $supportDb->prepare("
            SELECT 
                vi.instance_name,
                vi.id as instance_id,
                sl.sync_type,
                sl.status,
                sl.created_at as last_sync,
                sl.details
            FROM vicidial_instances vi
            LEFT JOIN (
                SELECT instance_id, sync_type, status, created_at, details
                FROM sync_logs sl1
                WHERE created_at = (
                    SELECT MAX(created_at) 
                    FROM sync_logs sl2 
                    WHERE sl2.instance_id = sl1.instance_id
                )
            ) sl ON vi.id = sl.instance_id
            WHERE vi.status = 'active'
            ORDER BY vi.instance_name
        ");
        $stmt->execute();
        $syncStatus = $stmt->fetchAll();
        
        return ['success' => true, 'data' => $syncStatus];
        
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
} 

/**
 * Add new Vicidial instance
 */
function addInstance($data) {
    try {
        // Validate required fields
        $requiredFields = ['instance_name', 'vicidial_db_host', 'vicidial_db_name', 'vicidial_db_user', 'vicidial_db_password'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Missing required field: {$field}"];
            }
        }
        
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        $supportDb = $dbManager->getSupportConnection();
        
        // Check if instance name already exists
        $stmt = $supportDb->prepare("SELECT id FROM vicidial_instances WHERE instance_name = ?");
        $stmt->execute([$data['instance_name']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Instance name already exists'];
        }
        
        // Insert new instance
        $stmt = $supportDb->prepare("
            INSERT INTO vicidial_instances (
                instance_name, instance_description, vicidial_db_host, vicidial_db_name,
                vicidial_db_user, vicidial_db_password, vicidial_db_port, web_server_url,
                dialer_server_ip, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['instance_name'],
            $data['instance_description'] ?? '',
            $data['vicidial_db_host'],
            $data['vicidial_db_name'],
            $data['vicidial_db_user'],
            $data['vicidial_db_password'],
            $data['vicidial_db_port'] ?? 3306,
            $data['web_server_url'] ?? '',
            $data['dialer_server_ip'] ?? '',
            $data['status'] ?? 'active'
        ]);
        
        $instanceId = $supportDb->lastInsertId();
        
        // Log the action
        $stmt = $supportDb->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, new_values)
            VALUES (?, 'create', 'vicidial_instance', ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $instanceId, json_encode($data)]);
        
        return ['success' => true, 'message' => 'Instance added successfully', 'instance_id' => $instanceId];
        
    } catch (\Exception $e) {
        error_log("Failed to add instance: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
} 

/**
 * Test connection to Vicidial instance
 */
function testConnection($instanceId) {
    try {
        $dbManager = \VicidialSupport\Database\DatabaseManager::getInstance();
        
        // Get instance details
        $supportDb = $dbManager->getSupportConnection();
        $stmt = $supportDb->prepare("SELECT * FROM vicidial_instances WHERE id = ?");
        $stmt->execute([$instanceId]);
        $instance = $stmt->fetch();
        
        if (!$instance) {
            return ['success' => false, 'error' => 'Instance not found'];
        }
        
        // Test Vicidial database connection
        $vicidialDb = $dbManager->getVicidialConnection($instanceId);
        
        // Test a simple query
        $stmt = $vicidialDb->prepare("SELECT COUNT(*) as count FROM vicidial_campaigns WHERE active = 'Y'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        return [
            'success' => true, 
            'message' => "Successfully connected to {$instance['instance_name']}",
            'campaigns_count' => $result['count']
        ];
        
    } catch (\Exception $e) {
        error_log("Connection test failed for instance {$instanceId}: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
} 