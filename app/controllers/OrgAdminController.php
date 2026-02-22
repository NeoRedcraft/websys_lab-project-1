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

        return view('pages/org-admin-dashboard', [
            'user' => $user,
            'organization' => $organization,
            'bookingRequests' => $bookingRequests,
            'stats' => $stats,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * View organization profile
     */
    public function viewProfile($params = [])
    {
        $user = get_user();
        $organization = $this->organizationModel->getById($user['org_id']);

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
        $orgId = $user['org_id'];

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

            $updated = $this->organizationModel->update($orgId, [
                'bio' => $bio,
                'genre' => $genre,
                'technical_requirements' => $technicalRequirements,
                'youtube_links' => $youtubeLinks,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

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
        $orgId = $user['org_id'];

        $bookingRequests = $this->bookingModel->getByOrganization($orgId);

        return view('org/booking-inbox', [
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
        $orgId = $user['org_id'];
        $bookingId = $params['id'] ?? null;

        if (!$bookingId) {
            http_response_code(404);
            return 'Booking not found';
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking || $booking['org_id'] !== $orgId) {
            http_response_code(403);
            return 'Unauthorized to view this booking';
        }

        $organizer = $this->userModel->getById($booking['user_id']);

        return view('org/booking-detail', [
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
        $orgId = $user['org_id'];
        $bookingId = $_POST['booking_id'] ?? null;
        $notes = $_POST['notes'] ?? '';

        if (!$bookingId) {
            return ['error' => 'Booking ID required'];
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking || $booking['org_id'] !== $orgId) {
            return ['error' => 'Unauthorized'];
        }

        // Verify booking is in pending status
        if ($booking['status'] !== 'pending') {
            return ['error' => 'Booking already processed'];
        }

        $updated = $this->bookingModel->accept($bookingId, $notes);

        if (!$updated) {
            return ['error' => 'Failed to accept booking'];
        }

        // FR-05: Log booking acceptance
        $this->auditLog->logBooking($user['id'], 'accepted', $bookingId, $booking, [
            'status' => 'accepted',
            'notes' => $notes,
        ]);

        return ['success' => 'Booking accepted'];
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
        $orgId = $user['org_id'];
        $bookingId = $_POST['booking_id'] ?? null;
        $reason = $_POST['reason'] ?? '';

        if (!$bookingId) {
            return ['error' => 'Booking ID required'];
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking || $booking['org_id'] !== $orgId) {
            return ['error' => 'Unauthorized'];
        }

        // Verify booking is in pending status
        if ($booking['status'] !== 'pending') {
            return ['error' => 'Booking already processed'];
        }

        $updated = $this->bookingModel->decline($bookingId, $reason);

        if (!$updated) {
            return ['error' => 'Failed to decline booking'];
        }

        // FR-05: Log booking declination
        $this->auditLog->logBooking($user['id'], 'declined', $bookingId, $booking, [
            'status' => 'declined',
            'reason' => $reason,
        ]);

        return ['success' => 'Booking declined'];
    }

    /**
     * View statistics for org admin
     */
    public function statistics($params = [])
    {
        $user = get_user();
        $orgId = $user['org_id'];

        $stats = $this->bookingModel->getStats($orgId);

        return view('org/statistics', [
            'stats' => $stats,
            'csrfToken' => csrf_token(),
        ]);
    }
}
