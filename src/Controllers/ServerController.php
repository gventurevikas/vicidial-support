<?php

namespace VicidialSupport\Controllers;

use VicidialSupport\Models\Server;

class ServerController
{
    private $serverModel;

    public function __construct($db)
    {
        $this->serverModel = new Server($db);
    }

    public function getAll()
    {
        try {
            $servers = $this->serverModel->getAll();
            return ['success' => true, 'data' => $servers];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getByInstance($instanceId)
    {
        try {
            $servers = $this->serverModel->getByInstance($instanceId);
            return ['success' => true, 'data' => $servers];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getById($id)
    {
        try {
            $server = $this->serverModel->getById($id);
            if ($server) {
                return ['success' => true, 'data' => $server];
            } else {
                return ['success' => false, 'error' => 'Server not found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create($data)
    {
        try {
            if (empty($data['instance_id']) || empty($data['server_name']) || 
                empty($data['server_type']) || empty($data['ip_address'])) {
                return ['success' => false, 'error' => 'Required fields are missing'];
            }

            if ($this->serverModel->create($data)) {
                return ['success' => true, 'message' => 'Server created successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to create server'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function update($id, $data)
    {
        try {
            if ($this->serverModel->update($id, $data)) {
                return ['success' => true, 'message' => 'Server updated successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to update server'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete($id)
    {
        try {
            if ($this->serverModel->delete($id)) {
                return ['success' => true, 'message' => 'Server deleted successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to delete server'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getLatestMetrics($serverId)
    {
        try {
            $metrics = $this->serverModel->getLatestMetrics($serverId);
            return ['success' => true, 'data' => $metrics];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getMetricsHistory($serverId, $days = 7)
    {
        try {
            $metrics = $this->serverModel->getMetricsHistory($serverId, $days);
            return ['success' => true, 'data' => $metrics];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function addMetrics($serverId, $metrics)
    {
        try {
            if ($this->serverModel->addMetrics($serverId, $metrics)) {
                return ['success' => true, 'message' => 'Metrics added successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to add metrics'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getServerCount()
    {
        try {
            $count = $this->serverModel->getServerCount();
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getServerCountByStatus($status)
    {
        try {
            $count = $this->serverModel->getServerCountByStatus($status);
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getServersWithIssues()
    {
        try {
            $servers = $this->serverModel->getServersWithIssues();
            return ['success' => true, 'data' => $servers];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getServerStats()
    {
        try {
            $stats = $this->serverModel->getServerStats();
            return ['success' => true, 'data' => $stats];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 