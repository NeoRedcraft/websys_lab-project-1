<?php

namespace App\Controllers;

use App\Middleware\Gatekeeper;
use App\Models\User;
use App\Models\Organization;
use App\Models\BookingRequest;
use App\Models\AuditLog;

class OrgAdminController
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

        // Require org_admin role
        $this->gatekeeper->requireRole('org_admin');
    }

    /**
     * Organization Admin Dashboard
     */
    public function dashboard($params = [])
    {
        require_auth();
        $user = get_user();
        
        // Get org_id from users_extended table
        $userRecord = $this->userModel->getById($user['id']);
        $orgId = $userRecord['org_id'] ?? null;
        
        if (!$orgId) {
            http_response_code(403);
            return view('error/403', ['message' => 'No organization assigned to this user']);
        }

        $organization = $this->organizationModel->getById($orgId);
        $bookingRequests = $this->bookingModel->getByOrganization($orgId);
        $stats = $this->bookingModel->getStats($orgId);

        $today = strtotime(date('Y-m-d'));
        $upcomingAcceptedEvents = [];
        $completedAcceptedEvents = [];

        foreach ($bookingRequests as $booking) {
            $status = strtolower((string) ($booking['status'] ?? ''));
            if ($status !== 'accepted') {
                continue;
            }

            $eventDate = strtotime((string) ($booking['event_date'] ?? ''));
            if ($eventDate === false) {
                continue;
            }

            if ($eventDate < $today) {
                $completedAcceptedEvents[] = $booking;
            } else {
                $upcomingAcceptedEvents[] = $booking;
            }
        }

        usort($upcomingAcceptedEvents, function ($a, $b) {
            return strtotime((string) ($a['event_date'] ?? '')) <=> strtotime((string) ($b['event_date'] ?? ''));
        });

        usort($completedAcceptedEvents, function ($a, $b) {
            return strtotime((string) ($b['event_date'] ?? '')) <=> strtotime((string) ($a['event_date'] ?? ''));
        });

        return view('pages/org-admin-dashboard', [
            'user' => $user,
            'organization' => $organization,
            'bookingRequests' => $bookingRequests,
            'stats' => $stats,
            'upcomingAcceptedEvents' => $upcomingAcceptedEvents,
            'completedAcceptedEvents' => $completedAcceptedEvents,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * View organization profile
     */
    public function viewProfile($params = [])
    {
        $user = get_user();
        $userRecord = $this->userModel->getById($user['id']);
        $orgId = $userRecord['org_id'] ?? null;

        if (!$orgId) {
            http_response_code(403);
            return view('error/403', ['message' => 'No organization assigned to this user']);
        }

        $organization = $this->organizationModel->getById($orgId);

        if (!$organization) {
            http_response_code(404);
            return 'Organization not found';
        }

        return view('org/profile-view', [
            'organization' => $organization,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * Edit organization profile (FR-04: Org Profile Customization)
     */
    public function editProfile($params = [])
    {
        $user = get_user();
        $userRecord = $this->userModel->getById($user['id']);
        $orgId = $userRecord['org_id'] ?? null;

        if (!$orgId) {
            http_response_code(403);
            return view('error/403', ['message' => 'No organization assigned to this user']);
        }

        $organization = $this->organizationModel->getById($orgId);
        if (!$organization) {
            http_response_code(404);
            return 'Organization not found';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return view('org/profile-form', [
                'organization' => $organization,
                'csrfToken' => csrf_token(),
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bio = $_POST['bio'] ?? $organization['bio'];
            $genre = $_POST['genre'] ?? $organization['genre'];
            $technicalRequirements = $_POST['technical_requirements'] ?? $organization['technical_requirements'];
            $youtubeLinks = $_POST['youtube_links'] ?? $organization['youtube_links'];

            $updateData = [
                'bio' => $bio,
                'genre' => $genre,
                'technical_requirements' => $technicalRequirements,
                'youtube_links' => $youtubeLinks,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if (!empty($_FILES['image']['name'])) {
                try {
                    $imageUrl = $this->organizationModel->uploadImage($orgId, $_FILES['image']);
                    if ($imageUrl) {
                        $updateData['image_url'] = $imageUrl;
                    }
                } catch (\Exception $e) {
                    return view('org/profile-form', [
                        'organization' => $organization,
                        'error' => $e->getMessage(),
                        'csrfToken' => csrf_token(),
                    ]);
                }
            }

            $updated = $this->organizationModel->update($orgId, $updateData);

            if (!$updated) {
                return view('org/profile-form', [
                    'organization' => $organization,
                    'error' => 'Failed to update profile',
                    'csrfToken' => csrf_token(),
                ]);
            }

            // Log profile update
            $this->auditLog->logOrganization($user['id'], 'profile_updated', $orgId, $organization, [
                'bio' => $bio,
                'genre' => $genre,
                'technical_requirements' => $technicalRequirements,
            ]);

            return view('org/profile-form', [
                'organization' => array_merge($organization, [
                    'bio' => $bio,
                    'genre' => $genre,
                    'technical_requirements' => $technicalRequirements,
                    'youtube_links' => $youtubeLinks,
                    'image_url' => $updateData['image_url'] ?? ($organization['image_url'] ?? null),
                ]),
                'success' => 'Profile updated successfully',
                'csrfToken' => csrf_token(),
            ]);
        }
    }

    /**
     * List incoming booking requests (Inbox)
     */
    public function inboxBookings($params = [])
    {
        $user = get_user();
        $userRecord = $this->userModel->getById($user['id']);
        $orgId = $userRecord['org_id'] ?? null;

        if (!$orgId) {
            http_response_code(403);
            return view('error/403', ['message' => 'No organization assigned to this user']);
        }

        $bookingRequests = $this->bookingModel->getByOrganization($orgId);

        return view('bookings/org-booking-inbox', [
            'bookingRequests' => $bookingRequests,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * View booking request details
     */
    public function viewBooking($params = [])
    {
        $user = get_user();
        $userRecord = $this->userModel->getById($user['id']);
        $orgId = $userRecord['org_id'] ?? null;
        $bookingId = $params['id'] ?? null;

        if (!$bookingId) {
            http_response_code(404);
            return 'Booking not found';
        }

        if (!$orgId) {
            http_response_code(403);
            return 'No organization assigned';
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking || (int) ($booking['organization_id'] ?? 0) !== (int) $orgId) {
            http_response_code(403);
            return 'Unauthorized to view this booking';
        }

        $organizer = $this->userModel->getById($booking['organizer_id']);

        return view('bookings/org-booking-detail', [
            'booking' => $booking,
            'organizer' => $organizer,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * Accept booking request (FR-05: Booking Workflow)
     */
    public function acceptBooking($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $user = get_user();
        $userRecord = $this->userModel->getById($user['id']);
        $orgId = $userRecord['org_id'] ?? null;
        $bookingId = $_POST['booking_id'] ?? null;
        $notes = $_POST['notes'] ?? '';
        $accessToken = session_get('access_token');

        if (!$orgId) {
            redirect('/org-admin/bookings?error=' . urlencode('No organization assigned'));
        }

        if (!$bookingId) {
            redirect('/org-admin/bookings?error=' . urlencode('Booking ID required'));
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking || (int) ($booking['organization_id'] ?? 0) !== (int) $orgId) {
            redirect('/org-admin/bookings?error=' . urlencode('Unauthorized'));
        }

        // Verify booking is in pending status
        if ($booking['status'] !== 'pending') {
            redirect('/org-admin/bookings?error=' . urlencode('Booking already processed'));
        }

        $updated = $this->bookingModel->accept($bookingId, $notes, $accessToken);

        if (!$updated) {
            redirect('/org-admin/bookings?error=' . urlencode('Failed to accept booking'));
        }

        // FR-05: Log booking acceptance
        $this->auditLog->logBooking($user['id'], 'accepted', $bookingId, $booking, [
            'status' => 'accepted',
            'notes' => $notes,
        ]);

        redirect('/org-admin/bookings?success=' . urlencode('Booking accepted'));
    }

    /**
     * Decline booking request (FR-05: Booking Workflow)
     */
    public function declineBooking($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $user = get_user();
        $userRecord = $this->userModel->getById($user['id']);
        $orgId = $userRecord['org_id'] ?? null;
        $bookingId = $_POST['booking_id'] ?? null;
        $reason = $_POST['reason'] ?? '';
        $accessToken = session_get('access_token');

        if (!$orgId) {
            redirect('/org-admin/bookings?error=' . urlencode('No organization assigned'));
        }

        if (!$bookingId) {
            redirect('/org-admin/bookings?error=' . urlencode('Booking ID required'));
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking || (int) ($booking['organization_id'] ?? 0) !== (int) $orgId) {
            redirect('/org-admin/bookings?error=' . urlencode('Unauthorized'));
        }

        // Verify booking is in pending status
        if ($booking['status'] !== 'pending') {
            redirect('/org-admin/bookings?error=' . urlencode('Booking already processed'));
        }

        $updated = $this->bookingModel->decline($bookingId, $reason, $accessToken);

        if (!$updated) {
            redirect('/org-admin/bookings?error=' . urlencode('Failed to decline booking'));
        }

        // FR-05: Log booking declination
        $this->auditLog->logBooking($user['id'], 'declined', $bookingId, $booking, [
            'status' => 'declined',
            'reason' => $reason,
        ]);

        redirect('/org-admin/bookings?success=' . urlencode('Booking declined'));
    }

    /**
     * View statistics for org admin
     */
    public function statistics($params = [])
    {
        $user = get_user();
        $userRecord = $this->userModel->getById($user['id']);
        $orgId = $userRecord['org_id'] ?? null;

        if (!$orgId) {
            http_response_code(403);
            return view('error/403', ['message' => 'No organization assigned to this user']);
        }

        $stats = $this->bookingModel->getStats($orgId);

        return view('org/statistics', [
            'stats' => $stats,
            'csrfToken' => csrf_token(),
        ]);
    }
}
