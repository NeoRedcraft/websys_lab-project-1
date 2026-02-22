<?php

namespace App\Controllers;

use App\Middleware\Gatekeeper;
use App\Models\User;
use App\Models\Organization;
use App\Models\AuditLog;

class AdminController
{
    private $gatekeeper;
    private $userModel;
    private $organizationModel;
    private $auditLog;

    public function __construct()
    {
        $this->gatekeeper = new Gatekeeper();
        $this->userModel = new User();
        $this->organizationModel = new Organization();
        $this->auditLog = new AuditLog();

        // FR-03: Require system admin role for all admin operations
        $this->gatekeeper->requireAdmin();
    }

    /**
     * Admin Dashboard - Master Panel
     */
    public function dashboard($params = [])
    {
        $user = get_user();
        $auditLogs = $this->auditLog->getAll(50);
        $organizations = $this->organizationModel->getAll();

        return view('pages/admin-dashboard', [
            'user' => $user,
            'auditLogs' => $auditLogs,
            'organizations' => $organizations,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * List all organizations
     */
    public function listOrganizations($params = [])
    {
        $organizations = $this->organizationModel->getAll();

        return view('admin/organizations-list', [
            'organizations' => $organizations,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * Create new organization (GET form / POST create)
     */
    public function createOrganization($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return view('admin/organization-form', [
                'organization' => null,
                'csrfToken' => csrf_token(),
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $genre = $_POST['genre'] ?? '';
            $bio = $_POST['bio'] ?? '';
            $technicalRequirements = $_POST['technical_requirements'] ?? '';
            $youtubeLinks = $_POST['youtube_links'] ?? '';

            if (!$name) {
                return view('admin/organization-form', [
                    'error' => 'Organization name is required',
                    'csrfToken' => csrf_token(),
                ]);
            }

            $result = $this->organizationModel->create([
                'name' => $name,
                'description' => $description,
                'genre' => $genre,
                'bio' => $bio,
                'technical_requirements' => $technicalRequirements,
                'youtube_links' => $youtubeLinks,
                'is_active' => true,
            ]);

            if (!$result) {
                return view('admin/organization-form', [
                    'error' => 'Failed to create organization',
                    'csrfToken' => csrf_token(),
                ]);
            }

            // Log organization creation
            $this->auditLog->logOrganization(get_user()['id'], 'created', $result, null, [
                'name' => $name,
                'description' => $description,
            ]);

            redirect('/admin/organizations?success=Organization created');
        }
    }

    /**
     * Edit organization
     */
    public function editOrganization($params = [])
    {
        $orgId = $params['id'] ?? null;
        if (!$orgId) {
            http_response_code(404);
            return 'Organization not found';
        }

        $organization = $this->organizationModel->getById($orgId);
        if (!$organization) {
            http_response_code(404);
            return 'Organization not found';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return view('admin/organization-form', [
                'organization' => $organization,
                'csrfToken' => csrf_token(),
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? $organization['name'];
            $description = $_POST['description'] ?? $organization['description'];
            $genre = $_POST['genre'] ?? $organization['genre'];
            $bio = $_POST['bio'] ?? $organization['bio'];
            $technicalRequirements = $_POST['technical_requirements'] ?? $organization['technical_requirements'];
            $youtubeLinks = $_POST['youtube_links'] ?? $organization['youtube_links'];

            $updated = $this->organizationModel->update($orgId, [
                'name' => $name,
                'description' => $description,
                'genre' => $genre,
                'bio' => $bio,
                'technical_requirements' => $technicalRequirements,
                'youtube_links' => $youtubeLinks,
            ]);

            if (!$updated) {
                return view('admin/organization-form', [
                    'organization' => $organization,
                    'error' => 'Failed to update organization',
                    'csrfToken' => csrf_token(),
                ]);
            }

            // Log organization update
            $this->auditLog->logOrganization(get_user()['id'], 'updated', $orgId, $organization, [
                'name' => $name,
                'description' => $description,
            ]);

            redirect('/admin/organizations?success=Organization updated');
        }
    }

    /**
     * Delete organization
     */
    public function deleteOrganization($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $orgId = $_POST['id'] ?? null;
        if (!$orgId) {
            return view('admin/organizations-list', [
                'error' => 'Organization ID required',
            ]);
        }

        $organization = $this->organizationModel->getById($orgId);
        if (!$organization) {
            return view('admin/organizations-list', [
                'error' => 'Organization not found',
            ]);
        }

        $deleted = $this->organizationModel->delete($orgId);

        if (!$deleted) {
            return view('admin/organizations-list', [
                'error' => 'Failed to delete organization',
            ]);
        }

        // Log organization deletion
        $this->auditLog->logOrganization(get_user()['id'], 'deleted', $orgId, $organization, []);

        redirect('/admin/organizations?success=Organization deleted');
    }

    /**
     * List all users for role assignment
     */
    public function listUsers($params = [])
    {
        $users         = $this->userModel->getAll();
        $organizations = $this->organizationModel->getAll();

        // Fetch roles for the change-role dropdown
        try {
            $supabase = \App\Utils\Supabase::getInstance();
            $rolesRaw = $supabase->adminRequest('GET', '/rest/v1/roles?select=id,name&order=id.asc');
            $roles = is_array($rolesRaw) ? $rolesRaw : [];
        } catch (\Exception $e) {
            $roles = [];
        }

        return view('admin/users-list', [
            'users'         => $users,
            'organizations' => $organizations,
            'roles'         => $roles,
            'csrfToken'     => csrf_token(),
        ]);
    }

    /**
     * Assign role to user
     */
    public function assignRole($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $userId = $_POST['user_id'] ?? null;
        $roleId = $_POST['role_id'] ?? null;
        $orgId = $_POST['org_id'] ?? null;

        if (!$userId || !$roleId) {
            return ['error' => 'User ID and Role ID required'];
        }

        $user = $this->userModel->getById($userId);
        if (!$user) {
            return ['error' => 'User not found'];
        }

        $oldRole = $this->userModel->getRole($userId);

        $updated = $this->userModel->adminUpdateRole($userId, (int)$roleId, $orgId ? (int)$orgId : null);

        if (!$updated) {
            return ['error' => 'Failed to assign role'];
        }

        // Log role assignment
        $this->auditLog->logUser(get_user()['id'], 'role_assigned', $userId, $user, [
            'old_role' => $oldRole['name'] ?? null,
            'new_role' => $roleId,
            'org_id' => $orgId,
        ]);

        return ['success' => 'Role assigned successfully'];
    }

    /**
     * Pre-register organization president
     */
    public function preregisterPresident($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $email = $_POST['email'] ?? '';
        $name  = $_POST['name']  ?? '';
        $orgId = !empty($_POST['org_id']) ? (int)$_POST['org_id'] : null;

        if (!$email || !$name || !$orgId) {
            return json_response(['error' => 'Email, name, and organization required'], 422);
        }

        if (!$this->userModel->isValidMapuaEmail($email)) {
            return json_response(['error' => 'Only @mymail.mapua.edu.ph email addresses are allowed'], 422);
        }

        if ($this->userModel->getByEmail($email)) {
            return json_response(['error' => 'Email already registered'], 409);
        }

        // org_admin role_id = 2 (matches your roles table insert order)
        $result = $this->userModel->adminCreateUser($email, $name, 2, $orgId);

        if (!$result['success']) {
            return json_response(['error' => $result['error'] ?? 'Failed to pre-register president'], 500);
        }

        $this->auditLog->logUser(get_user()['id'], 'president_preregistered', $result['user_id'], null, [
            'email'  => $email,
            'name'   => $name,
            'org_id' => $orgId,
        ]);

        return json_response([
            'success'       => 'President pre-registered successfully',
            'temp_password' => $result['temp_password'], // Show once to admin
            'user_id'       => $result['user_id'],
        ]);
    }

    /**
     * View audit logs
     */
    public function auditLogs($params = [])
    {
        $limit = $_GET['limit'] ?? 100;
        $userId = $_GET['user_id'] ?? null;

        if ($userId) {
            $logs = $this->auditLog->getByUser($userId, $limit);
        } else {
            $logs = $this->auditLog->getAll($limit);
        }

        return view('admin/audit-logs', [
            'logs' => $logs,
            'csrfToken' => csrf_token(),
        ]);
    }
}