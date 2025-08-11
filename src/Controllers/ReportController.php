<?php

namespace VicidialSupport\Controllers;

use VicidialSupport\Models\Report;

class ReportController
{
    private $reportModel;

    public function __construct($db)
    {
        $this->reportModel = new Report($db);
    }

    public function generatePerformanceReport($days = 30, $instanceId = null, $campaignId = null)
    {
        try {
            $data = $this->reportModel->generatePerformanceReport($days, $instanceId, $campaignId);
            return ['success' => true, 'data' => $data];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateRotationReport($days = 30, $instanceId = null)
    {
        try {
            $data = $this->reportModel->generateRotationReport($days, $instanceId);
            return ['success' => true, 'data' => $data];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateServerHealthReport($days = 7)
    {
        try {
            $data = $this->reportModel->generateServerHealthReport($days);
            return ['success' => true, 'data' => $data];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateCampaignAnalytics($days = 30, $instanceId = null)
    {
        try {
            $data = $this->reportModel->generateCampaignAnalytics($days, $instanceId);
            return ['success' => true, 'data' => $data];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateCallerIdPerformanceReport($days = 30, $campaignId = null)
    {
        try {
            $data = $this->reportModel->generateCallerIdPerformanceReport($days, $campaignId);
            return ['success' => true, 'data' => $data];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateListPerformanceReport($days = 30, $campaignId = null)
    {
        try {
            $data = $this->reportModel->generateListPerformanceReport($days, $campaignId);
            return ['success' => true, 'data' => $data];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getReportStats($days = 30)
    {
        try {
            $stats = $this->reportModel->getReportStats($days);
            return ['success' => true, 'data' => $stats];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function exportToCSV($data, $filename)
    {
        try {
            $file = $this->reportModel->exportToCSV($data, $filename);
            return ['success' => true, 'file' => $file];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function exportToJSON($data, $filename)
    {
        try {
            $file = $this->reportModel->exportToJSON($data, $filename);
            return ['success' => true, 'file' => $file];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateCustomReport($type, $params)
    {
        try {
            switch ($type) {
                case 'performance':
                    $data = $this->reportModel->generatePerformanceReport(
                        $params['days'] ?? 30,
                        $params['instance_id'] ?? null,
                        $params['campaign_id'] ?? null
                    );
                    break;
                case 'rotation':
                    $data = $this->reportModel->generateRotationReport(
                        $params['days'] ?? 30,
                        $params['instance_id'] ?? null
                    );
                    break;
                case 'server':
                    $data = $this->reportModel->generateServerHealthReport($params['days'] ?? 7);
                    break;
                case 'campaign':
                    $data = $this->reportModel->generateCampaignAnalytics(
                        $params['days'] ?? 30,
                        $params['instance_id'] ?? null
                    );
                    break;
                case 'caller_id':
                    $data = $this->reportModel->generateCallerIdPerformanceReport(
                        $params['days'] ?? 30,
                        $params['campaign_id'] ?? null
                    );
                    break;
                case 'list':
                    $data = $this->reportModel->generateListPerformanceReport(
                        $params['days'] ?? 30,
                        $params['campaign_id'] ?? null
                    );
                    break;
                default:
                    return ['success' => false, 'error' => 'Invalid report type'];
            }

            return ['success' => true, 'data' => $data];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateReport($reportData)
    {
        try {
            $type = $reportData['type'] ?? '';
            $format = $reportData['format'] ?? 'json';
            $startDate = $reportData['start_date'] ?? '';
            $endDate = $reportData['end_date'] ?? '';
            $instanceId = $reportData['instance_id'] ?? null;
            $campaignId = $reportData['campaign_id'] ?? null;

            // Calculate days from date range
            $days = 30;
            if ($startDate && $endDate) {
                $start = new \DateTime($startDate);
                $end = new \DateTime($endDate);
                $days = $end->diff($start)->days;
            }

            $params = [
                'days' => $days,
                'instance_id' => $instanceId,
                'campaign_id' => $campaignId
            ];

            $result = $this->generateCustomReport($type, $params);
            
            if (!$result['success']) {
                return $result;
            }

            // Export if format is specified
            if ($format !== 'json') {
                $filename = 'reports/' . $type . '_' . date('Y-m-d_H-i-s');
                
                if ($format === 'csv') {
                    $filename .= '.csv';
                    $exportResult = $this->exportToCSV($result['data'], $filename);
                } elseif ($format === 'json') {
                    $filename .= '.json';
                    $exportResult = $this->exportToJSON($result['data'], $filename);
                }

                if ($exportResult['success']) {
                    return ['success' => true, 'file' => $exportResult['file'], 'data' => $result['data']];
                } else {
                    return $exportResult;
                }
            }

            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 