<?php

namespace VicidialSupport\Models;

use PDO;

class Campaign
{
    private $db;
    private $instanceModel;

    public function __construct($db, $instanceModel = null)
    {
        $this->db = $db;
        $this->instanceModel = $instanceModel ?? new VicidialInstance($db);
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("
            SELECT c.*, vi.instance_name
            FROM campaigns c
            JOIN vicidial_instances vi ON c.instance_id = vi.id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByInstance($instanceId)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, vi.instance_name
            FROM campaigns c
            JOIN vicidial_instances vi ON c.instance_id = vi.id
            WHERE c.instance_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$instanceId]);
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, vi.instance_name
            FROM campaigns c
            JOIN vicidial_instances vi ON c.instance_id = vi.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO campaigns (
                instance_id, vicidial_campaign_id, campaign_name, campaign_type,
                status, target_answer_rate, target_conversion_rate, 
                rotation_frequency_hours, auto_rotation_enabled
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['instance_id'],
            $data['vicidial_campaign_id'],
            $data['campaign_name'],
            $data['campaign_type'] ?? 'general',
            $data['status'] ?? 'active',
            $data['target_answer_rate'] ?? 15.0,
            $data['target_conversion_rate'] ?? 2.0,
            $data['rotation_frequency_hours'] ?? 48,
            $data['auto_rotation_enabled'] ?? 1
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE campaigns SET
                campaign_name = ?, campaign_type = ?, status = ?,
                target_answer_rate = ?, target_conversion_rate = ?,
                rotation_frequency_hours = ?, auto_rotation_enabled = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['campaign_name'],
            $data['campaign_type'] ?? 'general',
            $data['status'] ?? 'active',
            $data['target_answer_rate'] ?? 15.0,
            $data['target_conversion_rate'] ?? 2.0,
            $data['rotation_frequency_hours'] ?? 48,
            $data['auto_rotation_enabled'] ?? 1,
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM campaigns WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getCampaignPerformance($id, $days = 30)
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(recorded_at) as date,
                AVG(answer_rate) as avg_answer_rate,
                AVG(conversion_rate) as avg_conversion_rate,
                SUM(total_calls) as total_calls,
                SUM(successful_calls) as successful_calls
            FROM performance_logs
            WHERE campaign_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$id, $days]);
        return $stmt->fetchAll();
    }

    public function getCampaignStats($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_calls,
                AVG(answer_rate) as avg_answer_rate,
                AVG(conversion_rate) as avg_conversion_rate,
                SUM(successful_calls) as successful_calls,
                SUM(failed_calls) as failed_calls
            FROM performance_logs
            WHERE campaign_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getCampaignCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM campaigns");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getCampaignCountByStatus($status)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM campaigns WHERE status = ?");
        $stmt->execute([$status]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function syncFromVicidial($instanceId)
    {
        $instance = $this->instanceModel->getById($instanceId);
        
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

            // Get campaigns from Vicidial
            $stmt = $vicidialDb->prepare("
                SELECT campaign_id, campaign_name, campaign_description, active
                FROM vicidial_campaigns WHERE active = 'Y'
            ");
            $stmt->execute();
            $vicidialCampaigns = $stmt->fetchAll();

            $synced = 0;
            foreach ($vicidialCampaigns as $vicidialCampaign) {
                // Check if campaign already exists
                $stmt = $this->db->prepare("
                    SELECT id FROM campaigns 
                    WHERE instance_id = ? AND vicidial_campaign_id = ?
                ");
                $stmt->execute([$instanceId, $vicidialCampaign['campaign_id']]);
                $existingCampaign = $stmt->fetch();

                if ($existingCampaign) {
                    // Update existing campaign
                    $stmt = $this->db->prepare("
                        UPDATE campaigns SET 
                            campaign_name = ?, status = ?
                        WHERE id = ?
                    ");
                    $status = $vicidialCampaign['active'] === 'Y' ? 'active' : 'paused';
                    $stmt->execute([
                        $vicidialCampaign['campaign_name'],
                        $status,
                        $existingCampaign['id']
                    ]);
                } else {
                    // Create new campaign
                    $this->create([
                        'instance_id' => $instanceId,
                        'vicidial_campaign_id' => $vicidialCampaign['campaign_id'],
                        'campaign_name' => $vicidialCampaign['campaign_name'],
                        'campaign_type' => 'general',
                        'status' => $vicidialCampaign['active'] === 'Y' ? 'active' : 'paused'
                    ]);
                }
                $synced++;
            }

            return [
                'success' => true,
                'message' => "Synced $synced campaigns from Vicidial instance",
                'synced_count' => $synced
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to sync campaigns: ' . $e->getMessage()
            ];
        }
    }
} 