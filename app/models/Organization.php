<?php

namespace App\Models;

use App\Utils\Supabase;

class Organization
{
    private $supabase;

    public function __construct()
    {
        $this->supabase = Supabase::getInstance();
    }

    /**
     * Get organization by ID
     */
    public function getById($orgId)
    {
        try {
            $response = $this->supabase->query('organizations', '*', ['id' => $orgId]);
            return $response['success'] ? $response['data'][0] ?? null : null;
        } catch (\Exception $e) {
            error_log('Error fetching organization: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all active organizations
     */
    public function getAll()
    {
        try {
            $response = $this->supabase->query('organizations', '*', ['is_active' => 'true']);
            return $response['success'] ? $response['data'] : [];
        } catch (\Exception $e) {
            error_log('Error fetching organizations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create new organization
     */
    public function create($data, $accessToken = null)
    {
        try {
            $orgData = array_merge($data, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'is_active' => $data['is_active'] ?? true
            ]);

            $response = $this->supabase->insert('organizations', $orgData, $accessToken);
            return $response['success'] ? ($response['data'][0]['id'] ?? true) : false;
        } catch (\Exception $e) {
            error_log('Error creating organization: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update organization profile
     */
    public function update($orgId, $data, $accessToken = null)
    {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $response = $this->supabase->update('organizations', $orgId, $data, $accessToken);
            return $response['success'];
        } catch (\Exception $e) {
            error_log('Error updating organization: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete/Deactivate organization
     */
    public function delete($orgId, $accessToken = null)
    {
        try {
            return $this->update($orgId, ['is_active' => false], $accessToken);
        } catch (\Exception $e) {
            error_log('Error deleting organization: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get organization admin (president)
     */
    public function getAdmin($orgId)
    {
        try {
            $userModel = new User();
            $admins = $userModel->getByOrganization($orgId);
            
            foreach ($admins as $admin) {
                $role = $userModel->getRole($admin['id']);
                if ($role && $role['name'] === 'org_admin') {
                    return $admin;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            error_log('Error fetching org admin: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if organization exists
     */
    public function exists($orgId)
    {
        $org = $this->getById($orgId);
        return $org !== null && $org['is_active'] === true;
    }

    /**
     * Get all active organizations with their details
     */
    public function getAllWithDetails()
    {
        try {
            $organizations = $this->getAll();
            
            foreach ($organizations as &$org) {
                // Get admin info
                $org['admin'] = $this->getAdmin($org['id']);
                
                // Get upcoming bookings count
                $bookingModel = new BookingRequest();
                $bookings = $bookingModel->getByOrganization($org['id']);
                $org['bookings'] = $bookings;
                $org['upcoming_bookings_count'] = count(array_filter($bookings, function($b) {
                    return $b['status'] === 'accepted' && strtotime($b['event_date']) >= time();
                }));
            }
            
            return $organizations;
        } catch (\Exception $e) {
            error_log('Error fetching organizations with details: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get organization with all accepted bookings (for calendar)
     */
    public function getWithAcceptedBookings($orgId)
    {
        try {
            $org = $this->getById($orgId);
            if (!$org) {
                return null;
            }

            $bookingModel = new BookingRequest();
            $allBookings = $bookingModel->getByOrganization($orgId);
            
            $org['accepted_bookings'] = array_filter($allBookings, function($b) {
                return $b['status'] === 'accepted';
            });
            
            return $org;
        } catch (\Exception $e) {
            error_log('Error fetching organization with bookings: ' . $e->getMessage());
            return null;
        }
    }
}
