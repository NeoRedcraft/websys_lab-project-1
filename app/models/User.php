<?php

namespace App\Models;

use App\Utils\Supabase;

class User
{
    private $supabase;

    public function __construct()
    {
        $this->supabase = Supabase::getInstance();
    }

    /**
     * Get user by ID
     */
    public function getById($userId)
    {
        try {
            $response = $this->supabase->query('users_extended', '*', ['id' => $userId]);
            return $response['success'] ? $response['data'][0] ?? null : null;
        } catch (\Exception $e) {
            error_log('Error fetching user: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by email
     */
    public function getByEmail($email)
    {
        try {
            $response = $this->supabase->query('users_extended', '*', ['email' => $email]);
            return $response['success'] ? $response['data'][0] ?? null : null;
        } catch (\Exception $e) {
            error_log('Error fetching user by email: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create new user profile in users_extended
     */
    public function create($userId, $email, $fullName, $roleId, $orgId = null, $accessToken = null)
    {
        try {
            $data = [
                'id' => $userId,
                'email' => $email,
                'full_name' => $fullName,
                'role_id' => $roleId,
                'org_id' => $orgId,
                'is_active' => true
            ];

            $response = $this->supabase->insert('users_extended', $data, $accessToken);
            return $response['success'] ? $response['data'][0] ?? true : false;
        } catch (\Exception $e) {
            error_log('Error creating user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user profile
     */
    public function update($userId, $data, $accessToken = null)
    {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $response = $this->supabase->update('users_extended', $userId, $data, $accessToken);
            return $response['success'];
        } catch (\Exception $e) {
            error_log('Error updating user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get users by role
     */
    public function getByRole($roleId)
    {
        try {
            // Query all users with specific role_id
            $url = $this->supabase->getUrl() . "/rest/v1/users_extended?role_id=eq.{$roleId}";
            $response = $this->supabase->query('users_extended', '*', ['role_id' => $roleId]);
            return $response['success'] ? $response['data'] : [];
        } catch (\Exception $e) {
            error_log('Error fetching users by role: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users by organization
     */
    public function getByOrganization($orgId)
    {
        try {
            $response = $this->supabase->query('users_extended', '*', ['org_id' => $orgId]);
            return $response['success'] ? $response['data'] : [];
        } catch (\Exception $e) {
            error_log('Error fetching users by org: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate email domain (@mymail.mapua.edu.ph)
     */
    public function isValidMapuaEmail($email)
    {
        return strpos($email, '@mymail.mapua.edu.ph') !== false;
    }

    /**
     * Check if user exists
     */
    public function exists($email)
    {
        $user = $this->getByEmail($email);
        return $user !== null;
    }

    /**
     * Deactivate user
     */
    public function deactivate($userId)
    {
        return $this->update($userId, ['is_active' => false]);
    }

    /**
     * Get user's role
     */
    /**
     * Get user's role
     */
    public function getRole($userId)
    {
        $user = $this->getById($userId);
        
        // If user is NOT in users_extended, they are likely a new registrant
        if (!$user) {
            // Check your 'roles' table for the ID of 'organizer'
            // Usually, this is '3' or whatever your organizer ID is
            return [
                'id' => 3, 
                'role_name' => 'organizer'
            ];
        }

        try {
            $response = $this->supabase->query('roles', '*', ['id' => $user['role_id']]);
            return $response['success'] ? $response['data'][0] ?? null : null;
        } catch (\Exception $e) {
            error_log('Error fetching role: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all users
     */
    public function getAll()
    {
        try {
            $url = $this->supabase->getUrl() . "/rest/v1/users_extended";
            $response = $this->supabase->makeRequest('GET', $url);
            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            error_log('Error fetching all users: ' . $e->getMessage());
            return [];
        }
    }
}