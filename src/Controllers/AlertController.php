<?php

namespace VicidialSupport\Controllers;

use VicidialSupport\Models\Alert;

class AlertController
{
    private $alertModel;

    public function __construct($db)
    {
        $this->alertModel = new Alert($db);
    }

    public function getAll()
    {
        try {
            $alerts = $this->alertModel->getAll();
            return ['success' => true, 'data' => $alerts];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getUnresolved()
    {
        try {
            $alerts = $this->alertModel->getUnresolved();
            return ['success' => true, 'data' => $alerts];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getByType($type)
    {
        try {
            $alerts = $this->alertModel->getByType($type);
            return ['success' => true, 'data' => $alerts];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getByCategory($category)
    {
        try {
            $alerts = $this->alertModel->getByCategory($category);
            return ['success' => true, 'data' => $alerts];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getById($id)
    {
        try {
            $alert = $this->alertModel->getById($id);
            if ($alert) {
                return ['success' => true, 'data' => $alert];
            } else {
                return ['success' => false, 'error' => 'Alert not found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create($data)
    {
        try {
            if (empty($data['alert_type']) || empty($data['alert_category']) || 
                empty($data['title']) || empty($data['message'])) {
                return ['success' => false, 'error' => 'Required fields are missing'];
            }

            if ($this->alertModel->create($data)) {
                return ['success' => true, 'message' => 'Alert created successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to create alert'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function update($id, $data)
    {
        try {
            if ($this->alertModel->update($id, $data)) {
                return ['success' => true, 'message' => 'Alert updated successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to update alert'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete($id)
    {
        try {
            if ($this->alertModel->delete($id)) {
                return ['success' => true, 'message' => 'Alert deleted successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to delete alert'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function resolve($id)
    {
        try {
            if ($this->alertModel->resolve($id)) {
                return ['success' => true, 'message' => 'Alert resolved successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to resolve alert'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function resolveAll()
    {
        try {
            if ($this->alertModel->resolveAll()) {
                return ['success' => true, 'message' => 'All alerts resolved successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to resolve alerts'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAlertCount()
    {
        try {
            $count = $this->alertModel->getAlertCount();
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAlertCountByType($type)
    {
        try {
            $count = $this->alertModel->getAlertCountByType($type);
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAlertCountByStatus($resolved)
    {
        try {
            $count = $this->alertModel->getAlertCountByStatus($resolved);
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAlertStats()
    {
        try {
            $stats = $this->alertModel->getAlertStats();
            return ['success' => true, 'data' => $stats];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createServerAlert($serverId, $type, $title, $message)
    {
        try {
            if ($this->alertModel->createServerAlert($serverId, $type, $title, $message)) {
                return ['success' => true, 'message' => 'Server alert created successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to create server alert'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createCallerIdAlert($callerId, $type, $title, $message)
    {
        try {
            if ($this->alertModel->createCallerIdAlert($callerId, $type, $title, $message)) {
                return ['success' => true, 'message' => 'Caller ID alert created successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to create caller ID alert'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createListAlert($listId, $type, $title, $message)
    {
        try {
            if ($this->alertModel->createListAlert($listId, $type, $title, $message)) {
                return ['success' => true, 'message' => 'List alert created successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to create list alert'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createCampaignAlert($campaignId, $type, $title, $message)
    {
        try {
            if ($this->alertModel->createCampaignAlert($campaignId, $type, $title, $message)) {
                return ['success' => true, 'message' => 'Campaign alert created successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to create campaign alert'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createSystemAlert($type, $title, $message)
    {
        try {
            if ($this->alertModel->createSystemAlert($type, $title, $message)) {
                return ['success' => true, 'message' => 'System alert created successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to create system alert'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 