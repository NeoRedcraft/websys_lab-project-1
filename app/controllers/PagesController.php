<?php

namespace App\Controllers;

class PagesController
{
    public function home($params = [])
    {
        return view('pages/home', [
            'isAuthenticated' => auth_check(),
            'user' => get_user(),
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

        return view('pages/organizer-dashboard', [
            'user' => get_user(),
        ]);
    }

    public function directory($params = [])
    {
        return view('pages/talent-directory', [
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