<?php

namespace App\Controllers;

use App\Middleware\Gatekeeper;
use App\Models\User;
use App\Models\Organization;
use App\Models\BookingRequest;
use App\Models\AuditLog;
use App\Utils\Supabase;

class BookingController
{
    private $gatekeeper;
    private $userModel;
    private $organizationModel;
    private $bookingModel;
    private $auditLog;
    private $supabase;

    public function __construct()
    {
        $this->gatekeeper = new Gatekeeper();
        $this->userModel = new User();
        $this->organizationModel = new Organization();
        $this->bookingModel = new BookingRequest();
        $this->auditLog = new AuditLog();
        $this->supabase = null;

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
            $userRecord = $this->userModel->getById($user['id']);
            $orgId = $_POST['org_id'] ?? null;
            $eventName = $_POST['event_name'] ?? '';
            $eventDate = $_POST['event_date'] ?? '';
            $venue = $_POST['venue'] ?? '';
            $technicalNeeds = $_POST['technical_needs'] ?? '';
            $engageEventLink = trim((string) ($_POST['engage_event_link'] ?? ''));
            $invitationPdfUrl = trim((string) ($_POST['invitation_pdf_url'] ?? ''));
            $accessToken = session_get('access_token');
            $organizerName = trim((string) ($userRecord['full_name'] ?? get_display_name($user)));
            $organizerEmail = trim((string) ($userRecord['email'] ?? ($user['email'] ?? '')));

            // Validate required fields
            if (!$orgId || !$eventName || !$eventDate || !$venue || !$engageEventLink || !$invitationPdfUrl) {
                $organizations = $this->organizationModel->getAll();
                return view('bookings/booking-form', [
                    'error' => 'All required fields must be filled, including Engage event link and invitation URL',
                    'booking' => [
                        'organization_id' => $orgId,
                        'event_name' => $eventName,
                        'event_date' => $eventDate,
                        'venue' => $venue,
                        'technical_needs' => $technicalNeeds,
                        'engage_event_link' => $engageEventLink,
                        'invitation_pdf_url' => $invitationPdfUrl,
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
                        'invitation_pdf_url' => $invitationPdfUrl,
                    ],
                    'organizations' => $organizations,
                    'csrfToken' => csrf_token(),
                ]);
            }

            if (!filter_var($invitationPdfUrl, FILTER_VALIDATE_URL) || !$this->isAllowedInvitationUrl($invitationPdfUrl)) {
                $organizations = $this->organizationModel->getAll();
                return view('bookings/booking-form', [
                    'error' => 'Invitation URL must be a Google Drive or OneDrive link',
                    'booking' => [
                        'organization_id' => $orgId,
                        'event_name' => $eventName,
                        'event_date' => $eventDate,
                        'venue' => $venue,
                        'technical_needs' => $technicalNeeds,
                        'engage_event_link' => $engageEventLink,
                        'invitation_pdf_url' => $invitationPdfUrl,
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

            // Create booking request with pending status
            $bookingId = $this->bookingModel->create([
                'organizer_id' => $user['id'],
                'organizer_name' => $organizerName,
                'organizer_email' => $organizerEmail,
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
                if ($invitationPdfUrl) {
                    $this->deleteInvitationAsset($invitationPdfUrl);
                }

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
                'organizer_name' => $organizerName,
                'organizer_email' => $organizerEmail,
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
            $invitationPdfUrl = trim((string) ($_POST['invitation_pdf_url'] ?? ($booking['invitation_pdf_url'] ?? '')));
            $accessToken = session_get('access_token');

            if (!$eventName || !$eventDate || !$venue || !$engageEventLink || !$invitationPdfUrl) {
                return view('bookings/booking-form', [
                    'booking' => $booking,
                    'error' => 'All required fields must be filled, including Engage event link and invitation URL',
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

            if (!filter_var($invitationPdfUrl, FILTER_VALIDATE_URL) || !$this->isAllowedInvitationUrl($invitationPdfUrl)) {
                return view('bookings/booking-form', [
                    'booking' => $booking,
                    'error' => 'Invitation URL must be a Google Drive or OneDrive link',
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
        $accessToken = session_get('access_token');
        $userRole = $this->userModel->getRole($user['id']);
        $isSystemAdmin = ($userRole['name'] ?? '') === 'system_admin';
        $redirectPath = '/bookings';

        if (!$bookingId) {
            redirect($redirectPath . '?error=' . urlencode('Booking ID required'));
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking) {
            redirect($redirectPath . '?error=' . urlencode('Booking not found'));
        }

        // Only owner can delete their booking
        $bookingOwnerId = $booking['organizer_id'] ?? $booking['user_id'] ?? null;
        if (!$isSystemAdmin && $bookingOwnerId !== $user['id']) {
            redirect($redirectPath . '?error=' . urlencode('Unauthorized'));
        }

        // Can only delete pending bookings
        if (!$isSystemAdmin && $booking['status'] !== 'pending') {
            redirect($redirectPath . '?error=' . urlencode('Can only delete pending booking requests'));
        }

        $deleted = $this->bookingModel->delete($bookingId, $accessToken, $isSystemAdmin);

        if (!$deleted) {
            redirect($redirectPath . '?error=' . urlencode('Failed to delete booking'));
        }

        $invitationPdfUrl = $booking['invitation_pdf_url'] ?? null;
        if (!empty($invitationPdfUrl)) {
            $this->deleteInvitationAsset($invitationPdfUrl);
        }

        // FR-05: Log booking deletion
        $this->auditLog->logBooking($user['id'], 'deleted', $bookingId, $booking, [
            'status' => 'deleted',
        ]);

        redirect($redirectPath . '?success=' . urlencode('Booking deleted'));
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

        $storageDriver = strtolower((string) env('INVITATION_STORAGE_DRIVER', 'supabase'));

        if ($storageDriver === 'supabase') {
            try {
                $supabase = $this->getSupabaseClient();
                if (!$supabase) {
                    return [$existingUrl, 'Supabase is not configured for invitation uploads'];
                }

                $bucket = trim((string) env('SUPABASE_INVITATIONS_BUCKET', 'invitations'));
                $prefix = trim((string) env('SUPABASE_INVITATIONS_PREFIX', 'booking-invitations'), '/');
                $objectPath = ($prefix !== '' ? ($prefix . '/') : '')
                    . date('Y/m')
                    . '/invitation_' . str_replace('.', '', uniqid('', true)) . '.pdf';

                $fileContent = @file_get_contents($file['tmp_name']);
                if ($fileContent === false) {
                    return [$existingUrl, 'Failed to read invitation PDF'];
                }

                $uploadedUrl = $supabase->uploadFile($bucket, $objectPath, $fileContent, 'application/pdf');
                return [$uploadedUrl, null];
            } catch (\Exception $e) {
                error_log('Invitation upload to Supabase failed: ' . $e->getMessage());
                return [$existingUrl, 'Failed to upload invitation PDF to Supabase'];
            }
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

    private function deleteInvitationAsset($invitationPdfUrl)
    {
        if (!is_string($invitationPdfUrl) || $invitationPdfUrl === '') {
            return false;
        }

        $supabase = $this->getSupabaseClient();
        if ($supabase) {
            $storageObject = $supabase->parseStorageObjectFromUrl($invitationPdfUrl, env('SUPABASE_INVITATIONS_BUCKET', 'invitations'));
            if ($storageObject && !empty($storageObject['bucket']) && !empty($storageObject['path'])) {
                return $supabase->deleteStorageObject($storageObject['bucket'], $storageObject['path']);
            }
        }

        $path = parse_url($invitationPdfUrl, PHP_URL_PATH);
        if (!is_string($path) || strpos($path, '/uploads/invitations/') !== 0) {
            return false;
        }

        $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/'));
        $absolutePath = base_path($normalizedPath);

        if (!is_file($absolutePath)) {
            return false;
        }

        return @unlink($absolutePath);
    }

    private function getSupabaseClient()
    {
        if ($this->supabase !== null) {
            return $this->supabase;
        }

        try {
            $this->supabase = Supabase::getInstance();
        } catch (\Exception $e) {
            error_log('Supabase client unavailable: ' . $e->getMessage());
            $this->supabase = false;
        }

        return $this->supabase ?: null;
    }

    private function isAllowedInvitationUrl($url)
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        $allowedHosts = [
            'drive.google.com',
            'docs.google.com',
            'onedrive.live.com',
            '1drv.ms',
        ];

        foreach ($allowedHosts as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                return true;
            }
        }

        return false;
    }

    /**
     * API: Get all organizations for talent directory
     */
    public function apiGetDirectoryOrganizations($params = [])
    {
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=60, s-maxage=120');
        
        try {
            $organizations = $this->organizationModel->getDirectoryListings();
            
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

        if (!auth_check()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required to view calendar events',
            ]);
            exit;
        }
        
        try {
            $organizations = $this->organizationModel->getAll();
            $organizationMap = [];
            foreach ($organizations as $org) {
                if (!empty($org['id'])) {
                    $organizationMap[(string) $org['id']] = $org;
                }
            }

            $bookings = $this->bookingModel->getUpcomingAcceptedForCalendar();

            $events = [];

            foreach ($bookings as $booking) {
                $orgId = (string) ($booking['organization_id'] ?? '');
                $org = $organizationMap[$orgId] ?? null;

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
                        'organization_id' => $booking['organization_id'],
                        'organization_name' => $org['name'] ?? ('Organization #' . $orgId),
                        'technical_needs' => $booking['technical_needs'] ?? null,
                    ]
                ];
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
