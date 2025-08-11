<?php

namespace VicidialSupport\Models;

use PDO;

class User
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function authenticate($username, $password)
    {
        $stmt = $this->db->prepare("
            SELECT id, username, password_hash, first_name, last_name, email, role, status
            FROM users 
            WHERE username = ? AND status = 'active'
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return false;
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT id, username, first_name, last_name, email, role, status, created_at
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByUsername($username)
    {
        $stmt = $this->db->prepare("
            SELECT id, username, first_name, last_name, email, role, status, created_at
            FROM users 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password_hash, first_name, last_name, email, role, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['username'],
            $hashedPassword,
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['role'] ?? 'user',
            $data['status'] ?? 'active'
        ]);
    }

    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== 'password') {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (isset($data['password'])) {
            $fields[] = "password_hash = ?";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $values[] = $id;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($values);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("
            SELECT id, username, first_name, last_name, email, role, status, created_at
            FROM users 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUserInstancePermissions($userId)
    {
        $stmt = $this->db->prepare("
            SELECT uip.*, vi.instance_name
            FROM user_instance_permissions uip
            JOIN vicidial_instances vi ON uip.instance_id = vi.id
            WHERE uip.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function hasPermission($userId, $instanceId, $permission)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM user_instance_permissions
            WHERE user_id = ? AND instance_id = ? AND $permission = 1
        ");
        $stmt->execute([$userId, $instanceId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
} 