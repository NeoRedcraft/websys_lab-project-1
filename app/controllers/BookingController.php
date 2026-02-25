<?php

namespace App\Controllers;

use App\Middleware\Gatekeeper;
use App\Models\User;
use App\Models\Organization;
use App\Models\BookingRequest;
use App\Models\AuditLog;

class BookingController
{
    private $gatekeeper;
    private $userModel;
    private $organizationModel;
    private $bookingModel;
    private $auditLog;

    public function __construct()
    {
        $this->gatekeeper = new Gatekeeper();
        $this->userModel = new User();
        $this->organizationModel = new Organization();
        $this->bookingModel = new BookingRequest();
        $this->auditLog = new AuditLog();

        // Require organizer role
        $this->gatekeeper->requireOrganizerAccess();
    }

    /**
     * List user's booking requests
     */
    public function listMyBookings($params = [])
    {
        $user = get_user();
        $bookings = $this->bookingModel->getByOrganizer($user['id']);

        return view('bookings/my-bookings', [
            'bookings' => $bookings,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * Create new booking request form (FR-05: Dynamic Booking & Coordination)
     */
    public function createBooking($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $organizations = $this->organizationModel->getAll();

            return view('bookings/booking-form', [
                'booking' => null,
                'organizations' => $organizations,
                'csrfToken' => csrf_token(),
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = get_user();
            $orgId = $_POST['org_id'] ?? null;
            $eventName = $_POST['event_name'] ?? '';
            $eventDate = $_POST['event_date'] ?? '';
            $venue = $_POST['venue'] ?? '';
            $technicalNeeds = $_POST['technical_needs'] ?? '';
            $expectedAttendees = $_POST['expected_attendees'] ?? 0;
            $additionalNotes = $_POST['additional_notes'] ?? '';

            // Validate required fields
            if (!$orgId || !$eventName || !$eventDate || !$venue) {
                $organizations = $this->organizationModel->getAll();
                return view('bookings/booking-form', [
                    'error' => 'All required fields must be filled',
                    'organizations' => $organizations,
                    'csrfToken' => csrf_token(),
                ]);
            }

            // Verify organization exists
            $org = $this->organizationModel->getById($orgId);
            if (!$org) {
                return view('bookings/booking-form', [
                    'error' => 'Invalid organization selected',
                    'organizations' => $this->organizationModel->getAll(),
                    'csrfToken' => csrf_token(),
                ]);
            }

            // Create booking request with pending status
            $bookingId = $this->bookingModel->create([
                'user_id' => $user['id'],
                'org_id' => $orgId,
                'event_name' => $eventName,
                'event_date' => $eventDate,
                'venue' => $venue,
                'technical_needs' => $technicalNeeds,
                'expected_attendees' => $expectedAttendees,
                'additional_notes' => $additionalNotes,
                'status' => 'pending',
            ]);

            if (!$bookingId) {
                return view('bookings/booking-form', [
                    'error' => 'Failed to create booking request',
                    'organizations' => $this->organizationModel->getAll(),
                    'csrfToken' => csrf_token(),
                ]);
            }

            // FR-05: Log booking creation
            $this->auditLog->logBooking($user['id'], 'created', $bookingId, null, [
                'event_name' => $eventName,
                'org_id' => $orgId,
                'event_date' => $eventDate,
                'venue' => $venue,
            ]);

            redirect('/bookings/my-bookings?success=Booking created successfully');
        }
    }

    /**
     * View booking details
     */
    public function viewBooking($params = [])
    {
        $user = get_user();
        $bookingId = $params['id'] ?? null;

        if (!$bookingId) {
            http_response_code(404);
            return 'Booking not found';
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking) {
            http_response_code(404);
            return 'Booking not found';
        }

        // Verify user owns booking or is org admin for this org
        $isOwner = $booking['user_id'] === $user['id'];
        $userRole = $this->userModel->getRole($user['id']);
        $isOrgAdmin = $userRole['name'] === 'org_admin' && $booking['org_id'] === $user['org_id'];

        if (!$isOwner && !$isOrgAdmin) {
            http_response_code(403);
            return 'Unauthorized to view this booking';
        }

        $organization = $this->organizationModel->getById($booking['org_id']);
        $organizer = $this->userModel->getById($booking['user_id']);

        return view('bookings/booking-detail', [
            'booking' => $booking,
            'organization' => $organization,
            'organizer' => $organizer,
            'isOwner' => $isOwner,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * Edit booking request (FR-05: Dynamic Booking & Coordination)
     */
    public function editBooking($params = [])
    {
        $user = get_user();
        $bookingId = $params['id'] ?? null;

        if (!$bookingId) {
            http_response_code(404);
            return 'Booking not found';
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking) {
            http_response_code(404);
            return 'Booking not found';
        }

        // Only owner can edit their booking
        if ($booking['user_id'] !== $user['id']) {
            http_response_code(403);
            return 'Unauthorized to edit this booking';
        }

        // Can only edit pending bookings
        if ($booking['status'] !== 'pending') {
            return view('bookings/booking-detail', [
                'booking' => $booking,
                'error' => 'Can only edit pending booking requests',
                'csrfToken' => csrf_token(),
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $organizations = $this->organizationModel->getAll();
            return view('bookings/booking-form', [
                'booking' => $booking,
                'organizations' => $organizations,
                'csrfToken' => csrf_token(),
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $eventName = $_POST['event_name'] ?? $booking['event_name'];
            $eventDate = $_POST['event_date'] ?? $booking['event_date'];
            $venue = $_POST['venue'] ?? $booking['venue'];
            $technicalNeeds = $_POST['technical_needs'] ?? $booking['technical_needs'];
            $expectedAttendees = $_POST['expected_attendees'] ?? $booking['expected_attendees'];
            $additionalNotes = $_POST['additional_notes'] ?? $booking['additional_notes'];

            if (!$eventName || !$eventDate || !$venue) {
                return view('bookings/booking-form', [
                    'booking' => $booking,
                    'error' => 'All required fields must be filled',
                    'organizations' => $this->organizationModel->getAll(),
                    'csrfToken' => csrf_token(),
                ]);
            }

            $updated = $this->bookingModel->update($bookingId, [
                'event_name' => $eventName,
                'event_date' => $eventDate,
                'venue' => $venue,
                'technical_needs' => $technicalNeeds,
                'expected_attendees' => $expectedAttendees,
                'additional_notes' => $additionalNotes,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$updated) {
                return view('bookings/booking-form', [
                    'booking' => $booking,
                    'error' => 'Failed to update booking',
                    'organizations' => $this->organizationModel->getAll(),
                    'csrfToken' => csrf_token(),
                ]);
            }

            // FR-05: Log booking update
            $this->auditLog->logBooking($user['id'], 'updated', $bookingId, $booking, [
                'event_name' => $eventName,
                'event_date' => $eventDate,
                'venue' => $venue,
            ]);

            redirect('/bookings/view/' . $bookingId . '?success=Booking updated successfully');
        }
    }

    /**
     * Delete booking request (FR-05: Dynamic Booking & Coordination)
     */
    public function deleteBooking($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $user = get_user();
        $bookingId = $_POST['booking_id'] ?? null;

        if (!$bookingId) {
            return ['error' => 'Booking ID required'];
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        // Only owner can delete their booking
        if ($booking['user_id'] !== $user['id']) {
            return ['error' => 'Unauthorized'];
        }

        // Can only delete pending bookings
        if ($booking['status'] !== 'pending') {
            return ['error' => 'Can only delete pending booking requests'];
        }

        $deleted = $this->bookingModel->delete($bookingId);

        if (!$deleted) {
            return ['error' => 'Failed to delete booking'];
        }

        // FR-05: Log booking deletion
        $this->auditLog->logBooking($user['id'], 'deleted', $bookingId, $booking, [
            'status' => 'deleted',
        ]);

        return ['success' => 'Booking deleted'];
    }

    /**
     * Search available organizations (for booking form)
     */
    public function searchOrganizations($params = [])
    {
        $query = $_GET['q'] ?? '';

        if (strlen($query) < 2) {
            return ['organizations' => []];
        }

        $organizations = $this->organizationModel->getAll();
        $filtered = array_filter($organizations, function($org) use ($query) {
            return stripos($org['name'], $query) !== false || 
                   stripos($org['description'], $query) !== false;
        });

        return ['organizations' => array_values($filtered)];
    }

    /**
     * API: Get all organizations for talent directory
     */
    public function apiGetDirectoryOrganizations($params = [])
    {
        header('Content-Type: application/json');
        
        try {
            $organizations = $this->organizationModel->getAllWithDetails();
            
            // Return JSON response
            echo json_encode([
                'success' => true,
                'data' => $organizations
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching organizations'
            ]);
        }
        exit;
    }

    /**
     * API: Get all events for calendar (from accepted bookings)
     */
    public function apiGetCalendarEvents($params = [])
    {
        header('Content-Type: application/json');
        
        try {
            $organizations = $this->organizationModel->getAll();
            $events = [];

            foreach ($organizations as $org) {
                $bookings = $this->bookingModel->getByOrganization($org['id']);
                
                // Only include accepted bookings
                $acceptedBookings = array_filter($bookings, function($b) {
                    return $b['status'] === 'accepted';
                });

                foreach ($acceptedBookings as $booking) {
                    $events[] = [
                        'id' => 'booking_' . $booking['id'],
                        'title' => $booking['event_name'],
                        'start' => $booking['event_date'] . 'T00:00:00',
                        'backgroundColor' => '#DC2626', // Red
                        'borderColor' => '#991B1B',
                        'extendedProps' => [
                            'event_name' => $booking['event_name'],
                            'event_date' => $booking['event_date'],
                            'venue' => $booking['venue'],
                            'organization_id' => $org['id'],
                            'organization_name' => $org['name'],
                            'technical_needs' => $booking['technical_needs'] ?? null,
                        ]
                    ];
                }
            }

            // Sort by date
            usort($events, function($a, $b) {
                return strtotime($a['start']) - strtotime($b['start']);
            });

            echo json_encode([
                'success' => true,
                'data' => $events
            ]);
        } catch (\Exception $e) {
            error_log('Calendar events API error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching calendar events',
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}
