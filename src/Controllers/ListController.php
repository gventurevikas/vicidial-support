<?php

namespace VicidialSupport\Controllers;

use VicidialSupport\Models\VicidialList;

class ListController
{
    private $listModel;

    public function __construct($db)
    {
        $this->listModel = new VicidialList($db);
    }

    public function getAll()
    {
        try {
            $lists = $this->listModel->getAll();
            return ['success' => true, 'data' => $lists];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getByCampaign($campaignId)
    {
        try {
            $lists = $this->listModel->getByCampaign($campaignId);
            return ['success' => true, 'data' => $lists];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getById($id)
    {
        try {
            $list = $this->listModel->getById($id);
            if ($list) {
                return ['success' => true, 'data' => $list];
            } else {
                return ['success' => false, 'error' => 'List not found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create($data)
    {
        try {
            if (empty($data['campaign_id']) || empty($data['list_name'])) {
                return ['success' => false, 'error' => 'Required fields are missing'];
            }

            if ($this->listModel->create($data)) {
                return ['success' => true, 'message' => 'List created successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to create list'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function update($id, $data)
    {
        try {
            if ($this->listModel->update($id, $data)) {
                return ['success' => true, 'message' => 'List updated successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to update list'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete($id)
    {
        try {
            if ($this->listModel->delete($id)) {
                return ['success' => true, 'message' => 'List deleted successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to delete list'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function rotate($id, $reason)
    {
        try {
            if ($this->listModel->rotate($id, $reason)) {
                return ['success' => true, 'message' => 'List rotated successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to rotate list'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getPerformance($id, $days = 30)
    {
        try {
            $performance = $this->listModel->getListPerformance($id, $days);
            return ['success' => true, 'data' => $performance];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getStats($id)
    {
        try {
            $stats = $this->listModel->getListStats($id);
            return ['success' => true, 'data' => $stats];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getNeedingRotation()
    {
        try {
            $lists = $this->listModel->getListsNeedingRotation();
            return ['success' => true, 'data' => $lists];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncFromVicidial($instanceId)
    {
        try {
            $result = $this->listModel->syncFromVicidial($instanceId);
            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getListCount()
    {
        try {
            $count = $this->listModel->getListCount();
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getListCountByStatus($status)
    {
        try {
            $count = $this->listModel->getListCountByStatus($status);
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 