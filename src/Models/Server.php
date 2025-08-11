<?php

namespace VicidialSupport\Models;

use PDO;

class Server
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("
            SELECT s.*, vi.instance_name
            FROM servers s
            JOIN vicidial_instances vi ON s.instance_id = vi.id
            ORDER BY s.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByInstance($instanceId)
    {
        $stmt = $this->db->prepare("
            SELECT s.*, vi.instance_name
            FROM servers s
            JOIN vicidial_instances vi ON s.instance_id = vi.id
            WHERE s.instance_id = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$instanceId]);
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT s.*, vi.instance_name
            FROM servers s
            JOIN vicidial_instances vi ON s.instance_id = vi.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO servers (
                instance_id, server_name, server_type, ip_address, status,
                cpu_threshold, memory_threshold, disk_threshold
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['instance_id'],
            $data['server_name'],
            $data['server_type'],
            $data['ip_address'],
            $data['status'] ?? 'active',
            $data['cpu_threshold'] ?? 80,
            $data['memory_threshold'] ?? 85,
            $data['disk_threshold'] ?? 90
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE servers SET
                server_name = ?, server_type = ?, ip_address = ?, status = ?,
                cpu_threshold = ?, memory_threshold = ?, disk_threshold = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['server_name'],
            $data['server_type'],
            $data['ip_address'],
            $data['status'] ?? 'active',
            $data['cpu_threshold'] ?? 80,
            $data['memory_threshold'] ?? 85,
            $data['disk_threshold'] ?? 90,
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM servers WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getLatestMetrics($serverId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM server_metrics
            WHERE server_id = ?
            ORDER BY recorded_at DESC
            LIMIT 1
        ");
        $stmt->execute([$serverId]);
        return $stmt->fetch();
    }

    public function getMetricsHistory($serverId, $days = 7)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM server_metrics
            WHERE server_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY recorded_at ASC
        ");
        $stmt->execute([$serverId, $days]);
        return $stmt->fetchAll();
    }

    public function addMetrics($serverId, $metrics)
    {
        $stmt = $this->db->prepare("
            INSERT INTO server_metrics (
                server_id, cpu_usage, memory_usage, disk_usage,
                network_in, network_out, database_connections,
                load_average_1min, load_average_5min, load_average_15min
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $serverId,
            $metrics['cpu_usage'],
            $metrics['memory_usage'],
            $metrics['disk_usage'],
            $metrics['network_in'] ?? 0,
            $metrics['network_out'] ?? 0,
            $metrics['database_connections'] ?? 0,
            $metrics['load_average_1min'] ?? 0,
            $metrics['load_average_5min'] ?? 0,
            $metrics['load_average_15min'] ?? 0
        ]);
    }

    public function getServerCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM servers");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getServerCountByStatus($status)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM servers WHERE status = ?");
        $stmt->execute([$status]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getServersWithIssues()
    {
        $stmt = $this->db->prepare("
            SELECT s.*, sm.cpu_usage, sm.memory_usage, sm.disk_usage
            FROM servers s
            JOIN server_metrics sm ON s.id = sm.server_id
            WHERE sm.recorded_at = (
                SELECT MAX(recorded_at) FROM server_metrics WHERE server_id = s.id
            )
            AND (
                sm.cpu_usage > s.cpu_threshold OR
                sm.memory_usage > s.memory_threshold OR
                sm.disk_usage > s.disk_threshold
            )
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getServerStats()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_servers,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_servers,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_servers,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_servers
            FROM servers
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
} 