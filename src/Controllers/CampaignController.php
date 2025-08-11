<?php

namespace VicidialSupport\Controllers;

use VicidialSupport\Models\Campaign;

class CampaignController
{
    private $campaignModel;

    public function __construct($db, $campaignModel = null)
    {
        $this->campaignModel = $campaignModel ?? new Campaign($db);
    }

    public function getCampaignModel()
    {
        return $this->campaignModel;
    }

    public function getAll()
    {
        try {
            $campaigns = $this->campaignModel->getAll();
            return ['success' => true, 'data' => $campaigns];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getByInstance($instanceId)
    {
        try {
            $campaigns = $this->campaignModel->getByInstance($instanceId);
            return ['success' => true, 'data' => $campaigns];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getById($id)
    {
        try {
            $campaign = $this->campaignModel->getById($id);
            if ($campaign) {
                return ['success' => true, 'data' => $campaign];
            } else {
                return ['success' => false, 'error' => 'Campaign not found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create($data)
    {
        try {
            if (empty($data['instance_id']) || empty($data['campaign_name'])) {
                return ['success' => false, 'error' => 'Required fields are missing'];
            }

            if ($this->campaignModel->create($data)) {
                return ['success' => true, 'message' => 'Campaign created successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to create campaign'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function update($id, $data)
    {
        try {
            if ($this->campaignModel->update($id, $data)) {
                return ['success' => true, 'message' => 'Campaign updated successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to update campaign'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete($id)
    {
        try {
            if ($this->campaignModel->delete($id)) {
                return ['success' => true, 'message' => 'Campaign deleted successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to delete campaign'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCampaignPerformance($id, $days = 30)
    {
        try {
            $performance = $this->campaignModel->getCampaignPerformance($id, $days);
            return ['success' => true, 'data' => $performance];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCampaignStats($id)
    {
        try {
            $stats = $this->campaignModel->getCampaignStats($id);
            return ['success' => true, 'data' => $stats];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncFromVicidial($instanceId)
    {
        try {
            $result = $this->campaignModel->syncFromVicidial($instanceId);
            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCampaignCount()
    {
        try {
            $count = $this->campaignModel->getCampaignCount();
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCampaignCountByStatus($status)
    {
        try {
            $count = $this->campaignModel->getCampaignCountByStatus($status);
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 