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

        $this->gatekeeper->requireAdmin();
    }

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
            'homeBannerUrl' => get_home_banner_url(),
            'success' => $_GET['success'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function uploadHomeBanner($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        if (empty($_FILES['home_banner']['tmp_name']) || !isset($_FILES['home_banner']['error']) || $_FILES['home_banner']['error'] !== UPLOAD_ERR_OK) {
            redirect('/admin/dashboard?error=' . urlencode('Upload failed. Please choose a valid image file.'));
        }

        $file = $_FILES['home_banner'];
        $mimeType = mime_content_type($file['tmp_name']);
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (!isset($allowedTypes[$mimeType])) {
            redirect('/admin/dashboard?error=' . urlencode('Invalid image type. Use JPG, PNG, WEBP, or GIF.'));
        }

        if ((int) $file['size'] > 5 * 1024 * 1024) {
            redirect('/admin/dashboard?error=' . urlencode('Image is too large. Maximum size is 5MB.'));
        }

        $uploadsDir = base_path('uploads');
        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
            redirect('/admin/dashboard?error=' . urlencode('Could not create uploads directory.'));
        }

        foreach (glob($uploadsDir . '/home-banner.*') ?: [] as $existingFile) {
            @unlink($existingFile);
        }

        $extension = $allowedTypes[$mimeType];
        $targetPath = $uploadsDir . '/home-banner.' . $extension;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            redirect('/admin/dashboard?error=' . urlencode('Failed to save uploaded image.'));
        }

        redirect('/admin/dashboard?success=' . urlencode('Home banner image updated.'));
    }

    public function listOrganizations($params = [])
    {
        $organizations = $this->organizationModel->getAll();

        return view('admin/organizations-list', [
            'organizations' => $organizations,
            'csrfToken' => csrf_token(),
        ]);
    }

    public function createOrganization($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return view('admin/organization-form', [
                'organization' => null,
                'csrfToken' => csrf_token(),
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log('CREATE POST received');
            error_log('FILES: ' . print_r($_FILES, true));
            error_log('POST: ' . print_r($_POST, true));

            $name = $_POST['name'] ?? '';
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

            if (!empty($_FILES['image']['name'])) {
                try {
                    $imageUrl = $this->organizationModel->uploadImage($result, $_FILES['image']);
                    if ($imageUrl) {
                        $savedImageRef = $this->organizationModel->saveImageReference($result, $imageUrl);
                        if (!$savedImageRef) {
                            return view('admin/organization-form', [
                                'error' => 'Organization created, but image could not be saved. Please ensure the organizations table has an image_url column.',
                                'organization' => $this->organizationModel->getById($result),
                                'csrfToken' => csrf_token(),
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    error_log('Image upload failed: ' . $e->getMessage());
                    return view('admin/organization-form', [
                        'error' => 'Organization created, but image upload failed: ' . $e->getMessage(),
                        'organization' => $this->organizationModel->getById($result),
                        'csrfToken' => csrf_token(),
                    ]);
                }
            }

            $this->auditLog->logOrganization(get_user()['id'], 'created', $result, null, [
                'name' => $name,
                'genre' => $genre,
            ]);

            redirect('/admin/organizations?success=Organization created');
        }
    }

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
            error_log('EDIT POST received');
            error_log('FILES: ' . print_r($_FILES, true));
            error_log('POST: ' . print_r($_POST, true));

            $name = $_POST['name'] ?? $organization['name'];
            $genre = $_POST['genre'] ?? $organization['genre'];
            $bio = $_POST['bio'] ?? $organization['bio'];
            $technicalRequirements = $_POST['technical_requirements'] ?? $organization['technical_requirements'];
            $youtubeLinks = $_POST['youtube_links'] ?? $organization['youtube_links'];

            $updateData = [
                'name' => $name,
                'genre' => $genre,
                'bio' => $bio,
                'technical_requirements' => $technicalRequirements,
                'youtube_links' => $youtubeLinks,
            ];

            if (!empty($_FILES['image']['name'])) {
                try {
                    $imageUrl = $this->organizationModel->uploadImage($orgId, $_FILES['image']);
                    if ($imageUrl) {
                        $savedImageRef = $this->organizationModel->saveImageReference($orgId, $imageUrl);
                        if (!$savedImageRef) {
                            return view('admin/organization-form', [
                                'organization' => $organization,
                                'error' => 'Image uploaded, but could not save image reference. Please ensure the organizations table has an image_url column.',
                                'csrfToken' => csrf_token(),
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    error_log('Image upload failed: ' . $e->getMessage());
                    return view('admin/organization-form', [
                        'organization' => $organization,
                        'error' => 'Image upload failed: ' . $e->getMessage(),
                        'csrfToken' => csrf_token(),
                    ]);
                }
            }

            $updated = $this->organizationModel->update($orgId, $updateData);

            if (!$updated) {
                return view('admin/organization-form', [
                    'organization' => $organization,
                    'error' => 'Failed to update organization',
                    'csrfToken' => csrf_token(),
                ]);
            }

            $this->auditLog->logOrganization(get_user()['id'], 'updated', $orgId, $organization, [
                'name' => $name,
                'genre' => $genre,
            ]);

            redirect('/admin/organizations?success=Organization updated');
        }
    }

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

        $this->auditLog->logOrganization(get_user()['id'], 'deleted', $orgId, $organization, []);

        redirect('/admin/organizations?success=Organization deleted');
    }

    public function listUsers($params = [])
    {
        $users         = $this->userModel->getAll();
        $organizations = $this->organizationModel->getAll();

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

        $this->auditLog->logUser(get_user()['id'], 'role_assigned', $userId, $user, [
            'old_role' => $oldRole['name'] ?? null,
            'new_role' => $roleId,
            'org_id' => $orgId,
        ]);

        return ['success' => 'Role assigned successfully'];
    }

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
            'temp_password' => $result['temp_password'],
            'user_id'       => $result['user_id'],
        ]);
    }

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