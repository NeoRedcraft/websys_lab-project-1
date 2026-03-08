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
        $role = $this->userModel->getRole($user['id']);
        $roleName = $role['name'] ?? null;

        if ($roleName === 'org_admin') {
            redirect('/org-admin/bookings');
        }

        if ($roleName === 'system_admin') {
            $allBookings = $this->bookingModel->getAll();
            $organizations = $this->organizationModel->getAll();

            $organizationMap = [];
            foreach ($organizations as $organization) {
                $organizationMap[(string) ($organization['id'] ?? '')] = $organization;
            }

            usort($allBookings, function ($a, $b) {
                return strtotime($a['event_date'] ?? '') <=> strtotime($b['event_date'] ?? '');
            });

            $bookingsByOrg = [];
            foreach ($allBookings as $booking) {
                $organizationId = (string) ($booking['organization_id'] ?? $booking['org_id'] ?? 'unknown');
                if (!isset($bookingsByOrg[$organizationId])) {
                    $bookingsByOrg[$organizationId] = [
                        'organization' => $organizationMap[$organizationId] ?? [
                            'id' => $organizationId,
                            'name' => 'Unknown Organization',
                        ],
                        'bookings' => [],
                    ];
                }

                $bookingsByOrg[$organizationId]['bookings'][] = $booking;
            }

            uasort($bookingsByOrg, function ($left, $right) {
                return strcasecmp($left['organization']['name'] ?? '', $right['organization']['name'] ?? '');
            });

            return view('admin/bookings-list', [
                'bookingsByOrg' => $bookingsByOrg,
                'csrfToken' => csrf_token(),
            ]);
        }

        $bookings = $this->bookingModel->getByOrganizer($user['id']);
        $bookings = array_values(array_filter($bookings, function ($booking) use ($user) {
            $ownerId = $booking['organizer_id'] ?? $booking['user_id'] ?? null;
            return (string) $ownerId === (string) ($user['id'] ?? '');
        }));

        foreach ($bookings as &$booking) {
            $organizationId = $booking['organization_id'] ?? $booking['org_id'] ?? null;
            if ($organizationId) {
                $organization = $this->organizationModel->getById($organizationId);
                $booking['org_name'] = $organization['name'] ?? ($booking['org_name'] ?? '');
            }
        }

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
            $selectedOrgId = $_GET['org_id'] ?? null;

            return view('bookings/booking-form', [
                'booking' => null,
                'organizations' => $organizations,
                'selectedOrgId' => $selectedOrgId,
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
            $engageEventLink = trim((string) ($_POST['engage_event_link'] ?? ''));
            $accessToken = session_get('access_token');

            // Validate required fields
            if (!$orgId || !$eventName || !$eventDate || !$venue || !$engageEventLink) {
                $organizations = $this->organizationModel->getAll();
                return view('bookings/booking-form', [
                    'error' => 'All required fields must be filled, including Engage event link',
                    'booking' => [
                        'organization_id' => $orgId,
                        'event_name' => $eventName,
                        'event_date' => $eventDate,
                        'venue' => $venue,
                        'technical_needs' => $technicalNeeds,
                        'engage_event_link' => $engageEventLink,
                    ],
                    'organizations' => $organizations,
                    'csrfToken' => csrf_token(),
                ]);
            }

            if (!filter_var($engageEventLink, FILTER_VALIDATE_URL)) {
                $organizations = $this->organizationModel->getAll();
                return view('bookings/booking-form', [
                    'error' => 'Please provide a valid Engage event URL',
                    'booking' => [
                        'organization_id' => $orgId,
                        'event_name' => $eventName,
                        'event_date' => $eventDate,
                        'venue' => $venue,
                        'technical_needs' => $technicalNeeds,
                        'engage_event_link' => $engageEventLink,
                    ],
                    'organizations' => $organizations,
                    'csrfToken' => csrf_token(),
                ]);
            }

            // Verify organization exists
            $org = $this->organizationModel->getById($orgId);
            if (!$org) {
                return view('bookings/booking-form', [
                    'error' => 'Invalid organization selected',
                    'booking' => [
                        'organization_id' => $orgId,
                        'event_name' => $eventName,
                        'event_date' => $eventDate,
                        'venue' => $venue,
                        'technical_needs' => $technicalNeeds,
                        'engage_event_link' => $engageEventLink,
                    ],
                    'organizations' => $this->organizationModel->getAll(),
                    'csrfToken' => csrf_token(),
                ]);
            }

            [$invitationPdfUrl, $uploadError] = $this->processInvitationUpload();
            if ($uploadError) {
                return view('bookings/booking-form', [
                    'error' => $uploadError,
                    'booking' => [
                        'organization_id' => $orgId,
                        'event_name' => $eventName,
                        'event_date' => $eventDate,
                        'venue' => $venue,
                        'technical_needs' => $technicalNeeds,
                        'engage_event_link' => $engageEventLink,
                    ],
                    'organizations' => $this->organizationModel->getAll(),
                    'csrfToken' => csrf_token(),
                ]);
            }

            if (!$invitationPdfUrl) {
                return view('bookings/booking-form', [
                    'error' => 'Invitation PDF is required',
                    'booking' => [
                        'organization_id' => $orgId,
                        'event_name' => $eventName,
                        'event_date' => $eventDate,
                        'venue' => $venue,
                        'technical_needs' => $technicalNeeds,
                        'engage_event_link' => $engageEventLink,
                    ],
                    'organizations' => $this->organizationModel->getAll(),
                    'csrfToken' => csrf_token(),
                ]);
            }

            // Create booking request with pending status
            $bookingId = $this->bookingModel->create([
                'organizer_id' => $user['id'],
                'organization_id' => $orgId,
                'event_name' => $eventName,
                'event_date' => $eventDate,
                'venue' => $venue,
                'engage_event_link' => $engageEventLink,
                'invitation_pdf_url' => $invitationPdfUrl,
                'technical_needs' => $technicalNeeds,
                'status' => 'pending',
            ], $accessToken);

            if (!$bookingId) {
                return view('bookings/booking-form', [
                    'error' => $this->bookingModel->getLastError() ?: 'Failed to create booking request',
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
                'engage_event_link' => $engageEventLink,
                'invitation_pdf_url' => $invitationPdfUrl,
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

        // Verify user owns booking, is org admin for this org, or is system admin
        $bookingOwnerId = $booking['organizer_id'] ?? $booking['user_id'] ?? null;
        $bookingOrgId = $booking['organization_id'] ?? $booking['org_id'] ?? null;
        $isOwner = (string) $bookingOwnerId === (string) ($user['id'] ?? '');
        $userRole = $this->userModel->getRole($user['id']);
        $isSystemAdmin = ($userRole['name'] ?? '') === 'system_admin';
        $userRecord = $this->userModel->getById($user['id']);
        $userOrgId = $userRecord['org_id'] ?? null;
        $isOrgAdmin = ($userRole['name'] ?? '') === 'org_admin' && (string) $bookingOrgId === (string) $userOrgId;

        if (!$isOwner && !$isOrgAdmin && !$isSystemAdmin) {
            http_response_code(403);
            return 'Unauthorized to view this booking';
        }

        $organization = $this->organizationModel->getById($bookingOrgId);
        $organizer = $this->userModel->getById($bookingOwnerId);

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
        $bookingOwnerId = $booking['organizer_id'] ?? $booking['user_id'] ?? null;
        if ($bookingOwnerId !== $user['id']) {
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
            $engageEventLink = trim((string) ($_POST['engage_event_link'] ?? ($booking['engage_event_link'] ?? '')));
            $accessToken = session_get('access_token');

            if (!$eventName || !$eventDate || !$venue || !$engageEventLink) {
                return view('bookings/booking-form', [
                    'booking' => $booking,
                    'error' => 'All required fields must be filled, including Engage event link',
                    'organizations' => $this->organizationModel->getAll(),
                    'csrfToken' => csrf_token(),
                ]);
            }

            if (!filter_var($engageEventLink, FILTER_VALIDATE_URL)) {
                return view('bookings/booking-form', [
                    'booking' => $booking,
                    'error' => 'Please provide a valid Engage event URL',
                    'organizations' => $this->organizationModel->getAll(),
                    'csrfToken' => csrf_token(),
                ]);
            }

            [$invitationPdfUrl, $uploadError] = $this->processInvitationUpload($booking['invitation_pdf_url'] ?? null);
            if ($uploadError) {
                return view('bookings/booking-form', [
                    'booking' => $booking,
                    'error' => $uploadError,
                    'organizations' => $this->organizationModel->getAll(),
                    'csrfToken' => csrf_token(),
                ]);
            }

            $updated = $this->bookingModel->update($bookingId, [
                'event_name' => $eventName,
                'event_date' => $eventDate,
                'venue' => $venue,
                'engage_event_link' => $engageEventLink,
                'invitation_pdf_url' => $invitationPdfUrl,
                'technical_needs' => $technicalNeeds,
                'updated_at' => date('Y-m-d H:i:s'),
            ], $accessToken);

            if (!$updated) {
                return view('bookings/booking-form', [
                    'booking' => $booking,
                    'error' => $this->bookingModel->getLastError() ?: 'Failed to update booking',
                    'organizations' => $this->organizationModel->getAll(),
                    'csrfToken' => csrf_token(),
                ]);
            }

            // FR-05: Log booking update
            $this->auditLog->logBooking($user['id'], 'updated', $bookingId, $booking, [
                'event_name' => $eventName,
                'event_date' => $eventDate,
                'venue' => $venue,
                'engage_event_link' => $engageEventLink,
                'invitation_pdf_url' => $invitationPdfUrl,
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
        $bookingOwnerId = $booking['organizer_id'] ?? $booking['user_id'] ?? null;
        if ($bookingOwnerId !== $user['id']) {
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

    private function processInvitationUpload($existingUrl = null)
    {
        $file = $_FILES['invitation_pdf'] ?? null;

        if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return [$existingUrl, null];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [$existingUrl, 'Failed to upload invitation PDF'];
        }

        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            return [$existingUrl, 'Invitation PDF must be 5MB or less'];
        }

        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']);
        $fileHandle = @fopen($file['tmp_name'], 'rb');
        $signature = $fileHandle ? fread($fileHandle, 5) : '';
        if ($fileHandle) {
            fclose($fileHandle);
        }

        $isPdfMime = in_array($mime, ['application/pdf', 'application/x-pdf'], true);
        $hasPdfSignature = $signature === '%PDF-';

        if ($extension !== 'pdf' || !$isPdfMime || !$hasPdfSignature) {
            return [$existingUrl, 'Invitation file must be a valid PDF'];
        }

        $uploadDir = base_path('uploads/invitations');
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return [$existingUrl, 'Failed to create invitations upload directory'];
        }

        $targetFile = $uploadDir . DIRECTORY_SEPARATOR . 'invitation_' . uniqid() . '.pdf';
        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            return [$existingUrl, 'Failed to save invitation PDF'];
        }

        return ['/uploads/invitations/' . basename($targetFile), null];
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
            $user = get_user();
            $userRole = $this->userModel->getRole($user['id']);
            $roleName = $userRole['name'] ?? null;

            if ($roleName === 'org_admin') {
                $userRecord = $this->userModel->getById($user['id']);
                $orgId = $userRecord['org_id'] ?? null;
                $organizations = $orgId ? [$this->organizationModel->getById($orgId)] : [];
            } else {
                $organizations = $this->organizationModel->getAll();
            }

            $events = [];

            foreach ($organizations as $org) {
                if (!$org || empty($org['id'])) {
                    continue;
                }

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
