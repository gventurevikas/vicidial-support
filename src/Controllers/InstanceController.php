<?php

namespace VicidialSupport\Controllers;

use VicidialSupport\Models\VicidialInstance;

class InstanceController
{
    private $instanceModel;

    public function __construct($db, $instanceModel = null)
    {
        $this->instanceModel = $instanceModel ?? new VicidialInstance($db);
    }

    public function getInstanceModel()
    {
        return $this->instanceModel;
    }

    public function getAll()
    {
        try {
            $instances = $this->instanceModel->getAll();
            return ['success' => true, 'data' => $instances];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getActive()
    {
        try {
            $instances = $this->instanceModel->getActive();
            return ['success' => true, 'data' => $instances];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getById($id)
    {
        try {
            $instance = $this->instanceModel->getById($id);
            if ($instance) {
                return ['success' => true, 'data' => $instance];
            } else {
                return ['success' => false, 'error' => 'Instance not found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create($data)
    {
        try {
            if (empty($data['instance_name']) || empty($data['vicidial_db_host']) || 
                empty($data['vicidial_db_name']) || empty($data['vicidial_db_user']) || 
                empty($data['vicidial_db_password'])) {
                return ['success' => false, 'error' => 'Required fields are missing'];
            }

            if ($this->instanceModel->create($data)) {
                return ['success' => true, 'message' => 'Instance created successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to create instance'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function update($id, $data)
    {
        try {
            if ($this->instanceModel->update($id, $data)) {
                return ['success' => true, 'message' => 'Instance updated successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to update instance'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete($id)
    {
        try {
            if ($this->instanceModel->delete($id)) {
                return ['success' => true, 'message' => 'Instance deleted successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to delete instance'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function testConnection($id)
    {
        try {
            $result = $this->instanceModel->testConnection($id);
            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getStats($id)
    {
        try {
            $stats = $this->instanceModel->getInstanceStats($id);
            if ($stats) {
                return ['success' => true, 'data' => $stats];
            } else {
                return ['success' => false, 'error' => 'Failed to get instance stats'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getInstanceCount()
    {
        try {
            $count = $this->instanceModel->getInstanceCount();
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getInstanceCountByStatus($status)
    {
        try {
            $count = $this->instanceModel->getInstanceCountByStatus($status);
            return ['success' => true, 'data' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 