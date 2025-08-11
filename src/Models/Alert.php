<?php

namespace VicidialSupport\Models;

use PDO;

class Alert
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("
            SELECT a.*, vi.instance_name
            FROM alerts a
            LEFT JOIN vicidial_instances vi ON a.entity_id = vi.id AND a.entity_type = 'instance'
            ORDER BY a.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUnresolved()
    {
        $stmt = $this->db->prepare("
            SELECT a.*, vi.instance_name
            FROM alerts a
            LEFT JOIN vicidial_instances vi ON a.entity_id = vi.id AND a.entity_type = 'instance'
            WHERE a.is_resolved = 0
            ORDER BY a.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByType($type)
    {
        $stmt = $this->db->prepare("
            SELECT a.*, vi.instance_name
            FROM alerts a
            LEFT JOIN vicidial_instances vi ON a.entity_id = vi.id AND a.entity_type = 'instance'
            WHERE a.alert_type = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public function getByCategory($category)
    {
        $stmt = $this->db->prepare("
            SELECT a.*, vi.instance_name
            FROM alerts a
            LEFT JOIN vicidial_instances vi ON a.entity_id = vi.id AND a.entity_type = 'instance'
            WHERE a.alert_category = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT a.*, vi.instance_name
            FROM alerts a
            LEFT JOIN vicidial_instances vi ON a.entity_id = vi.id AND a.entity_type = 'instance'
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO alerts (
                alert_type, alert_category, title, message,
                entity_id, entity_type, is_resolved
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['alert_type'],
            $data['alert_category'],
            $data['title'],
            $data['message'],
            $data['entity_id'] ?? null,
            $data['entity_type'] ?? null,
            $data['is_resolved'] ?? 0
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE alerts SET
                alert_type = ?, alert_category = ?, title = ?, message = ?,
                entity_id = ?, entity_type = ?, is_resolved = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['alert_type'],
            $data['alert_category'],
            $data['title'],
            $data['message'],
            $data['entity_id'] ?? null,
            $data['entity_type'] ?? null,
            $data['is_resolved'] ?? 0,
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM alerts WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function resolve($id)
    {
        $stmt = $this->db->prepare("
            UPDATE alerts SET 
                is_resolved = 1, 
                resolved_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    public function resolveAll()
    {
        $stmt = $this->db->prepare("
            UPDATE alerts SET 
                is_resolved = 1, 
                resolved_at = NOW()
            WHERE is_resolved = 0
        ");
        return $stmt->execute();
    }

    public function getAlertCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM alerts");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getAlertCountByType($type)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM alerts WHERE alert_type = ?");
        $stmt->execute([$type]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getAlertCountByStatus($resolved)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM alerts WHERE is_resolved = ?");
        $stmt->execute([$resolved]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getAlertStats()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_alerts,
                SUM(CASE WHEN alert_type = 'critical' THEN 1 ELSE 0 END) as critical_alerts,
                SUM(CASE WHEN alert_type = 'warning' THEN 1 ELSE 0 END) as warning_alerts,
                SUM(CASE WHEN alert_type = 'info' THEN 1 ELSE 0 END) as info_alerts,
                SUM(CASE WHEN is_resolved = 0 THEN 1 ELSE 0 END) as unresolved_alerts,
                SUM(CASE WHEN is_resolved = 1 THEN 1 ELSE 0 END) as resolved_alerts
            FROM alerts
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    public function createServerAlert($serverId, $type, $title, $message)
    {
        return $this->create([
            'alert_type' => $type,
            'alert_category' => 'server',
            'title' => $title,
            'message' => $message,
            'entity_id' => $serverId,
            'entity_type' => 'server'
        ]);
    }

    public function createCallerIdAlert($callerId, $type, $title, $message)
    {
        return $this->create([
            'alert_type' => $type,
            'alert_category' => 'caller_id',
            'title' => $title,
            'message' => $message,
            'entity_id' => $callerId,
            'entity_type' => 'caller_id'
        ]);
    }

    public function createListAlert($listId, $type, $title, $message)
    {
        return $this->create([
            'alert_type' => $type,
            'alert_category' => 'list',
            'title' => $title,
            'message' => $message,
            'entity_id' => $listId,
            'entity_type' => 'list'
        ]);
    }

    public function createCampaignAlert($campaignId, $type, $title, $message)
    {
        return $this->create([
            'alert_type' => $type,
            'alert_category' => 'campaign',
            'title' => $title,
            'message' => $message,
            'entity_id' => $campaignId,
            'entity_type' => 'campaign'
        ]);
    }

    public function createSystemAlert($type, $title, $message)
    {
        return $this->create([
            'alert_type' => $type,
            'alert_category' => 'system',
            'title' => $title,
            'message' => $message
        ]);
    }
} 