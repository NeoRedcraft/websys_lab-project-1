<?php

namespace App\Controllers;

use App\Utils\Supabase;
use App\Models\User;
use App\Models\AuditLog;

class AuthController
{
    private $supabase = null;
    private $userModel = null;
    private $auditLog = null;

    public function __construct()
    {
        $this->userModel = new User();
        $this->auditLog = new AuditLog();
    }

    private function getSupabase()
    {
        if ($this->supabase === null) {
            $this->supabase = Supabase::getInstance();
        }
        return $this->supabase;
    }

    public function signIn($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (auth_check()) {
                redirect('/');
            }
            return view('auth/signin', ['csrfToken' => csrf_token()]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (!$email || !$password) {
                return view('auth/signin', [
                    'error' => 'Email and password are required',
                    'csrfToken' => csrf_token(),
                ]);
            }

            $result = $this->getSupabase()->signIn($email, $password);

            if (!$result['success']) {
                // Log failed login attempt
                $this->auditLog->logAuth(null, 'login_failed', 'invalid_credentials', [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                ]);
                
                return view('auth/signin', [
                    'error' => $result['error'] ?? 'Sign in failed',
                    'csrfToken' => csrf_token(),
                ]);
            }

            $data = $result['data'];
            session_set('access_token', $data['access_token']);
            session_set('refresh_token', $data['refresh_token'] ?? null);
            session_set('expires_in', $data['expires_in'] ?? 3600);

            // Extract user from signIn response (Supabase REST returns user in the response)
            // This works for both Supabase and LocalAuth
            $user = $data['user'] ?? null;
            $userId = $user['id'] ?? null;

            if ($user) {
                session_set('user', $user);

                // Log successful login
                $this->auditLog->logAuth($userId, 'login_success', 'authenticated', [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                ]);
            }

            // FR-02: Role-based redirection after login
            $roleName = null;
            if ($userId) {
                $role = $this->userModel->getRole($userId);
                // Check if your DB column is 'name' or 'role_name'
                $roleName = $role['role_name'] ?? $role['name'] ?? null; 

                if ($roleName) {
                    session_set('role', $roleName);
                }
            }

            if ($roleName === 'system_admin') {
                redirect('/admin/dashboard');
            } elseif ($roleName === 'org_admin') {
                redirect('/org-admin/dashboard');
            } elseif ($roleName === 'organizer') {
                // Redirect specifically to the organizer dashboard
                redirect('/organizer-dashboard'); 
            }
                else {
                redirect('/bookings');
            }
        }
    }

    public function signUp($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (auth_check()) {
                redirect('/');
            }
            return view('auth/signup', ['csrfToken' => csrf_token()]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $name = $_POST['name'] ?? '';

            if (!$email || !$password || !$name) {
                return view('auth/signup', [
                    'error' => 'Email, password, and name are required',
                    'csrfToken' => csrf_token(),
                ]);
            }

            // FR-01: Validate domain - Only @mymail.mapua.edu.ph allowed
            if (!$this->userModel->isValidMapuaEmail($email)) {
                $this->auditLog->logAuth(null, 'signup_rejected', 'domain_invalid', [
                    'email' => $email,
                    'reason' => 'External domain',
                ]);
                
                return view('auth/signup', [
                    'error' => 'Only @mymail.mapua.edu.ph email addresses are allowed',
                    'csrfToken' => csrf_token(),
                ]);
            }

            // Check if email already exists
            $existingUser = $this->userModel->getByEmail($email);
            if ($existingUser) {
                return view('auth/signup', [
                    'error' => 'Email already registered',
                    'csrfToken' => csrf_token(),
                ]);
            }

            // Hash password with bcrypt
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Create auth account in Supabase
            $metadata = [
                'name' => $name,
                'email' => $email,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $result = $this->getSupabase()->signUp($email, $password, $metadata);

            if ($result['success']) {
                $data = $result['data'];
                $userId = $data['user']['id'];

                // Create the profile in your public table
                $this->userModel->create(
                    $userId, 
                    $email, 
                    $name, 
                    3,    // Default 'organizer' role_id
                    null  // Default org_id
                );
            }
            
            if (!$result['success']) {
                $this->auditLog->logAuth(null, 'signup_failed', 'auth_error', [
                    'email' => $email,
                    'error' => $result['error']['message'] ?? 'Unknown error',
                ]);
                
                return view('auth/signup', [
                    'error' => $result['error'] ?? 'Sign up failed',
                    'csrfToken' => csrf_token(),
                ]);
            }

            $data = $result['data'];
            $userId = $data['user']['id'] ?? null;

            // Create user in users_extended table with organizer role (default)
            $createUserResult = $this->userModel->create(
                $userId,
                $email,
                $name,
                3,    // organizer role_id
                null  // no org
            );

            if (!$createUserResult) {
                error_log("Failed to create user record for {$email} during signup");
                return view('auth/signup', [
                    'error' => 'Account creation failed. Please try again or contact support.',
                    'csrfToken' => csrf_token(),
                ]);
            }

            // Log successful signup
            $this->auditLog->logAuth($userId, 'signup_success', 'user_created', [
                'email' => $email,
                'name' => $name,
                'role' => 'organizer',
            ]);

            // Auto sign in after signup
            $signInResult = $this->getSupabase()->signIn($email, $password);
            if ($signInResult['success']) {
                $data = $signInResult['data'];
                session_set('access_token', $data['access_token']);
                session_set('refresh_token', $data['refresh_token'] ?? null);
                session_set('expires_in', $data['expires_in'] ?? 3600);

                // Extract user from signIn response
                $user = $data['user'] ?? null;
                if ($user) {
                    session_set('user', $user);
                }

                // FR-02: Role-based redirection after auto-signin
                $roleName = null;
                if ($userId) {
                    $role = $this->userModel->getRole($userId);
                    $roleName = $role['name'] ?? null;
                    if ($roleName) {
                        session_set('role', $roleName);
                    }
                }

                if ($roleName === 'system_admin') {
                    redirect('/admin/dashboard');
                } elseif ($roleName === 'org_admin') {
                    redirect('/org-admin/dashboard');
                } else {
                    redirect('/bookings');
                }
            }

            return view('auth/signup', [
                'success' => 'Account created! Please sign in.',
                'csrfToken' => csrf_token(),
            ]);
        }
    }

    public function signOut($params = [])
    {
        $user = get_user();
        if ($user) {
            // Log logout
            $this->auditLog->logAuth($user['id'], 'logout', 'session_terminated', [
                'email' => $user['email'],
                'ip' => $_SERVER['REMOTE_ADDR'],
            ]);
        }

        $this->getSupabase()->signOut();
        
        // Clear all session data
        session_forget('user');
        session_forget('access_token');
        session_forget('refresh_token');
        session_forget('expires_in');
        
        redirect('/');
    }

    public function profile($params = [])
    {
        require_auth();
        $user = get_user();
        return view('auth/profile', ['user' => $user]);
    }
}