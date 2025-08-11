<?php

namespace VicidialSupport\Controllers;

use VicidialSupport\Models\CallerId;

class CallerIdController
{
    private $callerIdModel;

    public function __construct($db)
    {
        $this->callerIdModel = new CallerId($db);
    }

    public function getAll()
    {
        try {
            $instanceId = $_GET['instance_id'] ?? null;
            $campaignId = $_GET['campaign_id'] ?? null;
            
            if ($instanceId && $campaignId) {
                $callerIds = $this->callerIdModel->getByCampaignAndInstance($campaignId, $instanceId);
            } elseif ($instanceId) {
                $callerIds = $this->callerIdModel->getByInstance($instanceId);
            } elseif ($campaignId) {
                $callerIds = $this->callerIdModel->getByCampaign($campaignId);
            } else {
                $callerIds = $this->callerIdModel->getAll();
            }
            
            return ['success' => true, 'data' => $callerIds];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getByCampaign($campaignId)
    {
        try {
            $callerIds = $this->callerIdModel->getByCampaign($campaignId);
            return ['success' => true, 'data' => $callerIds];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getByInstance($instanceId)
    {
        try {
            $callerIds = $this->callerIdModel->getByInstance($instanceId);
            return ['success' => true, 'data' => $callerIds];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getByCampaignAndInstance($campaignId, $instanceId)
    {
        try {
            $callerIds = $this->callerIdModel->getByCampaignAndInstance($campaignId, $instanceId);
            return ['success' => true, 'data' => $callerIds];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getById($id)
    {
        try {
            $callerId = $this->callerIdModel->getById($id);
            if ($callerId) {
                return ['success' => true, 'data' => $callerId];
            } else {
                return ['success' => false, 'error' => 'Caller ID not found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create($data)
    {
        try {
            if (empty($data['campaign_id']) || empty($data['phone_number'])) {
                return ['success' => false, 'error' => 'Required fields are missing'];
            }

            if ($this->callerIdModel->create($data)) {
                return ['success' => true, 'message' => 'Caller ID created successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to create caller ID'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function update($id, $data)
    {
        try {
            if ($this->callerIdModel->update($id, $data)) {
                return ['success' => true, 'message' => 'Caller ID updated successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to update caller ID'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete($id)
    {
        try {
            if ($this->callerIdModel->delete($id)) {
                return ['success' => true, 'message' => 'Caller ID deleted successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to delete caller ID'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function rotate($id, $reason)
    {
        try {
            if ($this->callerIdModel->rotate($id, $reason)) {
                return ['success' => true, 'message' => 'Caller ID rotated successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to rotate caller ID'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getPerformance($id, $days = 30)
    {
        try {
            $performance = $this->callerIdModel->getCallerIdPerformance($id, $days);
            return ['success' => true, 'data' => $performance];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getStats($id)
    {
        try {
            $stats = $this->callerIdModel->getCallerIdStats($id);
            return ['success' => true, 'data' => $stats];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getNeedingRotation()
    {
        try {
            $callerIds = $this->callerIdModel->getCallerIdsNeedingRotation();
            return ['success' => true, 'data' => $callerIds];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncFromVicidial($instanceId)
    {
        try {
            $result = $this->callerIdModel->syncFromVicidial($instanceId);
            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCallerIdCount()
    {
        try {
            $count = $this->callerIdModel->getCallerIdCount();
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCallerIdCountByStatus($status)
    {
        try {
            $count = $this->callerIdModel->getCallerIdCountByStatus($status);
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 