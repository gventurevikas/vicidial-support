<?php

namespace VicidialSupport\Services;

use VicidialSupport\Database\DatabaseManager;

class MonitoringService
{
    private $dbManager;
    
    public function __construct()
    {
        $this->dbManager = DatabaseManager::getInstance();
    }
    
    /**
     * Collect server metrics for all servers
     */
    public function collectServerMetrics()
    {
        $supportDb = $this->dbManager->getSupportConnection();
        $stmt = $supportDb->prepare("SELECT * FROM servers WHERE status = 'active'");
        $stmt->execute();
        $servers = $stmt->fetchAll();
        
        foreach ($servers as $server) {
            $this->collectServerMetricsForServer($server);
        }
    }
    
    /**
     * Collect metrics for a specific server
     */
    private function collectServerMetricsForServer($server)
    {
        try {
            // Get server metrics using SSH or SNMP
            $metrics = $this->getServerMetrics($server);
            
            // Store metrics in database
            $supportDb = $this->dbManager->getSupportConnection();
            $stmt = $supportDb->prepare("
                INSERT INTO server_metrics (server_id, cpu_usage, memory_usage, disk_usage, 
                                         network_in, network_out, database_connections,
                                         load_average_1min, load_average_5min, load_average_15min)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $server['id'],
                $metrics['cpu_usage'],
                $metrics['memory_usage'],
                $metrics['disk_usage'],
                $metrics['network_in'],
                $metrics['network_out'],
                $metrics['database_connections'],
                $metrics['load_average_1min'],
                $metrics['load_average_5min'],
                $metrics['load_average_15min']
            ]);
            
            // Check thresholds and create alerts
            $this->checkServerThresholds($server, $metrics);
            
        } catch (\Exception $e) {
            // Log error and create alert
            $this->createAlert(
                $server['instance_id'],
                'critical',
                'server',
                'Server Monitoring Error',
                "Failed to collect metrics for server {$server['server_name']}: " . $e->getMessage(),
                $server['id'],
                'server'
            );
        }
    }
    
    /**
     * Get server metrics using SSH commands
     */
    private function getServerMetrics($server)
    {
        if (!$server['ssh_username'] || !$server['ssh_password']) {
            throw new \Exception("SSH credentials not configured for server");
        }
        
        $connection = ssh2_connect($server['ip_address'], $server['ssh_port']);
        if (!$connection) {
            throw new \Exception("Failed to connect to server via SSH");
        }
        
        if (!ssh2_auth_password($connection, $server['ssh_username'], $server['ssh_password'])) {
            throw new \Exception("SSH authentication failed");
        }
        
        $metrics = [];
        
        // Get CPU usage
        $stream = ssh2_exec($connection, "top -bn1 | grep 'Cpu(s)' | awk '{print $2}' | cut -d'%' -f1");
        stream_set_blocking($stream, true);
        $metrics['cpu_usage'] = (float) trim(stream_get_contents($stream));
        
        // Get memory usage
        $stream = ssh2_exec($connection, "free | grep Mem | awk '{printf \"%.2f\", $3/$2 * 100.0}'");
        stream_set_blocking($stream, true);
        $metrics['memory_usage'] = (float) trim(stream_get_contents($stream));
        
        // Get disk usage
        $stream = ssh2_exec($connection, "df / | tail -1 | awk '{print $5}' | cut -d'%' -f1");
        stream_set_blocking($stream, true);
        $metrics['disk_usage'] = (float) trim(stream_get_contents($stream));
        
        // Get load average
        $stream = ssh2_exec($connection, "uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | tr -d ','");
        stream_set_blocking($stream, true);
        $metrics['load_average_1min'] = (float) trim(stream_get_contents($stream));
        
        $stream = ssh2_exec($connection, "uptime | awk -F'load average:' '{print $2}' | awk '{print $2}' | tr -d ','");
        stream_set_blocking($stream, true);
        $metrics['load_average_5min'] = (float) trim(stream_get_contents($stream));
        
        $stream = ssh2_exec($connection, "uptime | awk -F'load average:' '{print $2}' | awk '{print $3}' | tr -d ','");
        stream_set_blocking($stream, true);
        $metrics['load_average_15min'] = (float) trim(stream_get_contents($stream));
        
        // Get network stats
        $stream = ssh2_exec($connection, "cat /proc/net/dev | grep eth0 | awk '{print $2}'");
        stream_set_blocking($stream, true);
        $metrics['network_in'] = (float) trim(stream_get_contents($stream));
        
        $stream = ssh2_exec($connection, "cat /proc/net/dev | grep eth0 | awk '{print $10}'");
        stream_set_blocking($stream, true);
        $metrics['network_out'] = (float) trim(stream_get_contents($stream));
        
        // Get database connections (if this is a database server)
        if ($server['server_type'] === 'database') {
            $stream = ssh2_exec($connection, "mysql -e 'SHOW STATUS LIKE \"Threads_connected\"' | tail -1 | awk '{print $2}'");
            stream_set_blocking($stream, true);
            $metrics['database_connections'] = (int) trim(stream_get_contents($stream));
        } else {
            $metrics['database_connections'] = 0;
        }
        
        return $metrics;
    }
    
    /**
     * Check server thresholds and create alerts
     */
    private function checkServerThresholds($server, $metrics)
    {
        if ($metrics['cpu_usage'] > $server['cpu_threshold']) {
            $this->createAlert(
                $server['instance_id'],
                'warning',
                'server',
                'High CPU Usage',
                "Server {$server['server_name']} CPU usage is {$metrics['cpu_usage']}% (threshold: {$server['cpu_threshold']}%)",
                $server['id'],
                'server'
            );
        }
        
        if ($metrics['memory_usage'] > $server['memory_threshold']) {
            $this->createAlert(
                $server['instance_id'],
                'warning',
                'server',
                'High Memory Usage',
                "Server {$server['server_name']} memory usage is {$metrics['memory_usage']}% (threshold: {$server['memory_threshold']}%)",
                $server['id'],
                'server'
            );
        }
        
        if ($metrics['disk_usage'] > $server['disk_threshold']) {
            $this->createAlert(
                $server['instance_id'],
                'critical',
                'server',
                'High Disk Usage',
                "Server {$server['server_name']} disk usage is {$metrics['disk_usage']}% (threshold: {$server['disk_threshold']}%)",
                $server['id'],
                'server'
            );
        }
    }
    
    /**
     * Monitor caller ID health for all campaigns
     */
    public function monitorCallerIDHealth()
    {
        $supportDb = $this->dbManager->getSupportConnection();
        $stmt = $supportDb->prepare("
            SELECT DISTINCT c.instance_id, c.id as campaign_id, c.campaign_name
            FROM campaigns c
            WHERE c.status = 'active'
        ");
        $stmt->execute();
        $campaigns = $stmt->fetchAll();
        
        foreach ($campaigns as $campaign) {
            $this->monitorCallerIDHealthForCampaign($campaign);
        }
    }
    
    /**
     * Monitor caller ID health for specific campaign
     */
    private function monitorCallerIDHealthForCampaign($campaign)
    {
        try {
            $vicidialDb = $this->dbManager->getVicidialConnection($campaign['instance_id']);
            
            // Get caller ID performance from Vicidial
            $stmt = $vicidialDb->prepare("
                SELECT 
                    caller_id,
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN status = 'ANSWER' THEN 1 ELSE 0 END) as answered_calls,
                    SUM(CASE WHEN status = 'BUSY' OR status = 'NOANSWER' THEN 1 ELSE 0 END) as failed_calls
                FROM vicidial_log 
                WHERE campaign_id = ? 
                AND call_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY caller_id
            ");
            $stmt->execute([$campaign['vicidial_campaign_id']]);
            $callerIdStats = $stmt->fetchAll();
            
            // Update caller ID performance in support database
            $supportDb = $this->dbManager->getSupportConnection();
            foreach ($callerIdStats as $stat) {
                $answerRate = $stat['total_calls'] > 0 ? ($stat['answered_calls'] / $stat['total_calls']) * 100 : 0;
                $blockRate = $stat['total_calls'] > 0 ? ($stat['failed_calls'] / $stat['total_calls']) * 100 : 0;
                
                $stmt = $supportDb->prepare("
                    UPDATE caller_ids 
                    SET answer_rate = ?, block_rate = ?, total_calls = ?, 
                        successful_calls = ?, last_used_date = NOW()
                    WHERE campaign_id = ? AND phone_number = ?
                ");
                $stmt->execute([
                    $answerRate,
                    $blockRate,
                    $stat['total_calls'],
                    $stat['answered_calls'],
                    $campaign['campaign_id'],
                    $stat['caller_id']
                ]);
                
                // Check if rotation is needed
                $this->checkCallerIDRotation($campaign['campaign_id'], $stat['caller_id'], $answerRate, $blockRate);
            }
            
        } catch (\Exception $e) {
            $this->createAlert(
                $campaign['instance_id'],
                'critical',
                'campaign',
                'Caller ID Monitoring Error',
                "Failed to monitor caller ID health for campaign {$campaign['campaign_name']}: " . $e->getMessage(),
                $campaign['campaign_id'],
                'campaign'
            );
        }
    }
    
    /**
     * Check if caller ID rotation is needed
     */
    private function checkCallerIDRotation($campaignId, $callerId, $answerRate, $blockRate)
    {
        $supportDb = $this->dbManager->getSupportConnection();
        
        // Get campaign settings
        $stmt = $supportDb->prepare("SELECT * FROM campaigns WHERE id = ?");
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch();
        
        $rotationNeeded = false;
        $reason = '';
        
        // Check answer rate threshold
        if ($answerRate < $campaign['target_answer_rate']) {
            $rotationNeeded = true;
            $reason = "Low answer rate: {$answerRate}% (target: {$campaign['target_answer_rate']}%)";
        }
        
        // Check block rate threshold
        if ($blockRate > 5) {
            $rotationNeeded = true;
            $reason = "High block rate: {$blockRate}%";
        }
        
        if ($rotationNeeded) {
            // Get caller ID record
            $stmt = $supportDb->prepare("SELECT * FROM caller_ids WHERE campaign_id = ? AND phone_number = ?");
            $stmt->execute([$campaignId, $callerId]);
            $callerIdRecord = $stmt->fetch();
            
            if ($callerIdRecord) {
                $this->rotateCallerID($callerIdRecord['id'], $reason);
            }
        }
    }
    
    /**
     * Rotate caller ID
     */
    private function rotateCallerID($callerId, $reason)
    {
        $supportDb = $this->dbManager->getSupportConnection();
        
        // Get caller ID details
        $stmt = $supportDb->prepare("SELECT * FROM caller_ids WHERE id = ?");
        $stmt->execute([$callerId]);
        $callerIdRecord = $stmt->fetch();
        
        if (!$callerIdRecord) {
            return;
        }
        
        // Mark current caller ID as rotated
        $stmt = $supportDb->prepare("UPDATE caller_ids SET status = 'rotated', last_rotation_date = NOW() WHERE id = ?");
        $stmt->execute([$callerId]);
        
        // Log rotation
        $stmt = $supportDb->prepare("
            INSERT INTO rotation_history (instance_id, entity_type, entity_id, rotation_reason, performance_before, rotated_by)
            VALUES (?, 'caller_id', ?, ?, ?, NULL)
        ");
        $stmt->execute([
            $callerIdRecord['instance_id'],
            $callerId,
            $reason,
            $callerIdRecord['answer_rate']
        ]);
        
        // Activate next available caller ID
        $stmt = $supportDb->prepare("
            UPDATE caller_ids 
            SET status = 'active', last_rotation_date = NOW()
            WHERE campaign_id = ? AND status = 'inactive'
            LIMIT 1
        ");
        $stmt->execute([$callerIdRecord['campaign_id']]);
        
        // Create alert
        $this->createAlert(
            $callerIdRecord['instance_id'],
            'info',
            'caller_id',
            'Caller ID Rotated',
            "Caller ID {$callerIdRecord['phone_number']} was rotated: {$reason}",
            $callerId,
            'caller_id'
        );
    }
    
    /**
     * Create alert
     */
    private function createAlert($instanceId, $type, $category, $title, $message, $entityId = null, $entityType = null)
    {
        $supportDb = $this->dbManager->getSupportConnection();
        $stmt = $supportDb->prepare("
            INSERT INTO alerts (instance_id, alert_type, alert_category, title, message, entity_id, entity_type)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$instanceId, $type, $category, $title, $message, $entityId, $entityType]);
    }
} 