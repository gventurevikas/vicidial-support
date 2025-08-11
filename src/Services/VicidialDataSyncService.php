<?php

namespace VicidialSupport\Services;

use VicidialSupport\Database\DatabaseManager;

class VicidialDataSyncService
{
    private $dbManager;
    
    public function __construct()
    {
        $this->dbManager = DatabaseManager::getInstance();
    }
    
    /**
     * Sync all Vicidial instances data
     */
    public function syncAllInstances()
    {
        $instances = $this->dbManager->getActiveInstances();
        
        foreach ($instances as $instance) {
            try {
                $this->syncInstance($instance);
            } catch (\Exception $e) {
                // Log error and continue with next instance
                error_log("Failed to sync instance {$instance['instance_name']}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Sync data from specific Vicidial instance
     */
    public function syncInstance($instance)
    {
        // Increase execution time limit for large syncs
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M');
        
        // Handle both array and integer parameters
        $instanceId = is_array($instance) ? $instance['id'] : $instance;
        
        $vicidialDb = $this->dbManager->getVicidialConnection($instanceId);
        $supportDb = $this->dbManager->getSupportConnection();
        
        $syncStats = [
            'campaigns' => ['synced' => 0, 'updated' => 0, 'created' => 0, 'failed' => 0],
            'caller_ids' => ['synced' => 0, 'updated' => 0, 'created' => 0, 'failed' => 0],
            'lists' => ['synced' => 0, 'updated' => 0, 'created' => 0, 'failed' => 0],
            'performance' => ['synced' => 0, 'updated' => 0, 'created' => 0, 'failed' => 0]
        ];
        
        try {
            // Sync campaigns
            $syncStats['campaigns'] = $this->syncCampaigns($instanceId, $vicidialDb, $supportDb);
            
            // Sync caller IDs
            $syncStats['caller_ids'] = $this->syncCallerIDs($instanceId, $vicidialDb, $supportDb);
            
            // Sync lists
            $syncStats['lists'] = $this->syncLists($instanceId, $vicidialDb, $supportDb);
            
            // Sync performance data
            $syncStats['performance'] = $this->syncPerformanceData($instanceId, $vicidialDb, $supportDb);
            
            // Log successful sync
            $this->logSyncOperation($instanceId, 'automatic', 'success', $syncStats);
            
        } catch (\Exception $e) {
            // Log failed sync
            $this->logSyncOperation($instanceId, 'automatic', 'error', $syncStats, $e->getMessage());
            throw $e;
        }
        
        return $syncStats;
    }
    
    /**
     * Sync campaigns from Vicidial
     */
    private function syncCampaigns($instanceId, $vicidialDb, $supportDb)
    {
        $stats = ['synced' => 0, 'updated' => 0, 'created' => 0, 'failed' => 0];
        
        // Get campaigns from Vicidial
        $stmt = $vicidialDb->prepare("
            SELECT 
                campaign_id,
                campaign_name,
                campaign_description,
                active
            FROM vicidial_campaigns 
            WHERE active = 'Y'
        ");
        $stmt->execute();
        $vicidialCampaigns = $stmt->fetchAll();
        
        foreach ($vicidialCampaigns as $vicidialCampaign) {
            try {
                // Check if campaign exists in support system
                $stmt = $supportDb->prepare("
                    SELECT id FROM campaigns 
                    WHERE instance_id = ? AND vicidial_campaign_id = ?
                ");
                $stmt->execute([$instanceId, $vicidialCampaign['campaign_id']]);
                $existingCampaign = $stmt->fetch();
                
                if ($existingCampaign) {
                    // Update existing campaign
                    $stmt = $supportDb->prepare("
                        UPDATE campaigns 
                        SET campaign_name = ?, status = ?
                        WHERE id = ?
                    ");
                    $status = $vicidialCampaign['active'] === 'Y' ? 'active' : 'paused';
                    $stmt->execute([
                        $vicidialCampaign['campaign_name'],
                        $status,
                        $existingCampaign['id']
                    ]);
                    $stats['updated']++;
                } else {
                    // Insert new campaign
                    $stmt = $supportDb->prepare("
                        INSERT INTO campaigns (instance_id, vicidial_campaign_id, campaign_name, campaign_type, status)
                        VALUES (?, ?, ?, 'general', ?)
                    ");
                    $status = $vicidialCampaign['active'] === 'Y' ? 'active' : 'paused';
                    $stmt->execute([
                        $instanceId,
                        $vicidialCampaign['campaign_id'],
                        $vicidialCampaign['campaign_name'],
                        $status
                    ]);
                    $stats['created']++;
                }
                $stats['synced']++;
                
                // Log campaign performance data
                $this->logCampaignPerformance($instanceId, $vicidialCampaign['campaign_id'], $vicidialDb, $supportDb);
                
            } catch (\Exception $e) {
                $stats['failed']++;
                error_log("Failed to sync campaign {$vicidialCampaign['campaign_id']}: " . $e->getMessage());
            }
        }
        
        return $stats;
    }
    
    /**
     * Log campaign performance data
     */
    private function logCampaignPerformance($instanceId, $vicidialCampaignId, $vicidialDb, $supportDb)
    {
        try {
            // Get campaign ID from support database
            $stmt = $supportDb->prepare("
                SELECT id FROM campaigns 
                WHERE instance_id = ? AND vicidial_campaign_id = ?
            ");
            $stmt->execute([$instanceId, $vicidialCampaignId]);
            $campaign = $stmt->fetch();
            
            if (!$campaign) {
                return;
            }
            
            // Get performance data from Vicidial
            $stmt = $vicidialDb->prepare("
                SELECT 
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN status IN ('AA', 'A', 'ADC') THEN 1 ELSE 0 END) as successful_calls,
                    SUM(CASE WHEN status IN ('NA', 'N', 'BUSY', 'DROP', 'PDROP') THEN 1 ELSE 0 END) as hangup_calls,
                    SUM(CASE WHEN status IN ('XFER', 'TRANSFER') THEN 1 ELSE 0 END) as transfer_calls,
                    AVG(CASE WHEN status IN ('AA', 'A', 'ADC') THEN length_in_sec ELSE 0 END) as avg_call_duration
                FROM vicidial_log 
                WHERE campaign_id = ? 
                AND call_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$vicidialCampaignId]);
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
            error_log("Failed to log campaign performance for {$vicidialCampaignId}: " . $e->getMessage());
        }
    }
    
    /**
     * Sync caller IDs from Vicidial
     */
    private function syncCallerIDs($instanceId, $vicidialDb, $supportDb)
    {
        $stats = ['synced' => 0, 'updated' => 0, 'created' => 0, 'failed' => 0];
        
        // Get campaigns for this instance
        $stmt = $supportDb->prepare("SELECT id, vicidial_campaign_id FROM campaigns WHERE instance_id = ?");
        $stmt->execute([$instanceId]);
        $campaigns = $stmt->fetchAll();
        
        foreach ($campaigns as $campaign) {
            try {
                // Method 1: Get caller IDs from vicidial_campaign_cid_areacodes table
                $stmt = $vicidialDb->prepare("
                    SELECT DISTINCT outbound_cid
                    FROM vicidial_campaign_cid_areacodes 
                    WHERE campaign_id = ? 
                    AND outbound_cid IS NOT NULL 
                    AND outbound_cid != ''
                    AND active = 'Y'
                ");
                $stmt->execute([$campaign['vicidial_campaign_id']]);
                $areacodeCallerIds = $stmt->fetchAll();
                
                // Method 2: Get caller IDs from vicidial_dial_cid_log joined with vicidial_dial_log and vicidial_log
                $stmt = $vicidialDb->prepare("
                    SELECT DISTINCT dcl.outbound_cid
                    FROM vicidial_dial_cid_log dcl
                    INNER JOIN vicidial_dial_log dl ON dcl.caller_code = dl.caller_code
                    INNER JOIN vicidial_log vl ON dl.uniqueid = vl.uniqueid
                    WHERE vl.campaign_id = ? 
                    AND dcl.outbound_cid IS NOT NULL 
                    AND dcl.outbound_cid != ''
                    AND dcl.call_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY dcl.outbound_cid
                    ORDER BY COUNT(*) DESC
                    LIMIT 50
                ");
                $stmt->execute([$campaign['vicidial_campaign_id']]);
                $dialLogCallerIds = $stmt->fetchAll();
                
                // Method 3: Get caller IDs from vicidial_dial_cid_log_archive (if table exists)
                $archiveCallerIds = [];
                try {
                    $stmt = $vicidialDb->prepare("
                        SELECT DISTINCT dcl.outbound_cid
                        FROM vicidial_dial_cid_log_archive dcl
                        INNER JOIN vicidial_dial_log dl ON dcl.caller_code = dl.caller_code
                        INNER JOIN vicidial_log vl ON dl.uniqueid = vl.uniqueid
                        WHERE vl.campaign_id = ? 
                        AND dcl.outbound_cid IS NOT NULL 
                        AND dcl.outbound_cid != ''
                        AND dcl.call_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                        GROUP BY dcl.outbound_cid
                        ORDER BY COUNT(*) DESC
                        LIMIT 25
                    ");
                    $stmt->execute([$campaign['vicidial_campaign_id']]);
                    $archiveCallerIds = $stmt->fetchAll();
                } catch (\Exception $e) {
                    // Archive table might not exist, continue without it
                }
                
                // Combine all caller IDs, prioritizing areacode table first
                $allCallerIds = [];
                
                // Add from areacode table
                foreach ($areacodeCallerIds as $cid) {
                    $allCallerIds[$cid['outbound_cid']] = 'areacode';
                }
                
                // Add from dial log (if not already added)
                foreach ($dialLogCallerIds as $cid) {
                    if (!isset($allCallerIds[$cid['outbound_cid']])) {
                        $allCallerIds[$cid['outbound_cid']] = 'dial_log';
                    }
                }
                
                // Add from archive (if not already added)
                foreach ($archiveCallerIds as $cid) {
                    if (!isset($allCallerIds[$cid['outbound_cid']])) {
                        $allCallerIds[$cid['outbound_cid']] = 'archive';
                    }
                }
                
                echo "Found " . count($allCallerIds) . " caller IDs for campaign {$campaign['vicidial_campaign_id']}\n";
                
                // Batch process caller IDs
                $batchSize = 10;
                $batch = [];
                
                foreach ($allCallerIds as $phoneNumber => $source) {
                    try {
                        $batch[] = [
                            'instance_id' => $instanceId,
                            'campaign_id' => $campaign['id'],
                            'phone_number' => $phoneNumber,
                            'source' => $source
                        ];
                        
                        // Process batch when it reaches the size limit
                        if (count($batch) >= $batchSize) {
                            $this->processCallerIDBatch($batch, $supportDb, $stats, $instanceId);
                            $batch = [];
                        }
                        
                    } catch (\Exception $e) {
                        $stats['failed']++;
                        error_log("Failed to process caller ID {$phoneNumber}: " . $e->getMessage());
                    }
                }
                
                // Process remaining batch
                if (!empty($batch)) {
                    $this->processCallerIDBatch($batch, $supportDb, $stats, $instanceId);
                }
                
            } catch (\Exception $e) {
                error_log("Failed to sync caller IDs for campaign {$campaign['vicidial_campaign_id']}: " . $e->getMessage());
            }
        }
        
        return $stats;
    }
    
    /**
     * Process a batch of caller IDs
     */
    private function processCallerIDBatch($batch, $supportDb, &$stats, $instanceId = null)
    {
        if (empty($batch)) {
            return;
        }
        
        try {
            // Get existing caller IDs for this batch
            $phoneNumbers = array_column($batch, 'phone_number');
            $campaignIds = array_column($batch, 'campaign_id');
            $instanceIds = array_column($batch, 'instance_id');
            
            // Use a more efficient query to check for existing caller IDs
            $placeholders = str_repeat('?,', count($phoneNumbers) - 1) . '?';
            $stmt = $supportDb->prepare("
                SELECT id, campaign_id, phone_number 
                FROM caller_ids 
                WHERE instance_id = ? AND phone_number IN ($placeholders)
            ");
            
            $params = array_merge([$instanceIds[0]], $phoneNumbers);
            $stmt->execute($params);
            $existingCallerIds = $stmt->fetchAll();
            
            // Create lookup array for existing caller IDs
            $existingLookup = [];
            foreach ($existingCallerIds as $existing) {
                $key = $existing['campaign_id'] . '_' . $existing['phone_number'];
                $existingLookup[$key] = $existing['id'];
            }
            
            // Process each caller ID in the batch
            foreach ($batch as $callerId) {
                $key = $callerId['campaign_id'] . '_' . $callerId['phone_number'];
                
                if (!isset($existingLookup[$key])) {
                    // Insert new caller ID
                    $stmt = $supportDb->prepare("
                        INSERT INTO caller_ids (instance_id, campaign_id, phone_number, status)
                        VALUES (?, ?, ?, 'active')
                        ON DUPLICATE KEY UPDATE 
                        status = VALUES(status),
                        updated_at = NOW()
                    ");
                    $stmt->execute([
                        $callerId['instance_id'],
                        $callerId['campaign_id'],
                        $callerId['phone_number']
                    ]);
                    $newCallerIdId = $supportDb->lastInsertId();
                    $stats['created']++;
                    
                    // Log caller ID performance data
                    $this->logCallerIDPerformance($newCallerIdId, $callerId['phone_number'], $callerId['instance_id'], $supportDb);
                } else {
                    // Update existing caller ID
                    $stmt = $supportDb->prepare("
                        UPDATE caller_ids 
                        SET status = 'active', updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$existingLookup[$key]]);
                    $stats['updated']++;
                    
                    // Log caller ID performance data
                    $this->logCallerIDPerformance($existingLookup[$key], $callerId['phone_number'], $callerId['instance_id'], $supportDb);
                }
                $stats['synced']++;
            }
            
        } catch (\Exception $e) {
            $stats['failed'] += count($batch);
            error_log("Failed to process caller ID batch: " . $e->getMessage());
        }
    }
    
    /**
     * Log caller ID performance data
     */
    private function logCallerIDPerformance($callerIdId, $phoneNumber, $instanceId, $supportDb)
    {
        try {
            // Get Vicidial database connection
            $vicidialDb = $this->dbManager->getVicidialConnection($instanceId);
            
            // Get the campaign ID for this caller ID
            $stmt = $supportDb->prepare("SELECT campaign_id FROM caller_ids WHERE id = ?");
            $stmt->execute([$callerIdId]);
            $callerIdRecord = $stmt->fetch();
            
            if (!$callerIdRecord) {
                return;
            }
            
            // Get campaign ID from support database
            $stmt = $supportDb->prepare("SELECT vicidial_campaign_id FROM campaigns WHERE id = ?");
            $stmt->execute([$callerIdRecord['campaign_id']]);
            $campaign = $stmt->fetch();
            
            if (!$campaign) {
                return;
            }
            
            // Get comprehensive performance data
            $performance = $this->getComprehensivePerformanceData($vicidialDb, $phoneNumber, $campaign['vicidial_campaign_id']);
            
            if ($performance['total_calls'] > 0) {
                $answerRate = ($performance['answered_calls'] / $performance['total_calls']) * 100;
                $blockRate = ($performance['failed_calls'] / $performance['total_calls']) * 100;
                
                // Insert caller ID log
                $stmt = $supportDb->prepare("
                    INSERT INTO caller_id_logs (
                        caller_id_id, status, answer_rate, block_rate, total_calls, 
                        successful_calls, hangup_calls, transfer_calls, avg_call_duration,
                        last_used_date
                    ) VALUES (?, 'active', ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $callerIdId,
                    round($answerRate, 2),
                    round($blockRate, 2),
                    $performance['total_calls'],
                    $performance['answered_calls'],
                    $performance['failed_calls'],
                    $performance['transfer_calls'],
                    (int) $performance['avg_call_duration']
                ]);
            }
            
        } catch (\Exception $e) {
            error_log("Failed to log caller ID performance for {$phoneNumber}: " . $e->getMessage());
        }
    }
    
    /**
     * Sync lists from Vicidial
     */
    private function syncLists($instanceId, $vicidialDb, $supportDb)
    {
        $stats = ['synced' => 0, 'updated' => 0, 'created' => 0, 'failed' => 0];
        
        // Get campaigns for this instance
        $stmt = $supportDb->prepare("SELECT id, vicidial_campaign_id FROM campaigns WHERE instance_id = ?");
        $stmt->execute([$instanceId]);
        $campaigns = $stmt->fetchAll();
        
        foreach ($campaigns as $campaign) {
            try {
                // Get lists from Vicidial for this campaign
                $stmt = $vicidialDb->prepare("
                    SELECT 
                        list_id,
                        list_name,
                        active
                    FROM vicidial_lists 
                    WHERE campaign_id = ? AND active = 'Y'
                ");
                $stmt->execute([$campaign['vicidial_campaign_id']]);
                $vicidialLists = $stmt->fetchAll();
                
                foreach ($vicidialLists as $vicidialList) {
                    try {
                        // Check if list exists
                        $stmt = $supportDb->prepare("
                            SELECT id FROM lists 
                            WHERE instance_id = ? AND vicidial_list_id = ?
                        ");
                        $stmt->execute([$instanceId, $vicidialList['list_id']]);
                        $existingList = $stmt->fetch();
                        
                        if ($existingList) {
                            // Update existing list
                            $stmt = $supportDb->prepare("
                                UPDATE lists 
                                SET list_name = ?, status = ?
                                WHERE id = ?
                            ");
                            $status = $vicidialList['active'] === 'Y' ? 'active' : 'inactive';
                            $stmt->execute([
                                $vicidialList['list_name'],
                                $status,
                                $existingList['id']
                            ]);
                            $stats['updated']++;
                            
                            // Log list performance data
                            $this->logListPerformance($existingList['id'], $vicidialList['list_id'], $vicidialDb, $supportDb);
                        } else {
                            // Insert new list
                            $stmt = $supportDb->prepare("
                                INSERT INTO lists (instance_id, campaign_id, vicidial_list_id, list_name, status)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $status = $vicidialList['active'] === 'Y' ? 'active' : 'inactive';
                            $stmt->execute([
                                $instanceId,
                                $campaign['id'],
                                $vicidialList['list_id'],
                                $vicidialList['list_name'],
                                $status
                            ]);
                            $newListId = $supportDb->lastInsertId();
                            $stats['created']++;
                            
                            // Log list performance data
                            $this->logListPerformance($newListId, $vicidialList['list_id'], $vicidialDb, $supportDb);
                        }
                        $stats['synced']++;
                        
                    } catch (\Exception $e) {
                        $stats['failed']++;
                        error_log("Failed to sync list {$vicidialList['list_id']}: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                error_log("Failed to sync lists for campaign {$campaign['vicidial_campaign_id']}: " . $e->getMessage());
            }
        }
        
        return $stats;
    }
    
    /**
     * Log list performance data
     */
    private function logListPerformance($listId, $vicidialListId, $vicidialDb, $supportDb)
    {
        try {
            // Get performance data from Vicidial
            $stmt = $vicidialDb->prepare("
                SELECT 
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN status IN ('AA', 'A', 'ADC') THEN 1 ELSE 0 END) as successful_calls,
                    SUM(CASE WHEN status IN ('NA', 'N', 'BUSY', 'DROP', 'PDROP') THEN 1 ELSE 0 END) as hangup_calls,
                    SUM(CASE WHEN status IN ('XFER', 'TRANSFER') THEN 1 ELSE 0 END) as transfer_calls,
                    AVG(CASE WHEN status IN ('AA', 'A', 'ADC') THEN length_in_sec ELSE 0 END) as avg_call_duration
                FROM vicidial_log 
                WHERE list_id = ? 
                AND call_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$vicidialListId]);
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
                    $listId,
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
            error_log("Failed to log list performance for {$vicidialListId}: " . $e->getMessage());
        }
    }
    
    /**
     * Sync performance data from Vicidial
     */
    private function syncPerformanceData($instanceId, $vicidialDb, $supportDb)
    {
        $stats = ['synced' => 0, 'updated' => 0, 'created' => 0, 'failed' => 0];
        
        // Get campaigns for this instance
        $stmt = $supportDb->prepare("SELECT id, vicidial_campaign_id FROM campaigns WHERE instance_id = ?");
        $stmt->execute([$instanceId]);
        $campaigns = $stmt->fetchAll();
        
        foreach ($campaigns as $campaign) {
            try {
                // Get caller IDs from multiple sources for this campaign
                $callerIds = $this->getCallerIDsForCampaign($vicidialDb, $campaign['vicidial_campaign_id']);
                
                // Get performance data for each caller ID
                foreach ($callerIds as $callerId) {
                    try {
                        $phoneNumber = $callerId['outbound_cid'];
                        
                        // Get comprehensive performance data from multiple log tables
                        $performance = $this->getComprehensivePerformanceData($vicidialDb, $phoneNumber, $campaign['vicidial_campaign_id']);
                        
                        // Check if performance data exists in support system
                        $stmt = $supportDb->prepare("
                            SELECT id FROM performance_data 
                            WHERE instance_id = ? AND caller_id = ?
                        ");
                        $stmt->execute([$instanceId, $phoneNumber]);
                        $existingPerformance = $stmt->fetch();
                        
                        $performanceData = [
                            'total_calls' => (int) $performance['total_calls'],
                            'answered_calls' => (int) $performance['answered_calls'],
                            'failed_calls' => (int) $performance['failed_calls'],
                            'transfer_calls' => (int) $performance['transfer_calls'],
                            'answer_rate' => $performance['total_calls'] > 0 ? round(($performance['answered_calls'] / $performance['total_calls']) * 100, 2) : 0,
                            'conversion_rate' => $performance['total_calls'] > 0 ? round(($performance['transfer_calls'] / $performance['total_calls']) * 100, 2) : 0,
                            'avg_call_duration' => (int) $performance['avg_call_duration']
                        ];
                        
                        if ($existingPerformance) {
                            // Update existing performance data
                            $stmt = $supportDb->prepare("
                                UPDATE performance_data 
                                SET total_calls = ?, answered_calls = ?, failed_calls = ?, 
                                    transfer_calls = ?, answer_rate = ?, conversion_rate = ?, 
                                    avg_call_duration = ?, updated_at = NOW()
                                WHERE id = ?
                            ");
                            $stmt->execute([
                                $performanceData['total_calls'],
                                $performanceData['answered_calls'],
                                $performanceData['failed_calls'],
                                $performanceData['transfer_calls'],
                                $performanceData['answer_rate'],
                                $performanceData['conversion_rate'],
                                $performanceData['avg_call_duration'],
                                $existingPerformance['id']
                            ]);
                            $stats['updated']++;
                        } else {
                            // Insert new performance data
                            $stmt = $supportDb->prepare("
                                INSERT INTO performance_data (instance_id, caller_id, total_calls, 
                                    answered_calls, failed_calls, transfer_calls, answer_rate, 
                                    conversion_rate, avg_call_duration)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $instanceId,
                                $phoneNumber,
                                $performanceData['total_calls'],
                                $performanceData['answered_calls'],
                                $performanceData['failed_calls'],
                                $performanceData['transfer_calls'],
                                $performanceData['answer_rate'],
                                $performanceData['conversion_rate'],
                                $performanceData['avg_call_duration']
                            ]);
                            $stats['created']++;
                        }
                        $stats['synced']++;
                        
                    } catch (\Exception $e) {
                        $stats['failed']++;
                        error_log("Failed to sync performance data for caller ID {$phoneNumber}: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                error_log("Failed to sync performance data for campaign {$campaign['vicidial_campaign_id']}: " . $e->getMessage());
            }
        }
        
        return $stats;
    }
    
    /**
     * Get caller IDs for a campaign from multiple sources
     */
    private function getCallerIDsForCampaign($vicidialDb, $campaignId)
    {
        $callerIds = [];
        
        // Method 1: Get from vicidial_campaign_cid_areacodes
        try {
            $stmt = $vicidialDb->prepare("
                SELECT DISTINCT outbound_cid
                FROM vicidial_campaign_cid_areacodes 
                WHERE campaign_id = ? 
                AND outbound_cid IS NOT NULL 
                AND outbound_cid != ''
                AND active = 'Y'
                LIMIT 20
            ");
            $stmt->execute([$campaignId]);
            $areacodeCallerIds = $stmt->fetchAll();
            
            foreach ($areacodeCallerIds as $cid) {
                $callerIds[$cid['outbound_cid']] = $cid;
            }
        } catch (\Exception $e) {
            // Table might not exist or have data
        }
        
        // Method 2: Get from vicidial_dial_cid_log with joins (last 7 days, limited)
        try {
            $stmt = $vicidialDb->prepare("
                SELECT DISTINCT dcl.outbound_cid
                FROM vicidial_dial_cid_log dcl
                INNER JOIN vicidial_dial_log dl ON dcl.caller_code = dl.caller_code
                INNER JOIN vicidial_log vl ON dl.uniqueid = vl.uniqueid
                WHERE vl.campaign_id = ? 
                AND dcl.outbound_cid IS NOT NULL 
                AND dcl.outbound_cid != ''
                AND dcl.call_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY dcl.outbound_cid
                ORDER BY COUNT(*) DESC
                LIMIT 30
            ");
            $stmt->execute([$campaignId]);
            $dialLogCallerIds = $stmt->fetchAll();
            
            foreach ($dialLogCallerIds as $cid) {
                if (!isset($callerIds[$cid['outbound_cid']])) {
                    $callerIds[$cid['outbound_cid']] = $cid;
                }
            }
        } catch (\Exception $e) {
            // Handle error
        }
        
        // Method 3: Get from vicidial_dial_cid_log_archive (last 30 days, limited)
        try {
            $stmt = $vicidialDb->prepare("
                SELECT DISTINCT dcl.outbound_cid
                FROM vicidial_dial_cid_log_archive dcl
                INNER JOIN vicidial_dial_log dl ON dcl.caller_code = dl.caller_code
                INNER JOIN vicidial_log vl ON dl.uniqueid = vl.uniqueid
                WHERE vl.campaign_id = ? 
                AND dcl.outbound_cid IS NOT NULL 
                AND dcl.outbound_cid != ''
                AND dcl.call_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY dcl.outbound_cid
                ORDER BY COUNT(*) DESC
                LIMIT 15
            ");
            $stmt->execute([$campaignId]);
            $archiveCallerIds = $stmt->fetchAll();
            
            foreach ($archiveCallerIds as $cid) {
                if (!isset($callerIds[$cid['outbound_cid']])) {
                    $callerIds[$cid['outbound_cid']] = $cid;
                }
            }
        } catch (\Exception $e) {
            // Archive table might not exist
        }
        
        return array_values($callerIds);
    }
    
    /**
     * Get comprehensive performance data from multiple log tables
     */
    private function getComprehensivePerformanceData($vicidialDb, $phoneNumber, $campaignId)
    {
        $performance = [
            'total_calls' => 0,
            'answered_calls' => 0,
            'failed_calls' => 0,
            'transfer_calls' => 0,
            'avg_call_duration' => 0
        ];
        
        // Get data from vicidial_dial_cid_log with joins (last 7 days for efficiency)
        try {
            $stmt = $vicidialDb->prepare("
                SELECT 
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN vl.status IN ('AA', 'A', 'ADC') THEN 1 ELSE 0 END) as answered_calls,
                    SUM(CASE WHEN vl.status IN ('NA', 'N', 'BUSY', 'DROP', 'PDROP') THEN 1 ELSE 0 END) as failed_calls,
                    SUM(CASE WHEN vl.status IN ('XFER', 'TRANSFER') THEN 1 ELSE 0 END) as transfer_calls,
                    AVG(CASE WHEN vl.status IN ('AA', 'A', 'ADC') THEN vl.length_in_sec ELSE 0 END) as avg_call_duration
                FROM vicidial_dial_cid_log dcl
                INNER JOIN vicidial_dial_log dl ON dcl.caller_code = dl.caller_code
                INNER JOIN vicidial_log vl ON dl.uniqueid = vl.uniqueid
                WHERE dcl.outbound_cid = ? 
                AND vl.campaign_id = ?
                AND dcl.call_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$phoneNumber, $campaignId]);
            $recentData = $stmt->fetch();
            
            $performance['total_calls'] += (int) $recentData['total_calls'];
            $performance['answered_calls'] += (int) $recentData['answered_calls'];
            $performance['failed_calls'] += (int) $recentData['failed_calls'];
            $performance['transfer_calls'] += (int) $recentData['transfer_calls'];
            
            if ($recentData['avg_call_duration'] > 0) {
                $performance['avg_call_duration'] = (int) $recentData['avg_call_duration'];
            }
        } catch (\Exception $e) {
            // Handle error
        }
        
        // If no recent data, try archive (last 30 days)
        if ($performance['total_calls'] == 0) {
            try {
                $stmt = $vicidialDb->prepare("
                    SELECT 
                        COUNT(*) as total_calls,
                        SUM(CASE WHEN vl.status IN ('AA', 'A', 'ADC') THEN 1 ELSE 0 END) as answered_calls,
                        SUM(CASE WHEN vl.status IN ('NA', 'N', 'BUSY', 'DROP', 'PDROP') THEN 1 ELSE 0 END) as failed_calls,
                        SUM(CASE WHEN vl.status IN ('XFER', 'TRANSFER') THEN 1 ELSE 0 END) as transfer_calls,
                        AVG(CASE WHEN vl.status IN ('AA', 'A', 'ADC') THEN vl.length_in_sec ELSE 0 END) as avg_call_duration
                    FROM vicidial_dial_cid_log_archive dcl
                    INNER JOIN vicidial_dial_log dl ON dcl.caller_code = dl.caller_code
                    INNER JOIN vicidial_log vl ON dl.uniqueid = vl.uniqueid
                    WHERE dcl.outbound_cid = ? 
                    AND vl.campaign_id = ?
                    AND dcl.call_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ");
                $stmt->execute([$phoneNumber, $campaignId]);
                $archiveData = $stmt->fetch();
                
                $performance['total_calls'] += (int) $archiveData['total_calls'];
                $performance['answered_calls'] += (int) $archiveData['answered_calls'];
                $performance['failed_calls'] += (int) $archiveData['failed_calls'];
                $performance['transfer_calls'] += (int) $archiveData['transfer_calls'];
                
                if ($performance['avg_call_duration'] == 0 && $archiveData['avg_call_duration'] > 0) {
                    $performance['avg_call_duration'] = (int) $archiveData['avg_call_duration'];
                }
            } catch (\Exception $e) {
                // Archive table might not exist
            }
        }
        
        return $performance;
    }
    
    /**
     * Get real-time campaign statistics from Vicidial
     */
    public function getCampaignStats($instanceId, $campaignId)
    {
        $vicidialDb = $this->dbManager->getVicidialConnection($instanceId);
        $supportDb = $this->dbManager->getSupportConnection();
        
        // Get Vicidial campaign ID
        $stmt = $supportDb->prepare("SELECT vicidial_campaign_id FROM campaigns WHERE id = ?");
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch();
        
        if (!$campaign) {
            throw new \Exception("Campaign not found");
        }
        
        // Get real-time stats from Vicidial
        $stmt = $vicidialDb->prepare("
            SELECT 
                COUNT(*) as total_calls,
                SUM(CASE WHEN status = 'ANSWER' THEN 1 ELSE 0 END) as answered_calls,
                SUM(CASE WHEN status = 'BUSY' OR status = 'NOANSWER' THEN 1 ELSE 0 END) as failed_calls,
                SUM(CASE WHEN status = 'XFER' THEN 1 ELSE 0 END) as transfer_calls,
                AVG(CASE WHEN status = 'ANSWER' THEN length_in_sec ELSE 0 END) as avg_call_duration
            FROM vicidial_log 
            WHERE campaign_id = ? 
            AND call_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$campaign['vicidial_campaign_id']]);
        $stats = $stmt->fetch();
        
        return [
            'total_calls' => $stats['total_calls'],
            'answered_calls' => $stats['answered_calls'],
            'failed_calls' => $stats['failed_calls'],
            'transfer_calls' => $stats['transfer_calls'],
            'answer_rate' => $stats['total_calls'] > 0 ? ($stats['answered_calls'] / $stats['total_calls']) * 100 : 0,
            'conversion_rate' => $stats['total_calls'] > 0 ? ($stats['transfer_calls'] / $stats['total_calls']) * 100 : 0,
            'avg_call_duration' => (int) $stats['avg_call_duration']
        ];
    }
    
    /**
     * Get caller ID performance from Vicidial
     */
    public function getCallerIDPerformance($instanceId, $callerId)
    {
        $supportDb = $this->dbManager->getSupportConnection();
        
        // Get the latest performance data from caller_id_logs
        $stmt = $supportDb->prepare("
            SELECT 
                answer_rate,
                block_rate,
                total_calls,
                successful_calls,
                hangup_calls,
                transfer_calls,
                avg_call_duration,
                recorded_at
            FROM caller_id_logs 
            WHERE caller_id_id = ?
            ORDER BY recorded_at DESC
            LIMIT 1
        ");
        $stmt->execute([$callerId]);
        $performance = $stmt->fetch();
        
        if (!$performance) {
            // Fallback to real-time calculation from Vicidial
            $vicidialDb = $this->dbManager->getVicidialConnection($instanceId);
            
            // Get the phone number from the caller ID record
            $stmt = $supportDb->prepare("SELECT phone_number FROM caller_ids WHERE id = ?");
            $stmt->execute([$callerId]);
            $callerIdRecord = $stmt->fetch();
            
            if (!$callerIdRecord) {
                throw new \Exception("Caller ID not found");
            }
            
            $phoneNumber = $callerIdRecord['phone_number'];
            
            // Get performance data using joined tables with correct status values
            $stmt = $vicidialDb->prepare("
                SELECT 
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN vl.status IN ('AA', 'A', 'ADC') THEN 1 ELSE 0 END) as answered_calls,
                    SUM(CASE WHEN vl.status IN ('NA', 'N', 'BUSY', 'DROP', 'PDROP') THEN 1 ELSE 0 END) as failed_calls,
                    SUM(CASE WHEN vl.status IN ('XFER', 'TRANSFER') THEN 1 ELSE 0 END) as transfer_calls,
                    AVG(CASE WHEN vl.status IN ('AA', 'A', 'ADC') THEN vl.length_in_sec ELSE 0 END) as avg_call_duration
                FROM vicidial_dial_cid_log dcl
                INNER JOIN vicidial_dial_log dl ON dcl.caller_code = dl.caller_code
                INNER JOIN vicidial_log vl ON dl.uniqueid = vl.uniqueid
                WHERE dcl.outbound_cid = ? 
                AND dcl.call_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$phoneNumber]);
            $stats = $stmt->fetch();
            
            // If no data found in 30 days, try 90 days
            if ($stats['total_calls'] == 0) {
                $stmt = $vicidialDb->prepare("
                    SELECT 
                        COUNT(*) as total_calls,
                        SUM(CASE WHEN vl.status IN ('AA', 'A', 'ADC') THEN 1 ELSE 0 END) as answered_calls,
                        SUM(CASE WHEN vl.status IN ('NA', 'N', 'BUSY', 'DROP', 'PDROP') THEN 1 ELSE 0 END) as failed_calls,
                        SUM(CASE WHEN vl.status IN ('XFER', 'TRANSFER') THEN 1 ELSE 0 END) as transfer_calls,
                        AVG(CASE WHEN vl.status IN ('AA', 'A', 'ADC') THEN vl.length_in_sec ELSE 0 END) as avg_call_duration
                    FROM vicidial_dial_cid_log dcl
                    INNER JOIN vicidial_dial_log dl ON dcl.caller_code = dl.caller_code
                    INNER JOIN vicidial_log vl ON dl.uniqueid = vl.uniqueid
                    WHERE dcl.outbound_cid = ? 
                    AND dcl.call_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                ");
                $stmt->execute([$phoneNumber]);
                $stats = $stmt->fetch();
            }
            
            return [
                'total_calls' => (int) $stats['total_calls'],
                'answered_calls' => (int) $stats['answered_calls'],
                'failed_calls' => (int) $stats['failed_calls'],
                'transfer_calls' => (int) $stats['transfer_calls'],
                'answer_rate' => $stats['total_calls'] > 0 ? round(($stats['answered_calls'] / $stats['total_calls']) * 100, 2) : 0,
                'conversion_rate' => $stats['total_calls'] > 0 ? round(($stats['transfer_calls'] / $stats['total_calls']) * 100, 2) : 0,
                'avg_call_duration' => (int) $stats['avg_call_duration']
            ];
        }
        
        return [
            'total_calls' => (int) $performance['total_calls'],
            'answered_calls' => (int) $performance['successful_calls'],
            'failed_calls' => (int) $performance['hangup_calls'],
            'transfer_calls' => (int) $performance['transfer_calls'],
            'answer_rate' => (float) $performance['answer_rate'],
            'conversion_rate' => $performance['total_calls'] > 0 ? round(($performance['transfer_calls'] / $performance['total_calls']) * 100, 2) : 0,
            'avg_call_duration' => (int) $performance['avg_call_duration'],
            'last_updated' => $performance['recorded_at']
        ];
    }
    
    /**
     * Log sync operation
     */
    private function logSyncOperation($instanceId, $syncType, $status, $stats, $errorMessage = null)
    {
        try {
            $supportDb = $this->dbManager->getSupportConnection();
            
            $totalSynced = $stats['campaigns']['synced'] + $stats['caller_ids']['synced'] + 
                          $stats['lists']['synced'] + $stats['performance']['synced'];
            $totalUpdated = $stats['campaigns']['updated'] + $stats['caller_ids']['updated'] + 
                           $stats['lists']['updated'] + $stats['performance']['updated'];
            $totalCreated = $stats['campaigns']['created'] + $stats['caller_ids']['created'] + 
                           $stats['lists']['created'] + $stats['performance']['created'];
            $totalFailed = $stats['campaigns']['failed'] + $stats['caller_ids']['failed'] + 
                          $stats['lists']['failed'] + $stats['performance']['failed'];
            
            $details = json_encode([
                'campaigns' => $stats['campaigns'],
                'caller_ids' => $stats['caller_ids'],
                'lists' => $stats['lists'],
                'performance' => $stats['performance']
            ]);
            
            if ($errorMessage) {
                $details = $errorMessage . "\n" . $details;
            }
            
            $stmt = $supportDb->prepare("
                INSERT INTO sync_logs (
                    instance_id, sync_type, status, details, 
                    records_synced, records_updated, records_created, records_failed, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $userId = $_SESSION['user_id'] ?? null;
            $stmt->execute([
                $instanceId, $syncType, $status, $details,
                $totalSynced, $totalUpdated, $totalCreated, $totalFailed, $userId
            ]);
            
        } catch (\Exception $e) {
            error_log("Failed to log sync operation: " . $e->getMessage());
        }
    }
    
    /**
     * Get sync statistics for an instance
     */
    public function getSyncStats($instanceId, $days = 7)
    {
        try {
            $supportDb = $this->dbManager->getSupportConnection();
            
            $stmt = $supportDb->prepare("
                SELECT 
                    sync_type,
                    status,
                    COUNT(*) as count,
                    SUM(records_synced) as total_synced,
                    SUM(records_updated) as total_updated,
                    SUM(records_created) as total_created,
                    SUM(records_failed) as total_failed,
                    MAX(created_at) as last_sync
                FROM sync_logs 
                WHERE instance_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY sync_type, status
                ORDER BY last_sync DESC
            ");
            $stmt->execute([$instanceId, $days]);
            
            return $stmt->fetchAll();
            
        } catch (\Exception $e) {
            error_log("Failed to get sync stats: " . $e->getMessage());
            return [];
        }
    }
} 