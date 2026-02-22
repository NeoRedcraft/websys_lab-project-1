<?php

namespace App\Models;

use App\Utils\Supabase;

class BookingRequest
{
    private $supabase;

    public function __construct()
    {
        $this->supabase = Supabase::getInstance();
    }

    /**
     * Get booking request by ID
     */
    public function getById($requestId)
    {
        try {
            $response = $this->supabase->query('booking_requests', '*', ['id' => $requestId]);
            return $response['success'] ? $response['data'][0] ?? null : null;
        } catch (\Exception $e) {
            error_log('Error fetching booking: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all booking requests by organizer
     */
    public function getByOrganizer($organizerId)
    {
        try {
            // Note: Supabase query builder needs to be enhanced to support complex filters
            // For now, we'll fetch and filter client-side
            $url = $this->supabase->getUrl() . "/rest/v1/booking_requests?organizer_id=eq.{$organizerId}";
            
            $response = $this->supabase->makeRequest('GET', $url);
            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            error_log('Error fetching organizer bookings: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all booking requests for an organization
     */
    public function getByOrganization($organizationId)
    {
        try {
            $url = $this->supabase->getUrl() . "/rest/v1/booking_requests?organization_id=eq.{$organizationId}";
            
            $response = $this->supabase->makeRequest('GET', $url);
            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            error_log('Error fetching organization bookings: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all booking requests (for admin audit)
     */
    public function getAll()
    {
        try {
            $url = $this->supabase->getUrl() . "/rest/v1/booking_requests";
            
            $response = $this->supabase->makeRequest('GET', $url);
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
            $bookingData = array_merge($data, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'status' => $data['status'] ?? 'pending'
            ]);

            $response = $this->supabase->insert('booking_requests', $bookingData, $accessToken);
            return $response['success'] ? ($response['data'][0]['id'] ?? true) : false;
        } catch (\Exception $e) {
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
            $data['updated_at'] = date('Y-m-d H:i:s');
            $response = $this->supabase->update('booking_requests', $requestId, $data, $accessToken);
            return $response['success'];
        } catch (\Exception $e) {
            error_log('Error updating booking: ' . $e->getMessage());
            return false;
        }
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
    public function getStats($orgId = null)
    {
        try {
            $all = $orgId ? $this->getByOrganization($orgId) : $this->getAll();
            
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
