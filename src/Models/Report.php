<?php

namespace VicidialSupport\Models;

use PDO;

class Report
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function generatePerformanceReport($days = 30, $instanceId = null, $campaignId = null)
    {
        $whereClause = "WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params = [$days];

        if ($instanceId) {
            $whereClause .= " AND c.instance_id = ?";
            $params[] = $instanceId;
        }

        if ($campaignId) {
            $whereClause .= " AND pl.campaign_id = ?";
            $params[] = $campaignId;
        }

        $stmt = $this->db->prepare("
            SELECT 
                DATE(pl.recorded_at) as date,
                AVG(pl.answer_rate) as avg_answer_rate,
                AVG(pl.conversion_rate) as avg_conversion_rate,
                SUM(pl.total_calls) as total_calls,
                SUM(pl.successful_calls) as successful_calls,
                SUM(pl.failed_calls) as failed_calls
            FROM performance_logs pl
            JOIN campaigns c ON pl.campaign_id = c.id
            $whereClause
            GROUP BY DATE(pl.recorded_at)
            ORDER BY date DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function generateRotationReport($days = 30, $instanceId = null)
    {
        $whereClause = "WHERE rh.rotated_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params = [$days];

        if ($instanceId) {
            $whereClause .= " AND c.instance_id = ?";
            $params[] = $instanceId;
        }

        $stmt = $this->db->prepare("
            SELECT 
                rh.entity_type,
                rh.rotation_reason,
                rh.rotated_at,
                CASE 
                    WHEN rh.entity_type = 'caller_id' THEN cid.phone_number
                    WHEN rh.entity_type = 'list' THEN l.list_name
                    ELSE 'Unknown'
                END as entity_name,
                c.campaign_name,
                vi.instance_name
            FROM rotation_history rh
            LEFT JOIN caller_ids cid ON rh.entity_type = 'caller_id' AND rh.entity_id = cid.id
            LEFT JOIN lists l ON rh.entity_type = 'list' AND rh.entity_id = l.id
            LEFT JOIN campaigns c ON (cid.campaign_id = c.id OR l.campaign_id = c.id)
            LEFT JOIN vicidial_instances vi ON c.instance_id = vi.id
            $whereClause
            ORDER BY rh.rotated_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function generateServerHealthReport($days = 7)
    {
        $stmt = $this->db->prepare("
            SELECT 
                s.server_name,
                s.server_type,
                vi.instance_name,
                sm.cpu_usage,
                sm.memory_usage,
                sm.disk_usage,
                sm.database_connections,
                sm.recorded_at,
                CASE 
                    WHEN sm.cpu_usage > s.cpu_threshold OR 
                         sm.memory_usage > s.memory_threshold OR 
                         sm.disk_usage > s.disk_threshold THEN 'critical'
                    WHEN sm.cpu_usage > s.cpu_threshold * 0.8 OR 
                         sm.memory_usage > s.memory_threshold * 0.8 OR 
                         sm.disk_usage > s.disk_threshold * 0.8 THEN 'warning'
                    ELSE 'healthy'
                END as health_status
            FROM servers s
            JOIN vicidial_instances vi ON s.instance_id = vi.id
            JOIN server_metrics sm ON s.id = sm.server_id
            WHERE sm.recorded_at = (
                SELECT MAX(recorded_at) FROM server_metrics WHERE server_id = s.id
            )
            AND sm.recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY health_status DESC, sm.recorded_at DESC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function generateCampaignAnalytics($days = 30, $instanceId = null)
    {
        $whereClause = "WHERE pl.recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params = [$days];

        if ($instanceId) {
            $whereClause .= " AND c.instance_id = ?";
            $params[] = $instanceId;
        }

        $stmt = $this->db->prepare("
            SELECT 
                c.campaign_name,
                c.campaign_type,
                vi.instance_name,
                AVG(pl.answer_rate) as avg_answer_rate,
                AVG(pl.conversion_rate) as avg_conversion_rate,
                SUM(pl.total_calls) as total_calls,
                SUM(pl.successful_calls) as successful_calls,
                SUM(pl.failed_calls) as failed_calls,
                COUNT(DISTINCT DATE(pl.recorded_at)) as active_days
            FROM performance_logs pl
            JOIN campaigns c ON pl.campaign_id = c.id
            JOIN vicidial_instances vi ON c.instance_id = vi.id
            $whereClause
            GROUP BY c.id
            ORDER BY total_calls DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function generateCallerIdPerformanceReport($days = 30, $campaignId = null)
    {
        $whereClause = "WHERE pl.recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params = [$days];

        if ($campaignId) {
            $whereClause .= " AND pl.campaign_id = ?";
            $params[] = $campaignId;
        }

        $stmt = $this->db->prepare("
            SELECT 
                cid.phone_number,
                cid.caller_id_name,
                c.campaign_name,
                vi.instance_name,
                AVG(pl.answer_rate) as avg_answer_rate,
                AVG(pl.block_rate) as avg_block_rate,
                SUM(pl.total_calls) as total_calls,
                SUM(pl.successful_calls) as successful_calls,
                cid.status,
                cid.last_rotation_date
            FROM performance_logs pl
            JOIN caller_ids cid ON pl.caller_id = cid.id
            JOIN campaigns c ON cid.campaign_id = c.id
            JOIN vicidial_instances vi ON c.instance_id = vi.id
            $whereClause
            GROUP BY cid.id
            ORDER BY avg_answer_rate ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function generateListPerformanceReport($days = 30, $campaignId = null)
    {
        $whereClause = "WHERE pl.recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params = [$days];

        if ($campaignId) {
            $whereClause .= " AND pl.campaign_id = ?";
            $params[] = $campaignId;
        }

        $stmt = $this->db->prepare("
            SELECT 
                l.list_name,
                c.campaign_name,
                vi.instance_name,
                l.total_records,
                l.valid_records,
                l.processed_records,
                AVG(pl.answer_rate) as avg_answer_rate,
                AVG(pl.conversion_rate) as avg_conversion_rate,
                SUM(pl.total_calls) as total_calls,
                SUM(pl.successful_calls) as successful_calls,
                l.status,
                l.last_rotation_date
            FROM performance_logs pl
            JOIN lists l ON pl.list_id = l.id
            JOIN campaigns c ON l.campaign_id = c.id
            JOIN vicidial_instances vi ON c.instance_id = vi.id
            $whereClause
            GROUP BY l.id
            ORDER BY avg_answer_rate ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getReportStats($days = 30)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT pl.campaign_id) as active_campaigns,
                COUNT(DISTINCT pl.caller_id) as active_caller_ids,
                COUNT(DISTINCT pl.list_id) as active_lists,
                SUM(pl.total_calls) as total_calls,
                SUM(pl.successful_calls) as successful_calls,
                AVG(pl.answer_rate) as avg_answer_rate,
                AVG(pl.conversion_rate) as avg_conversion_rate,
                COUNT(DISTINCT rh.entity_id) as total_rotations
            FROM performance_logs pl
            LEFT JOIN rotation_history rh ON rh.rotated_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            WHERE pl.recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days, $days]);
        return $stmt->fetch();
    }

    public function exportToCSV($data, $filename)
    {
        $file = fopen($filename, 'w');
        
        if (!empty($data)) {
            // Write headers
            fputcsv($file, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
        }
        
        fclose($file);
        return $filename;
    }

    public function exportToJSON($data, $filename)
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($filename, $json);
        return $filename;
    }
} 