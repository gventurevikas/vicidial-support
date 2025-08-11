<?php

namespace VicidialSupport\Services;

use VicidialSupport\Database\DatabaseManager;

/**
 * Performance Logging Service
 * Automatically logs performance data for campaigns, caller IDs, and lists
 */
class PerformanceLoggingService
{
    private $dbManager;
    
    public function __construct()
    {
        $this->dbManager = DatabaseManager::getInstance();
    }
    
    /**
     * Log performance data for all active instances
     */
    public function logAllPerformanceData()
    {
        $instances = $this->dbManager->getActiveInstances();
        
        foreach ($instances as $instance) {
            try {
                $this->logInstancePerformanceData($instance['id']);
            } catch (\Exception $e) {
                error_log("Failed to log performance data for instance {$instance['id']}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Log performance data for a specific instance
     */
    public function logInstancePerformanceData($instanceId)
    {
        $vicidialDb = $this->dbManager->getVicidialConnection($instanceId);
        $supportDb = $this->dbManager->getSupportConnection();
        
        // Log campaign performance
        $this->logCampaignPerformance($instanceId, $vicidialDb, $supportDb);
        
        // Log caller ID performance
        $this->logCallerIDPerformance($instanceId, $vicidialDb, $supportDb);
        
        // Log list performance
        $this->logListPerformance($instanceId, $vicidialDb, $supportDb);
    }
    
    /**
     * Log campaign performance data
     */
    private function logCampaignPerformance($instanceId, $vicidialDb, $supportDb)
    {
        // Get campaigns for this instance
        $stmt = $supportDb->prepare("SELECT id, vicidial_campaign_id FROM campaigns WHERE instance_id = ?");
        $stmt->execute([$instanceId]);
        $campaigns = $stmt->fetchAll();
        
        foreach ($campaigns as $campaign) {
            try {
                // Get performance data from Vicidial for last 5 minutes
                $stmt = $vicidialDb->prepare("
                    SELECT 
                        COUNT(*) as total_calls,
                        SUM(CASE WHEN status IN ('AA', 'A', 'ADC') THEN 1 ELSE 0 END) as successful_calls,
                        SUM(CASE WHEN status IN ('NA', 'N', 'BUSY', 'DROP', 'PDROP') THEN 1 ELSE 0 END) as hangup_calls,
                        SUM(CASE WHEN status IN ('XFER', 'TRANSFER') THEN 1 ELSE 0 END) as transfer_calls,
                        AVG(CASE WHEN status IN ('AA', 'A', 'ADC') THEN length_in_sec ELSE 0 END) as avg_call_duration
                    FROM vicidial_log 
                    WHERE campaign_id = ? 
                    AND call_date >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ");
                $stmt->execute([$campaign['vicidial_campaign_id']]);
                $performance = $stmt->fetch();
                
                if ($performance['total_calls'] > 0) {
                    $answerRate = ($performance['successful_calls'] / $performance['total_calls']) * 100;
                    $conversionRate = ($performance['transfer_calls'] / $performance['total_calls']) * 100;
                    
                    // Insert campaign log
                    $stmt = $supportDb->prepare("
                        INSERT INTO campaign_logs (
                            campaign_id, total_calls, successful_calls, hangup_calls, transfer_calls,
                            answer_rate, conversion_rate, avg_call_duration
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $campaign['id'],
                        $performance['total_calls'],
                        $performance['successful_calls'],
                        $performance['hangup_calls'],
                        $performance['transfer_calls'],
                        round($answerRate, 2),
                        round($conversionRate, 2),
                        (int) $performance['avg_call_duration']
                    ]);
                }
                
            } catch (\Exception $e) {
                error_log("Failed to log campaign performance for {$campaign['vicidial_campaign_id']}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Log caller ID performance data
     */
    private function logCallerIDPerformance($instanceId, $vicidialDb, $supportDb)
    {
        // Get caller IDs for this instance
        $stmt = $supportDb->prepare("
            SELECT ci.id, ci.phone_number 
            FROM caller_ids ci
            WHERE ci.instance_id = ?
        ");
        $stmt->execute([$instanceId]);
        $callerIds = $stmt->fetchAll();
        
        foreach ($callerIds as $callerId) {
            try {
                // Get performance data from Vicidial using joined tables for last 5 minutes
                $stmt = $vicidialDb->prepare("
                    SELECT 
                        COUNT(*) as total_calls,
                        SUM(CASE WHEN vl.status IN ('AA', 'A', 'ADC') THEN 1 ELSE 0 END) as successful_calls,
                        SUM(CASE WHEN vl.status IN ('NA', 'N', 'BUSY', 'DROP', 'PDROP') THEN 1 ELSE 0 END) as hangup_calls,
                        SUM(CASE WHEN vl.status IN ('XFER', 'TRANSFER') THEN 1 ELSE 0 END) as transfer_calls,
                        AVG(CASE WHEN vl.status IN ('AA', 'A', 'ADC') THEN vl.length_in_sec ELSE 0 END) as avg_call_duration
                    FROM vicidial_dial_cid_log dcl
                    INNER JOIN vicidial_dial_log dl ON dcl.caller_code = dl.caller_code
                    INNER JOIN vicidial_log vl ON dl.uniqueid = vl.uniqueid
                    WHERE dcl.outbound_cid = ? 
                    AND dcl.call_date >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ");
                $stmt->execute([$callerId['phone_number']]);
                $performance = $stmt->fetch();
                
                if ($performance['total_calls'] > 0) {
                    $answerRate = ($performance['successful_calls'] / $performance['total_calls']) * 100;
                    $blockRate = ($performance['hangup_calls'] / $performance['total_calls']) * 100;
                    
                    // Insert caller ID log
                    $stmt = $supportDb->prepare("
                        INSERT INTO caller_id_logs (
                            caller_id_id, status, answer_rate, block_rate, total_calls, 
                            successful_calls, hangup_calls, transfer_calls, avg_call_duration,
                            last_used_date
                        ) VALUES (?, 'active', ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $callerId['id'],
                        round($answerRate, 2),
                        round($blockRate, 2),
                        $performance['total_calls'],
                        $performance['successful_calls'],
                        $performance['hangup_calls'],
                        $performance['transfer_calls'],
                        (int) $performance['avg_call_duration']
                    ]);
                }
                
            } catch (\Exception $e) {
                error_log("Failed to log caller ID performance for {$callerId['phone_number']}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Log list performance data
     */
    private function logListPerformance($instanceId, $vicidialDb, $supportDb)
    {
        // Get lists for this instance
        $stmt = $supportDb->prepare("
            SELECT l.id, l.vicidial_list_id 
            FROM lists l
            WHERE l.instance_id = ?
        ");
        $stmt->execute([$instanceId]);
        $lists = $stmt->fetchAll();
        
        foreach ($lists as $list) {
            try {
                // Get performance data from Vicidial for last 5 minutes
                $stmt = $vicidialDb->prepare("
                    SELECT 
                        COUNT(*) as total_calls,
                        SUM(CASE WHEN status IN ('AA', 'A', 'ADC') THEN 1 ELSE 0 END) as successful_calls,
                        SUM(CASE WHEN status IN ('NA', 'N', 'BUSY', 'DROP', 'PDROP') THEN 1 ELSE 0 END) as hangup_calls,
                        SUM(CASE WHEN status IN ('XFER', 'TRANSFER') THEN 1 ELSE 0 END) as transfer_calls,
                        AVG(CASE WHEN status IN ('AA', 'A', 'ADC') THEN length_in_sec ELSE 0 END) as avg_call_duration
                    FROM vicidial_log 
                    WHERE list_id = ? 
                    AND call_date >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ");
                $stmt->execute([$list['vicidial_list_id']]);
                $performance = $stmt->fetch();
                
                if ($performance['total_calls'] > 0) {
                    $answerRate = ($performance['successful_calls'] / $performance['total_calls']) * 100;
                    $conversionRate = ($performance['transfer_calls'] / $performance['total_calls']) * 100;
                    $hangupRate = ($performance['hangup_calls'] / $performance['total_calls']) * 100;
                    $transferRate = ($performance['transfer_calls'] / $performance['total_calls']) * 100;
                    
                    // Insert list log
                    $stmt = $supportDb->prepare("
                        INSERT INTO list_logs (
                            list_id, total_calls, successful_calls, hangup_calls, transfer_calls,
                            answer_rate, conversion_rate, hangup_rate, transfer_rate, avg_call_duration
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $list['id'],
                        $performance['total_calls'],
                        $performance['successful_calls'],
                        $performance['hangup_calls'],
                        $performance['transfer_calls'],
                        round($answerRate, 2),
                        round($conversionRate, 2),
                        round($hangupRate, 2),
                        round($transferRate, 2),
                        (int) $performance['avg_call_duration']
                    ]);
                }
                
            } catch (\Exception $e) {
                error_log("Failed to log list performance for {$list['vicidial_list_id']}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Clean old performance logs (keep last 30 days)
     */
    public function cleanOldLogs()
    {
        $supportDb = $this->dbManager->getSupportConnection();
        
        try {
            // Clean campaign logs older than 30 days
            $supportDb->exec("
                DELETE FROM campaign_logs 
                WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            // Clean caller ID logs older than 30 days
            $supportDb->exec("
                DELETE FROM caller_id_logs 
                WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            // Clean list logs older than 30 days
            $supportDb->exec("
                DELETE FROM list_logs 
                WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
        } catch (\Exception $e) {
            error_log("Failed to clean old logs: " . $e->getMessage());
        }
    }
} 