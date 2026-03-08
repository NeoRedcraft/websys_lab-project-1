<?php

namespace App\Controllers;

class PagesController
{
    public function home($params = [])
    {
        return view('pages/home', [
            'isAuthenticated' => auth_check(),
            'user' => get_user(),
            'homeBannerUrl' => get_home_banner_url(),
        ]);
    }

    public function dashboard($params = [])
    {
        require_auth();
        $role = session_get('role');

        if ($role === 'system_admin') {
            redirect('/admin/dashboard');
        } elseif ($role === 'org_admin') {
            redirect('/org-admin/dashboard');
        } elseif ($role === 'organizer') { // ADD THIS
            redirect('/organizer-dashboard');
        } else {
            redirect('/bookings');
        }
    }

    public function adminDashboard($params = [])
    {
        require_auth();
        
        if (!user_has_role('system_admin')) { 
            http_response_code(403);
            return view('error/403');
        }

        return view('pages/admin-dashboard', [
            'user' => get_user(),
        ]);
    }

    public function orgAdminDashboard($params = [])
    {
        require_auth();
        
        if (!user_has_role('org_admin')) {
            http_response_code(403);
            return view('error/403');
        }

        return view('pages/org-admin-dashboard', [
            'user' => get_user(),
        ]);
    }

    public function organizerDashboard($params = [])
    {
        require_auth();
        
        if (!user_has_role('organizer')) {
            http_response_code(403);
            return view('error/403');
        }

        $user = get_user();
        $bookingModel = new \App\Models\BookingRequest();
        $bookings = $bookingModel->getByOrganizer($user['id']);

        $stats = [
            'pending' => 0,
            'accepted' => 0,
        ];

        foreach ($bookings as $booking) {
            $status = strtolower((string) ($booking['status'] ?? ''));
            if ($status === 'pending') {
                $stats['pending']++;
            } elseif ($status === 'accepted') {
                $stats['accepted']++;
            }
        }

        return view('pages/organizer-dashboard', [
            'user' => $user,
            'bookings' => $bookings,
            'stats' => $stats,
        ]);
    }

    public function directory($params = [])
    {
        return view('pages/talent-directory', [
            'isAuthenticated' => auth_check(),
            'user' => get_user(),
        ]);
    }

    public function searchResults($params = [])
    {
        $query = trim($_GET['q'] ?? '');
        $organizations = [];

        if ($query !== '') {
            $orgModel = new \App\Models\Organization();
            $organizations = $orgModel->searchActiveByTerm($query);
        }

        return view('pages/search-results', [
            'query' => $query,
            'organizations' => $organizations,
            'isAuthenticated' => auth_check(),
            'user' => get_user(),
        ]);
    }

    public function apiGetDirectoryOrganizations($params = [])
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        try {
            $orgModel = new \App\Models\Organization();
            $organizations = $orgModel->getAllWithDetails();

            echo json_encode([
                'success' => true,
                'data' => $organizations,
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching organizations',
            ]);
        }

        exit;
    }

    public function calendar($params = [])
    {
        if (!auth_check()) {
            return view('pages/calendar', [
                'isAuthenticated' => false,
                'user' => null,
                'requiresAuthPrompt' => true,
            ]);
        }

        return view('pages/calendar', [
            'isAuthenticated' => auth_check(),
            'user' => get_user(),
            'requiresAuthPrompt' => false,
        ]);
    }

    public function organizationProfile($params = [])
    {
        $orgId = $params['id'] ?? null;
        
        if (!$orgId) {
            redirect('/directory');
        }

        $orgModel = new \App\Models\Organization();
        $org = $orgModel->getWithAcceptedBookings($orgId);
        
        if (!$org) {
            http_response_code(404);
            return view('error/404');
        }

        return view('pages/organization-profile', [
            'organization' => $org,
            'isAuthenticated' => auth_check(),
            'user' => get_user(),
        ]);
    }

    public function accountSettings($params = [])
    {
        require_auth();
        return view('pages/account-settings', [
            'user' => get_user(),
        ]);
    }

    public function notFound($params = [])
    {
        http_response_code(404);
        return view('error/404', [
            'isAuthenticated' => auth_check(),
        ]);
    }
}