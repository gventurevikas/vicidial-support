<?php

namespace VicidialSupport\Database;

use PDO;
use PDOException;

class DatabaseManager
{
    private static $instance = null;
    private $connections = [];
    private $config;
    
    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/database.php';
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get connection to main support system database
     */
    public function getSupportConnection()
    {
        $config = $this->config['connections']['vicidial_support'];
        
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        
        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            return $pdo;
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get connection to specific Vicidial instance
     */
    public function getVicidialConnection($instanceId)
    {
        if (isset($this->connections[$instanceId])) {
            return $this->connections[$instanceId];
        }
        
        // Get instance configuration from support database
        $supportDb = $this->getSupportConnection();
        $stmt = $supportDb->prepare("SELECT * FROM vicidial_instances WHERE id = ? AND status = 'active'");
        $stmt->execute([$instanceId]);
        $instance = $stmt->fetch();
        
        if (!$instance) {
            throw new \Exception("Vicidial instance not found or inactive");
        }
        
        // Create connection to Vicidial database
        $dsn = "mysql:host={$instance['vicidial_db_host']};port={$instance['vicidial_db_port']};dbname={$instance['vicidial_db_name']};charset=utf8mb4";
        
        try {
            $pdo = new PDO($dsn, $instance['vicidial_db_user'], $instance['vicidial_db_password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            $this->connections[$instanceId] = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            throw new \Exception("Vicidial database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get all active Vicidial instances
     */
    public function getActiveInstances()
    {
        $supportDb = $this->getSupportConnection();
        $stmt = $supportDb->prepare("SELECT * FROM vicidial_instances WHERE status = 'active'");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Test connection to Vicidial instance
     */
    public function testVicidialConnection($instanceId)
    {
        try {
            $connection = $this->getVicidialConnection($instanceId);
            $connection->query("SELECT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Close all connections
     */
    public function closeConnections()
    {
        foreach ($this->connections as $connection) {
            $connection = null;
        }
        $this->connections = [];
    }
} 