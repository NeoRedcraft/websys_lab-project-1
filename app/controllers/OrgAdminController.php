<?php

namespace App\Controllers;

use App\Middleware\Gatekeeper;
use App\Models\User;
use App\Models\Organization;
use App\Models\BookingRequest;
use App\Models\AuditLog;
use App\Utils\Supabase;

class OrgAdminController
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

        $reviewState = $this->getAdminProfileChangeReviewState((int) $orgId);

        return view('org/profile-view', [
            'organization' => $organization,
            'reviewState' => $reviewState,
            'success' => $_GET['success'] ?? null,
            'error' => $_GET['error'] ?? null,
            'success' => $_GET['success'] ?? null,
            'error' => $_GET['error'] ?? null,
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

        $reviewState = $this->getAdminProfileChangeReviewState((int) $orgId);
        $prefilledOrganization = $organization;
        if (!empty($reviewState['hasPending'])) {
            $pendingValues = $reviewState['latestAdminChange']['new_values_decoded'] ?? null;
            if (is_array($pendingValues)) {
                $allowedFields = ['name', 'bio', 'genre', 'technical_requirements', 'youtube_links', 'image_url'];
                foreach ($allowedFields as $field) {
                    if (array_key_exists($field, $pendingValues)) {
                        $prefilledOrganization[$field] = $pendingValues[$field];
                    }
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return view('org/profile-form', [
                'organization' => $prefilledOrganization,
                'reviewState' => $reviewState,
                'csrfToken' => csrf_token(),
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accessToken = session_get('access_token');
            if (!$accessToken) {
                return view('org/profile-form', [
                    'organization' => $organization,
                    'error' => 'Session expired. Please sign in again.',
                    'csrfToken' => csrf_token(),
                ]);
            }

            $name = $_POST['name'] ?? $organization['name'];
            $bio = $_POST['bio'] ?? $organization['bio'];
            $genre = $_POST['genre'] ?? $organization['genre'];
            $technicalRequirements = $_POST['technical_requirements'] ?? $organization['technical_requirements'];
            $youtubeLinks = $_POST['youtube_links'] ?? $organization['youtube_links'];

            $accessToken = $_SESSION['access_token'] ?? null;
            $updateData = [
                'name' => $name,
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

            $updated = $this->organizationModel->update($orgId, $updateData, $accessToken);

            if (!$updated) {
                return view('org/profile-form', [
                    'organization' => $organization,
                    'error' => 'Failed to update profile',
                    'csrfToken' => csrf_token(),
                ]);
            }

            // Log profile update
            $this->auditLog->logOrganization($user['id'], 'profile_updated', $orgId, $organization, [
                'name' => $name,
                'bio' => $bio,
                'genre' => $genre,
                'technical_requirements' => $technicalRequirements,
            ]);

            return redirect('/org-admin/profile?success=' . urlencode('Profile updated successfully'));
        }
    }

    /**
     * Delete organization profile (FR-04: Org Profile Customization)
     */
    public function deleteProfile($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $user = get_user();
        $userRecord = $this->userModel->getById($user['id']);
        $orgId = $userRecord['org_id'] ?? null;

        if (!$orgId) {
            http_response_code(403);
            return 'No organization assigned to this user';
        }

        $organization = $this->organizationModel->getById($orgId);
        if (!$organization) {
            http_response_code(404);
            return 'Organization not found';
        }

        $accessToken = $_SESSION['access_token'] ?? null;
        $deleted = $this->organizationModel->delete($orgId, $accessToken);

        if (!$deleted) {
            return view('org/profile-form', [
                'organization' => $organization,
                'error' => 'Failed to delete organization',
                'csrfToken' => csrf_token(),
            ]);
        }

        $this->auditLog->logOrganization($user['id'], 'deleted', $orgId, $organization, []);

        session_flush();
        redirect('/?success=' . urlencode('Organization deleted successfully'));
    }

    public function acceptAdminProfileChanges($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $user = get_user();
        $userRecord = $this->userModel->getById($user['id']);
        $orgId = $userRecord['org_id'] ?? null;

        if (!$orgId) {
            redirect('/org-admin/profile?error=' . urlencode('No organization assigned'));
        }

        $reviewState = $this->getAdminProfileChangeReviewState((int) $orgId);
        if (empty($reviewState['hasPending'])) {
            redirect('/org-admin/profile?error=' . urlencode('No pending admin profile changes to accept'));
        }

        $latestAdminChange = $reviewState['latestAdminChange'] ?? null;
        $this->auditLog->logOrganization($user['id'], 'admin_change_accepted', $orgId, null, [
            'source_action' => $latestAdminChange['action'] ?? 'updated',
            'source_created_at' => $latestAdminChange['created_at'] ?? null,
        ]);

        redirect('/org-admin/profile?success=' . urlencode('Admin profile changes accepted'));
    }

    public function declineAdminProfileChanges($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $user = get_user();
        $userRecord = $this->userModel->getById($user['id']);
        $orgId = $userRecord['org_id'] ?? null;

        if (!$orgId) {
            redirect('/org-admin/profile?error=' . urlencode('No organization assigned'));
        }

        $reviewState = $this->getAdminProfileChangeReviewState((int) $orgId);
        if (empty($reviewState['hasPending'])) {
            redirect('/org-admin/profile?error=' . urlencode('No pending admin profile changes to decline'));
        }

        $latestAdminChange = $reviewState['latestAdminChange'] ?? null;
        $oldValues = $this->decodeAuditPayload($latestAdminChange['old_values'] ?? null);
        if (!is_array($oldValues)) {
            redirect('/org-admin/profile?error=' . urlencode('Unable to restore previous organization details'));
        }

        $allowedFields = ['name', 'bio', 'genre', 'technical_requirements', 'youtube_links', 'image_url'];
        $revertData = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $oldValues)) {
                $revertData[$field] = $oldValues[$field];
            }
        }

        if (empty($revertData)) {
            redirect('/org-admin/profile?error=' . urlencode('No restorable profile fields were found'));
        }

        $updated = $this->organizationModel->update($orgId, $revertData, session_get('access_token'));
        if (!$updated) {
            redirect('/org-admin/profile?error=' . urlencode('Failed to decline admin profile changes'));
        }

        $this->auditLog->logOrganization($user['id'], 'admin_change_declined', $orgId, $latestAdminChange, [
            'reverted_fields' => array_keys($revertData),
            'source_created_at' => $latestAdminChange['created_at'] ?? null,
        ]);

        redirect('/org-admin/profile?success=' . urlencode('Admin profile changes declined and reverted'));
    }

    private function getAdminProfileChangeReviewState($orgId)
    {
        $logs = $this->auditLog->getByEntity('organization', $orgId);
        if (!is_array($logs) || empty($logs)) {
            return [
                'hasPending' => false,
                'latestAdminChange' => null,
            ];
        }

        $latestAdminChange = $this->findLatestAuditAction($logs, ['updated']);
        $latestResolution = $this->findLatestAuditAction($logs, ['admin_change_accepted', 'admin_change_declined', 'profile_updated']);

        $hasPending = false;
        if ($latestAdminChange) {
            $adminTs = strtotime((string) ($latestAdminChange['created_at'] ?? ''));
            $resolutionTs = $latestResolution ? strtotime((string) ($latestResolution['created_at'] ?? '')) : false;
            $hasPending = $adminTs !== false && ($resolutionTs === false || $resolutionTs < $adminTs);
        }

        if ($latestAdminChange) {
            $latestAdminChange['old_values_decoded'] = $this->decodeAuditPayload($latestAdminChange['old_values'] ?? null);
            $latestAdminChange['new_values_decoded'] = $this->decodeAuditPayload($latestAdminChange['new_values'] ?? null);
        }

        return [
            'hasPending' => $hasPending,
            'latestAdminChange' => $latestAdminChange,
        ];
    }

    private function findLatestAuditAction(array $logs, array $actions)
    {
        foreach ($logs as $log) {
            if (!is_array($log)) {
                continue;
            }

            $action = strtolower((string) ($log['action'] ?? ''));
            if (in_array($action, $actions, true)) {
                return $log;
            }
        }

        return null;
    }

    private function decodeAuditPayload($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
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

        foreach ($bookingRequests as &$booking) {
            $booking['organizer_name'] = trim((string) ($booking['organizer_name'] ?? ''));
            $booking['organizer_email'] = trim((string) ($booking['organizer_email'] ?? ''));

            if ($booking['organizer_name'] !== '' && $booking['organizer_email'] !== '') {
                continue;
            }

            $organizerId = $booking['organizer_id'] ?? null;
            if (!$organizerId) {
                continue;
            }

            $organizer = $this->userModel->getById($organizerId);
            if (!$organizer) {
                continue;
            }

            if ($booking['organizer_name'] === '') {
                $booking['organizer_name'] = trim((string) ($organizer['full_name'] ?? get_display_name($organizer)));
            }

            if ($booking['organizer_email'] === '') {
                $booking['organizer_email'] = trim((string) ($organizer['email'] ?? ''));
            }
        }
        unset($booking);

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

        if (!$orgId) {
            http_response_code(403);
            return 'No organization assigned to this user';
        }
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

        if (is_array($organizer)) {
            $organizer['full_name'] = $organizer['full_name'] ?? ($booking['organizer_name'] ?? null);
            $organizer['email'] = $organizer['email'] ?? ($booking['organizer_email'] ?? null);
        } elseif (!empty($booking['organizer_name']) || !empty($booking['organizer_email'])) {
            $organizer = [
                'full_name' => $booking['organizer_name'] ?? null,
                'email' => $booking['organizer_email'] ?? null,
            ];
        }

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

        if (!$orgId) {
            return ['error' => 'No organization assigned to this user'];
        }
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

        $accessToken = $_SESSION['access_token'] ?? null;
        $updated = $this->bookingModel->accept($bookingId, $notes, $accessToken, $accessToken);

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

        if (!$orgId) {
            return ['error' => 'No organization assigned to this user'];
        }
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

        $accessToken = $_SESSION['access_token'] ?? null;
        $updated = $this->bookingModel->decline($bookingId, $reason, $accessToken, $accessToken);

        if (!$updated) {
            redirect('/org-admin/bookings?error=' . urlencode('Failed to decline booking'));
        }

        $invitationPdfUrl = $booking['invitation_pdf_url'] ?? null;
        if (!empty($invitationPdfUrl) && $this->deleteInvitationAsset($invitationPdfUrl)) {
            // Clear URL after successful local cleanup to avoid stale links.
            $this->bookingModel->update($bookingId, ['invitation_pdf_url' => null], $accessToken);
        }

        // FR-05: Log booking declination
        $this->auditLog->logBooking($user['id'], 'declined', $bookingId, $booking, [
            'status' => 'declined',
            'reason' => $reason,
        ]);

        redirect('/org-admin/bookings?success=' . urlencode('Booking declined'));
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
