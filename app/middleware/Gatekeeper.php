<?php

namespace App\Middleware;

use App\Models\User;

class Gatekeeper
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated()
    {
        return session_has('user') && session_has('access_token');
    }

    /**
     * Require user to be authenticated
     */
    public static function requireAuth()
    {
        if (!self::isAuthenticated()) {
            redirect('/signin');
            exit;
        }
    }

    /**
     * Require specific role
     */
    public function requireRole($requiredRole)
    {
        self::requireAuth();
        
        $user = get_user();
        $userObj = $this->userModel->getById($user['id']);
        
        if (!$userObj) {
            self::terminateSession('User not found');
            exit;
        }

        $userRole = $this->userModel->getRole($user['id']);
        
        if (!$userRole || $userRole['name'] !== $requiredRole) {
            self::terminateSession('Unauthorized access attempt');
            exit;
        }

        return true;
    }

    /**
     * Require one of multiple roles
     */
    public function requireRoles(array $allowedRoles)
    {
        self::requireAuth();
        
        $user = get_user();
        $userRole = $this->userModel->getRole($user['id']);
        
        if (!$userRole || !in_array($userRole['name'], $allowedRoles)) {
            self::terminateSession('Unauthorized access');
            exit;
        }

        return true;
    }

    /**
     * Require system admin role
     */
    public function requireAdmin()
    {
        return $this->requireRole('system_admin');
    }

    /**
     * Require organization admin role
     */
    public function requireOrgAdmin()
    {
        return $this->requireRole('org_admin');
    }

    /**
     * Require organizer role (can be org_admin or organizer)
     */
    public function requireOrganizerAccess()
    {
        return $this->requireRoles(['organizer', 'org_admin', 'system_admin']);
    }

    /**
     * Terminate session due to unauthorized access
     */
    public static function terminateSession($reason = 'Session terminated')
    {
        error_log('Gatekeeper: ' . $reason . ' from ' . $_SERVER['REMOTE_ADDR']);
        
        session_forget('user');
        session_forget('access_token');
        session_forget('refresh_token');
        
        http_response_code(403);
        
        header('Location: /signin?error=unauthorized');
        exit;
    }

    /**
     * Check if user owns organization
     */
    public function userOwnsOrganization($userId, $orgId)
    {
        $user = $this->userModel->getById($userId);
        
        if (!$user || $user['org_id'] !== $orgId) {
            return false;
        }

        $userRole = $this->userModel->getRole($userId);
        
        return $userRole && $userRole['name'] === 'org_admin';
    }

    /**
     * Check if user owns booking request
     */
    public function userOwnsBooking($userId, $bookingOwnerId)
    {
        return $userId === $bookingOwnerId;
    }

    /**
     * Check if user can manage organization bookings
     */
    public function userCanManageOrgBookings($userId, $orgId)
    {
        $user = $this->userModel->getById($userId);
        
        if (!$user) {
            return false;
        }

        // System admin can manage any org bookings
        $userRole = $this->userModel->getRole($userId);
        if ($userRole && $userRole['name'] === 'system_admin') {
            return true;
        }

        // Org admin can only manage their own org bookings
        return $user['org_id'] === $orgId && 
               $userRole && 
               $userRole['name'] === 'org_admin';
    }

    /**
     * Log access attempt
     */
    public function logAccess($action, $resource, $allowed = true)
    {
        $user = get_user();
        $status = $allowed ? 'ALLOWED' : 'DENIED';
        
        error_log("[Gatekeeper] {$status} - User: {$user['email']} - Action: {$action} - Resource: {$resource} - IP: {$_SERVER['REMOTE_ADDR']}");
    }
}
