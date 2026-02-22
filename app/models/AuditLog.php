<?php

namespace App\Models;

use App\Utils\Supabase;

class AuditLog
{
    private $supabase;

    public function __construct()
    {
        $this->supabase = Supabase::getInstance();
    }

    /**
     * Log an action
     */
    public function log($userId, $action, $entityType, $entityId = null, $oldValues = null, $newValues = null, $accessToken = null)
    {
        try {
            $data = [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ];

            $response = $this->supabase->insert('audit_logs', $data, $accessToken);
            return $response['success'];
        } catch (\Exception $e) {
            error_log('Error logging audit: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all audit logs (admin only)
     */
    public function getAll($limit = 100, $offset = 0)
    {
        try {
            $url = $this->supabase->getUrl() . "/rest/v1/audit_logs?order=created_at.desc&limit={$limit}&offset={$offset}";
            
            $response = $this->supabase->makeRequest('GET', $url);
            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            error_log('Error fetching audit logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit logs for a specific user
     */
    public function getByUser($userId, $limit = 100)
    {
        try {
            $url = $this->supabase->getUrl() . "/rest/v1/audit_logs?user_id=eq.{$userId}&order=created_at.desc&limit={$limit}";
            
            $response = $this->supabase->makeRequest('GET', $url);
            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            error_log('Error fetching user audit logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit logs for a specific entity
     */
    public function getByEntity($entityType, $entityId)
    {
        try {
            $url = $this->supabase->getUrl() . "/rest/v1/audit_logs?entity_type=eq.{$entityType}&entity_id=eq.{$entityId}&order=created_at.desc";
            
            $response = $this->supabase->makeRequest('GET', $url);
            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            error_log('Error fetching entity audit logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Log booking action
     */
    public function logBooking($userId, $action, $bookingId, $oldValues = null, $newValues = null)
    {
        return $this->log($userId, $action, 'booking_request', $bookingId, $oldValues, $newValues);
    }

    /**
     * Log user action
     */
    public function logUser($userId, $action, $targetUserId, $oldValues = null, $newValues = null)
    {
        return $this->log($userId, $action, 'user', $targetUserId, $oldValues, $newValues);
    }

    /**
     * Log organization action
     */
    public function logOrganization($userId, $action, $orgId, $oldValues = null, $newValues = null)
    {
        return $this->log($userId, $action, 'organization', $orgId, $oldValues, $newValues);
    }

    /**
     * Log authentication action
     */
    public function logAuth($userId, $action)
    {
        return $this->log($userId, $action, 'authentication', null);
    }
}
