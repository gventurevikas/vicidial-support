<?php

namespace VicidialSupport\Models;

use PDO;

class VicidialInstance
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("
            SELECT * FROM vicidial_instances 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActive()
    {
        $stmt = $this->db->prepare("
            SELECT * FROM vicidial_instances 
            WHERE status = 'active'
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM vicidial_instances 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO vicidial_instances (
                instance_name, instance_description, vicidial_db_host, 
                vicidial_db_name, vicidial_db_user, vicidial_db_password, 
                vicidial_db_port, web_server_url, dialer_server_ip, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['instance_name'],
            $data['instance_description'] ?? null,
            $data['vicidial_db_host'],
            $data['vicidial_db_name'],
            $data['vicidial_db_user'],
            $data['vicidial_db_password'],
            $data['vicidial_db_port'] ?? 3306,
            $data['web_server_url'] ?? null,
            $data['dialer_server_ip'] ?? null,
            $data['status'] ?? 'active'
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE vicidial_instances SET
                instance_name = ?, instance_description = ?, vicidial_db_host = ?,
                vicidial_db_name = ?, vicidial_db_user = ?, vicidial_db_password = ?,
                vicidial_db_port = ?, web_server_url = ?, dialer_server_ip = ?, status = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['instance_name'],
            $data['instance_description'] ?? null,
            $data['vicidial_db_host'],
            $data['vicidial_db_name'],
            $data['vicidial_db_user'],
            $data['vicidial_db_password'],
            $data['vicidial_db_port'] ?? 3306,
            $data['web_server_url'] ?? null,
            $data['dialer_server_ip'] ?? null,
            $data['status'] ?? 'active',
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM vicidial_instances WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function testConnection($id)
    {
        $instance = $this->getById($id);
        if (!$instance) {
            return ['success' => false, 'error' => 'Instance not found'];
        }

        try {
            $dsn = "mysql:host={$instance['vicidial_db_host']};port={$instance['vicidial_db_port']};dbname={$instance['vicidial_db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $instance['vicidial_db_user'], $instance['vicidial_db_password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Test a simple query
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM vicidial_campaigns");
            $result = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Connection successful',
                'campaigns_count' => $result['count']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    public function getInstanceStats($id)
    {
        $instance = $this->getById($id);
        if (!$instance) {
            return null;
        }

        try {
            $dsn = "mysql:host={$instance['vicidial_db_host']};port={$instance['vicidial_db_port']};dbname={$instance['vicidial_db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $instance['vicidial_db_user'], $instance['vicidial_db_password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Get campaign count
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM vicidial_campaigns WHERE active = 'Y'");
            $campaigns = $stmt->fetch();

            // Get active calls
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM vicidial_log WHERE status = 'QUEUE'");
            $activeCalls = $stmt->fetch();

            // Get today's calls
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM vicidial_log WHERE DATE(call_date) = CURDATE()");
            $todayCalls = $stmt->fetch();

            return [
                'campaigns' => $campaigns['count'],
                'active_calls' => $activeCalls['count'],
                'today_calls' => $todayCalls['count']
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getInstanceCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM vicidial_instances");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getInstanceCountByStatus($status)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM vicidial_instances WHERE status = ?");
        $stmt->execute([$status]);
        $result = $stmt->fetch();
        return $result['count'];
    }
} 