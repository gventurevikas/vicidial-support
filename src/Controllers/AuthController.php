<?php

namespace VicidialSupport\Controllers;

use VicidialSupport\Models\User;

class AuthController
{
    private $userModel;

    public function __construct($db, $userModel = null)
    {
        $this->userModel = $userModel ?? new User($db);
    }

    public function getUserModel()
    {
        return $this->userModel;
    }

    public function login()
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'error' => 'Invalid request method'];
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Username and password are required'];
        }

        $user = $this->userModel->authenticate($username, $password);

        if ($user) {
            // Start session and store user data
            if (session_status() === PHP_SESSION_NONE && !defined('TESTING')) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            return ['success' => true, 'user' => $user];
        } else {
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE && !defined('TESTING')) {
            session_start();
        }
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE && !defined('TESTING')) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->userModel->getById($_SESSION['user_id']);
    }

    public function requireAuth()
    {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    public function requireRole($role)
    {
        $this->requireAuth();
        
        if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
    }

    public function createUser($data)
    {
        // Check if username already exists
        $existingUser = $this->userModel->getByUsername($data['username']);
        if ($existingUser) {
            return ['success' => false, 'error' => 'Username already exists'];
        }

        if ($this->userModel->create($data)) {
            return ['success' => true, 'message' => 'User created successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to create user'];
        }
    }

    public function updateUser($id, $data)
    {
        if ($this->userModel->update($id, $data)) {
            return ['success' => true, 'message' => 'User updated successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to update user'];
        }
    }

    public function deleteUser($id)
    {
        if ($this->userModel->delete($id)) {
            return ['success' => true, 'message' => 'User deleted successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to delete user'];
        }
    }

    public function getAllUsers()
    {
        return $this->userModel->getAll();
    }

    public function getUserById($id)
    {
        return $this->userModel->getById($id);
    }

    public function changePassword($userId, $currentPassword, $newPassword)
    {
        $user = $this->userModel->getById($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }

        // Update password
        if ($this->userModel->update($userId, ['password' => $newPassword])) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to change password'];
        }
    }
} 