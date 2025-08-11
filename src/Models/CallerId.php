<?php

namespace VicidialSupport\Models;

use PDO;

class CallerId
{
    private $db;
    private $instanceModel;

    public function __construct($db, $instanceModel = null)
    {
        $this->db = $db;
        $this->instanceModel = $instanceModel ?? new VicidialInstance($db);
    }

    public function getAll($instanceId = null)
    {
        $sql = "
            SELECT 
                vp.*,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND((vp.successful_calls * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as answer_rate,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND(((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as block_rate,
                CASE 
                    WHEN vp.total_calls > 0 AND 
                         ((vp.successful_calls * 100.0 / vp.total_calls) < 15 OR 
                          ((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls) > 30) THEN 'yes'
                    ELSE 'no' 
                END as needs_rotation_calculated
            FROM v_caller_id_performance vp
        ";
        
        $params = [];
        if ($instanceId) {
            $sql .= " WHERE vp.instance_id = ?";
            $params[] = $instanceId;
        }
        
        $sql .= " ORDER BY vp.total_calls DESC, vp.successful_calls DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByCampaign($campaignId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                vp.*,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND((vp.successful_calls * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as answer_rate,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND(((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as block_rate,
                CASE 
                    WHEN vp.total_calls > 0 AND 
                         ((vp.successful_calls * 100.0 / vp.total_calls) < 15 OR 
                          ((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls) > 30) THEN 'yes'
                    ELSE 'no' 
                END as needs_rotation_calculated
            FROM v_caller_id_performance vp
            WHERE vp.campaign_id = ?
            ORDER BY vp.total_calls DESC, vp.successful_calls DESC
        ");
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    public function getByInstance($instanceId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                vp.*,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND((vp.successful_calls * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as answer_rate,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND(((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as block_rate,
                CASE 
                    WHEN vp.total_calls > 0 AND 
                         ((vp.successful_calls * 100.0 / vp.total_calls) < 15 OR 
                          ((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls) > 30) THEN 'yes'
                    ELSE 0 
                END as needs_rotation_calculated
            FROM v_caller_id_performance vp
            WHERE vp.instance_id = ?
            ORDER BY vp.total_calls DESC, vp.successful_calls DESC
        ");
        $stmt->execute([$instanceId]);
        return $stmt->fetchAll();
    }

    public function getByCampaignAndInstance($campaignId, $instanceId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                vp.*,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND((vp.successful_calls * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as answer_rate,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND(((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as block_rate,
                CASE 
                    WHEN vp.total_calls > 0 AND 
                         ((vp.successful_calls * 100.0 / vp.total_calls) < 15 OR 
                          ((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls) > 30) THEN 'yes'
                    ELSE 'no' 
                END as needs_rotation_calculated
            FROM v_caller_id_performance vp
            WHERE vp.campaign_id = ? AND vp.instance_id = ?
            ORDER BY vp.total_calls DESC, vp.successful_calls DESC
        ");
        $stmt->execute([$campaignId, $instanceId]);
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                vp.*,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND((vp.successful_calls * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as answer_rate,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND(((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as block_rate,
                CASE 
                    WHEN vp.total_calls > 0 AND 
                         ((vp.successful_calls * 100.0 / vp.total_calls) < 15 OR 
                          ((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls) > 30) THEN 'yes'
                    ELSE 'no' 
                END as needs_rotation_calculated
            FROM v_caller_id_performance vp
            WHERE vp.caller_id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO caller_ids (
                campaign_id, phone_number, caller_id_name, status,
                answer_rate, block_rate, complaint_count, total_calls
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['campaign_id'],
            $data['phone_number'],
            $data['caller_id_name'] ?? null,
            $data['status'] ?? 'active',
            $data['answer_rate'] ?? 0,
            $data['block_rate'] ?? 0,
            $data['complaint_count'] ?? 0,
            $data['total_calls'] ?? 0
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE caller_ids SET
                phone_number = ?, caller_id_name = ?, status = ?,
                answer_rate = ?, block_rate = ?, complaint_count = ?, total_calls = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['phone_number'],
            $data['caller_id_name'] ?? null,
            $data['status'] ?? 'active',
            $data['answer_rate'] ?? 0,
            $data['block_rate'] ?? 0,
            $data['complaint_count'] ?? 0,
            $data['total_calls'] ?? 0,
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM caller_ids WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function rotate($id, $reason)
    {
        $stmt = $this->db->prepare("
            UPDATE caller_ids SET 
                status = 'rotated', 
                last_rotation_date = NOW()
            WHERE id = ?
        ");
        
        if ($stmt->execute([$id])) {
            // Log rotation
            $stmt = $this->db->prepare("
                INSERT INTO rotation_history (
                    entity_type, entity_id, rotation_reason, rotated_at
                ) VALUES ('caller_id', ?, ?, NOW())
            ");
            $stmt->execute([$id, $reason]);
            
            return true;
        }
        
        return false;
    }

    public function getCallerIdPerformance($id, $days = 30)
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(recorded_at) as date,
                AVG(answer_rate) as avg_answer_rate,
                AVG(block_rate) as avg_block_rate,
                SUM(total_calls) as total_calls,
                SUM(successful_calls) as successful_calls
            FROM performance_logs
            WHERE caller_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$id, $days]);
        return $stmt->fetchAll();
    }

    public function getCallerIdStats($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_calls,
                AVG(answer_rate) as avg_answer_rate,
                AVG(block_rate) as avg_block_rate,
                SUM(successful_calls) as successful_calls,
                SUM(failed_calls) as failed_calls
            FROM performance_logs
            WHERE caller_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getCallerIdCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM caller_ids");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getCallerIdCountByStatus($status)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM caller_ids WHERE status = ?");
        $stmt->execute([$status]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getCallerIdsNeedingRotation()
    {
        $stmt = $this->db->prepare("
            SELECT 
                vp.*,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND((vp.successful_calls * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as answer_rate,
                CASE 
                    WHEN vp.total_calls > 0 THEN 
                        ROUND(((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls), 2)
                    ELSE 0 
                END as block_rate
            FROM v_caller_id_performance vp
            WHERE vp.status = 'active' 
            AND vp.total_calls > 100
            AND (
                (vp.successful_calls * 100.0 / vp.total_calls) < 15 OR 
                ((vp.total_busy_calls + vp.total_noanswer_calls) * 100.0 / vp.total_calls) > 30
            )
            ORDER BY vp.total_calls DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
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

            // Get caller IDs from Vicidial
            $stmt = $vicidialDb->prepare("
                SELECT phone_number, caller_id_name, active
                FROM vicidial_caller_ids WHERE active = 'Y'
            ");
            $stmt->execute();
            $vicidialCallerIds = $stmt->fetchAll();

            $synced = 0;
            foreach ($vicidialCallerIds as $vicidialCallerId) {
                // Check if caller ID already exists
                $stmt = $this->db->prepare("
                    SELECT id FROM caller_ids 
                    WHERE phone_number = ?
                ");
                $stmt->execute([$vicidialCallerId['phone_number']]);
                $existingCallerId = $stmt->fetch();

                if (!$existingCallerId) {
                    // Create new caller ID (assign to first campaign for now)
                    $stmt = $this->db->prepare("
                        SELECT id FROM campaigns WHERE instance_id = ? LIMIT 1
                    ");
                    $stmt->execute([$instanceId]);
                    $campaign = $stmt->fetch();

                    if ($campaign) {
                        $this->create([
                            'campaign_id' => $campaign['id'],
                            'phone_number' => $vicidialCallerId['phone_number'],
                            'caller_id_name' => $vicidialCallerId['caller_id_name'],
                            'status' => $vicidialCallerId['active'] === 'Y' ? 'active' : 'inactive'
                        ]);
                        $synced++;
                    }
                }
            }

            return [
                'success' => true,
                'message' => "Synced $synced caller IDs from Vicidial instance",
                'synced_count' => $synced
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to sync caller IDs: ' . $e->getMessage()
            ];
        }
    }
} 