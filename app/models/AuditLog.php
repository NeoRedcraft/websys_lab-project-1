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
            $normalizedEntityId = $this->normalizeEntityId($entityId);
            $oldPayload = $this->normalizePayload($oldValues);
            $newPayload = $this->normalizePayload($newValues);

            // audit_logs.entity_id is BIGINT. Keep string/UUID references in payload metadata.
            if ($normalizedEntityId === null && $entityId !== null && (string) $entityId !== '') {
                if (!is_array($newPayload)) {
                    $newPayload = [];
                }
                $newPayload['_entity_ref'] = (string) $entityId;
            }

            $data = [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $normalizedEntityId,
                'old_values' => $oldPayload,
                'new_values' => $newPayload,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ];

            $this->supabase->adminRequest('POST', '/rest/v1/audit_logs', $data, [
                'Prefer' => 'return=representation',
                'Content-Type' => 'application/json',
            ]);

            return true;
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
            $safeLimit = max(1, (int) $limit);
            $safeOffset = max(0, (int) $offset);
            $endpoint = '/rest/v1/audit_logs?order=created_at.desc&limit=' . $safeLimit . '&offset=' . $safeOffset;

            $response = $this->supabase->adminRequest('GET', $endpoint, [], ['Prefer' => 'return=representation']);
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
            $safeLimit = max(1, (int) $limit);
            $endpoint = '/rest/v1/audit_logs?user_id=eq.' . rawurlencode((string) $userId)
                . '&order=created_at.desc&limit=' . $safeLimit;

            $response = $this->supabase->adminRequest('GET', $endpoint, [], ['Prefer' => 'return=representation']);
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
            $normalizedEntityId = $this->normalizeEntityId($entityId);
            $endpoint = '/rest/v1/audit_logs?entity_type=eq.' . rawurlencode((string) $entityType) . '&order=created_at.desc';
            if ($normalizedEntityId !== null) {
                $endpoint .= '&entity_id=eq.' . $normalizedEntityId;
            }

            $response = $this->supabase->adminRequest('GET', $endpoint, [], ['Prefer' => 'return=representation']);
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
    public function logAuth($userId, $action, $entityId = null, $newValues = null)
    {
        return $this->log($userId, $action, 'authentication', $entityId, null, $newValues);
    }

    private function normalizeEntityId($entityId)
    {
        if ($entityId === null) {
            return null;
        }

        $value = trim((string) $entityId);
        if ($value === '') {
            return null;
        }

        if (!preg_match('/^-?\d+$/', $value)) {
            return null;
        }

        return (int) $value;
    }

    private function normalizePayload($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        return ['value' => (string) $value];
    }
}
