<?php

namespace App\Models;

use App\Utils\Supabase;

class BookingRequest
{
    private $supabase;
    private $lastError = null;

    private function resolveHeaders($accessToken = null)
    {
        $token = $accessToken ?: session_get('access_token');
        if (!$token) {
            return [];
        }

        return [
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    public function __construct()
    {
        $this->supabase = Supabase::getInstance();
    }

    /**
     * Get booking request by ID
     */
    public function getById($requestId, $accessToken = null)
    {
        try {
            $endpoint = '/rest/v1/booking_requests?select=*&id=eq.' . urlencode((string) $requestId) . '&limit=1';
            $response = $this->supabase->makeRequest('GET', $endpoint, [], $this->resolveHeaders($accessToken));
            return is_array($response) ? ($response[0] ?? null) : null;
        } catch (\Exception $e) {
            error_log('Error fetching booking: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all booking requests by organizer
     */
    public function getByOrganizer($organizerId, $accessToken = null)
    {
        try {
            // Note: Supabase query builder needs to be enhanced to support complex filters
            // For now, we'll fetch and filter client-side
            $url = $this->supabase->getUrl() . "/rest/v1/booking_requests?organizer_id=eq.{$organizerId}";
            
            $response = $this->supabase->makeRequest('GET', $url, [], $this->resolveHeaders($accessToken));
            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            error_log('Error fetching organizer bookings: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all booking requests for an organization
     */
    public function getByOrganization($organizationId, $accessToken = null)
    {
        try {
            $url = $this->supabase->getUrl() . "/rest/v1/booking_requests?organization_id=eq.{$organizationId}";
            
            $response = $this->supabase->makeRequest('GET', $url, [], $this->resolveHeaders($accessToken));
            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            error_log('Error fetching organization bookings: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all booking requests (for admin audit)
     */
    public function getAll($accessToken = null)
    {
        try {
            $url = $this->supabase->getUrl() . "/rest/v1/booking_requests";
            
            $response = $this->supabase->makeRequest('GET', $url, [], $this->resolveHeaders($accessToken));
            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            error_log('Error fetching all bookings: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create new booking request
     */
    public function create($data, $accessToken = null)
    {
        try {
            $this->lastError = null;
            $bookingData = array_merge($data, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'status' => $data['status'] ?? 'pending'
            ]);

            $response = $this->supabase->insert('booking_requests', $bookingData, $accessToken);
            if (!$response['success']) {
                $this->lastError = $response['error'] ?? 'Failed to create booking request';
                return false;
            }

            return $response['data'][0]['id'] ?? true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Error creating booking: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update booking request
     */
    public function update($requestId, $data, $accessToken = null)
    {
        try {
            $this->lastError = null;
            $data['updated_at'] = date('Y-m-d H:i:s');
            $response = $this->supabase->update('booking_requests', $requestId, $data, $accessToken);
            if (!$response['success']) {
                $this->lastError = $response['error'] ?? 'Failed to update booking request';
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Error updating booking: ' . $e->getMessage());
            return false;
        }
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Update booking request status
     */
    public function updateStatus($requestId, $status, $accessToken = null)
    {
        try {
            $data = [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $response = $this->supabase->update('booking_requests', $requestId, $data, $accessToken);
            return $response['success'];
        } catch (\Exception $e) {
            error_log('Error updating booking status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete booking request (only if pending)
     */
    public function delete($requestId, $accessToken = null)
    {
        try {
            $booking = $this->getById($requestId);
            if (!$booking || $booking['status'] !== 'pending') {
                return false;
            }

            $response = $this->supabase->delete('booking_requests', $requestId, $accessToken);
            return $response['success'];
        } catch (\Exception $e) {
            error_log('Error deleting booking: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Accept booking request
     */
    public function accept($requestId, $notes = '', $accessToken = null)
    {
        try {
            $data = [
                'status' => 'accepted',
                'accepted_notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $response = $this->supabase->update('booking_requests', $requestId, $data, $accessToken);
            return $response['success'];
        } catch (\Exception $e) {
            error_log('Error accepting booking: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Decline booking request
     */
    public function decline($requestId, $reason = '', $accessToken = null)
    {
        try {
            $data = [
                'status' => 'declined',
                'declined_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $response = $this->supabase->update('booking_requests', $requestId, $data, $accessToken);
            return $response['success'];
        } catch (\Exception $e) {
            error_log('Error declining booking: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get booking statistics
     */
    public function getStats($orgId = null, $accessToken = null)
    {
        try {
            $all = $orgId ? $this->getByOrganization($orgId, $accessToken) : $this->getAll($accessToken);
            
            return [
                'total' => count($all),
                'pending' => count(array_filter($all, fn($b) => $b['status'] === 'pending')),
                'accepted' => count(array_filter($all, fn($b) => $b['status'] === 'accepted')),
                'declined' => count(array_filter($all, fn($b) => $b['status'] === 'declined'))
            ];
        } catch (\Exception $e) {
            error_log('Error calculating booking stats: ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'accepted' => 0, 'declined' => 0];
        }
    }
}
