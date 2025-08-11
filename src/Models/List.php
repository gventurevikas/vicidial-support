<?php

namespace VicidialSupport\Models;

use PDO;

class VicidialList
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("
            SELECT l.*, c.campaign_name, vi.instance_name
            FROM lists l
            JOIN campaigns c ON l.campaign_id = c.id
            JOIN vicidial_instances vi ON c.instance_id = vi.id
            ORDER BY l.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByCampaign($campaignId)
    {
        $stmt = $this->db->prepare("
            SELECT l.*, c.campaign_name, vi.instance_name
            FROM lists l
            JOIN campaigns c ON l.campaign_id = c.id
            JOIN vicidial_instances vi ON c.instance_id = vi.id
            WHERE l.campaign_id = ?
            ORDER BY l.created_at DESC
        ");
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT l.*, c.campaign_name, vi.instance_name
            FROM lists l
            JOIN campaigns c ON l.campaign_id = c.id
            JOIN vicidial_instances vi ON c.instance_id = vi.id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO lists (
                campaign_id, list_name, vicidial_list_id, status,
                total_records, valid_records, processed_records,
                answer_rate, conversion_rate, hangup_rate, transfer_rate
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['campaign_id'],
            $data['list_name'],
            $data['vicidial_list_id'],
            $data['status'] ?? 'active',
            $data['total_records'] ?? 0,
            $data['valid_records'] ?? 0,
            $data['processed_records'] ?? 0,
            $data['answer_rate'] ?? 0,
            $data['conversion_rate'] ?? 0,
            $data['hangup_rate'] ?? 0,
            $data['transfer_rate'] ?? 0
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE lists SET
                list_name = ?, status = ?, total_records = ?, valid_records = ?,
                processed_records = ?, answer_rate = ?, conversion_rate = ?,
                hangup_rate = ?, transfer_rate = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['list_name'],
            $data['status'] ?? 'active',
            $data['total_records'] ?? 0,
            $data['valid_records'] ?? 0,
            $data['processed_records'] ?? 0,
            $data['answer_rate'] ?? 0,
            $data['conversion_rate'] ?? 0,
            $data['hangup_rate'] ?? 0,
            $data['transfer_rate'] ?? 0,
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM lists WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function rotate($id, $reason)
    {
        $stmt = $this->db->prepare("
            UPDATE lists SET 
                status = 'rotated', 
                last_rotation_date = NOW()
            WHERE id = ?
        ");
        
        if ($stmt->execute([$id])) {
            // Log rotation
            $stmt = $this->db->prepare("
                INSERT INTO rotation_history (
                    entity_type, entity_id, rotation_reason, rotated_at
                ) VALUES ('list', ?, ?, NOW())
            ");
            $stmt->execute([$id, $reason]);
            
            return true;
        }
        
        return false;
    }

    public function getListPerformance($id, $days = 30)
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(recorded_at) as date,
                AVG(answer_rate) as avg_answer_rate,
                AVG(conversion_rate) as avg_conversion_rate,
                SUM(total_calls) as total_calls,
                SUM(successful_calls) as successful_calls
            FROM performance_logs
            WHERE list_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$id, $days]);
        return $stmt->fetchAll();
    }

    public function getListStats($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_calls,
                AVG(answer_rate) as avg_answer_rate,
                AVG(conversion_rate) as avg_conversion_rate,
                SUM(successful_calls) as successful_calls,
                SUM(failed_calls) as failed_calls
            FROM performance_logs
            WHERE list_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getListCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM lists");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getListCountByStatus($status)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM lists WHERE status = ?");
        $stmt->execute([$status]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getListsNeedingRotation()
    {
        $stmt = $this->db->prepare("
            SELECT l.*, c.campaign_name
            FROM lists l
            JOIN campaigns c ON l.campaign_id = c.id
            WHERE l.status = 'active' 
            AND l.answer_rate < 15
            AND l.processed_records > l.total_records * 0.8
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function syncFromVicidial($instanceId)
    {
        $instanceModel = new VicidialInstance($this->db);
        $instance = $instanceModel->getById($instanceId);
        
        if (!$instance) {
            return ['success' => false, 'error' => 'Instance not found'];
        }

        try {
            $dsn = "mysql:host={$instance['vicidial_db_host']};port={$instance['vicidial_db_port']};dbname={$instance['vicidial_db_name']};charset=utf8mb4";
            $vicidialDb = new PDO($dsn, $instance['vicidial_db_user'], $instance['vicidial_db_password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Get lists from Vicidial
            $stmt = $vicidialDb->prepare("
                SELECT list_id, list_name, active
                FROM vicidial_lists WHERE active = 'Y'
            ");
            $stmt->execute();
            $vicidialLists = $stmt->fetchAll();

            $synced = 0;
            foreach ($vicidialLists as $vicidialList) {
                // Check if list already exists
                $stmt = $this->db->prepare("
                    SELECT id FROM lists 
                    WHERE vicidial_list_id = ?
                ");
                $stmt->execute([$vicidialList['list_id']]);
                $existingList = $stmt->fetch();

                if (!$existingList) {
                    // Create new list (assign to first campaign for now)
                    $stmt = $this->db->prepare("
                        SELECT id FROM campaigns WHERE instance_id = ? LIMIT 1
                    ");
                    $stmt->execute([$instanceId]);
                    $campaign = $stmt->fetch();

                    if ($campaign) {
                        $this->create([
                            'campaign_id' => $campaign['id'],
                            'list_name' => $vicidialList['list_name'],
                            'vicidial_list_id' => $vicidialList['list_id'],
                            'status' => $vicidialList['active'] === 'Y' ? 'active' : 'inactive'
                        ]);
                        $synced++;
                    }
                }
            }

            return [
                'success' => true,
                'message' => "Synced $synced lists from Vicidial instance",
                'synced_count' => $synced
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to sync lists: ' . $e->getMessage()
            ];
        }
    }
} 