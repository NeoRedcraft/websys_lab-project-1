<?php

namespace App\Controllers;

use App\Middleware\Gatekeeper;
use App\Models\User;
use App\Models\Organization;
use App\Models\AuditLog;
use App\Models\BookingRequest;

class AdminController
{
    private $gatekeeper;
    private $userModel;
    private $organizationModel;
    private $auditLog;
    private $bookingRequest;

    public function __construct()
    {
        $this->gatekeeper = new Gatekeeper();
        $this->userModel = new User();
        $this->organizationModel = new Organization();
        $this->auditLog = new AuditLog();
        $this->bookingRequest = new BookingRequest();

        $this->gatekeeper->requireAdmin();
    }

    public function dashboard($params = [])
    {
        $user = get_user();
        $auditLogs = $this->auditLog->getAll(1000);
        $bookingStats = $this->bookingRequest->getStats(null, session_get('access_token'));
        $organizations = $this->organizationModel->getAllForAdmin(session_get('access_token'));

        return view('pages/admin-dashboard', [
            'user' => $user,
            'auditLogs' => $auditLogs,
            'stats' => $bookingStats,
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
        $organizations = $this->organizationModel->getAllForAdmin(session_get('access_token'));

        return view('admin/organizations-list', [
            'organizations' => $organizations,
            'csrfToken' => csrf_token(),
            'success' => $_GET['success'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function createOrganization($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return view('admin/organization-form', [
                'organization' => null,
                'presidentName' => '',
                'presidentEmail' => '',
                'csrfToken' => csrf_token(),
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log('CREATE POST received');
            error_log('FILES: ' . print_r($_FILES, true));
            error_log('POST: ' . print_r($_POST, true));
            $accessToken = session_get('access_token');

            $name = $_POST['name'] ?? '';
            $genre = $_POST['genre'] ?? '';
            $bio = $_POST['bio'] ?? '';
            $technicalRequirements = $_POST['technical_requirements'] ?? '';
            $youtubeLinks = $_POST['youtube_links'] ?? '';
            $presidentName = trim((string) ($_POST['president_name'] ?? ''));
            $presidentEmail = trim((string) ($_POST['president_email'] ?? ''));

            if (!$name) {
                return view('admin/organization-form', [
                    'error' => 'Organization name is required',
                    'organization' => [
                        'name' => $name,
                        'genre' => $genre,
                        'bio' => $bio,
                        'technical_requirements' => $technicalRequirements,
                        'youtube_links' => $youtubeLinks,
                    ],
                    'presidentName' => $presidentName,
                    'presidentEmail' => $presidentEmail,
                    'csrfToken' => csrf_token(),
                ]);
            }

            if (($presidentName !== '' && $presidentEmail === '') || ($presidentName === '' && $presidentEmail !== '')) {
                return view('admin/organization-form', [
                    'error' => 'Provide both president name and email, or leave both blank.',
                    'organization' => [
                        'name' => $name,
                        'genre' => $genre,
                        'bio' => $bio,
                        'technical_requirements' => $technicalRequirements,
                        'youtube_links' => $youtubeLinks,
                    ],
                    'presidentName' => $presidentName,
                    'presidentEmail' => $presidentEmail,
                    'csrfToken' => csrf_token(),
                ]);
            }

            if ($presidentEmail !== '' && !$this->userModel->isValidMapuaEmail($presidentEmail)) {
                return view('admin/organization-form', [
                    'error' => 'President email must be a @mymail.mapua.edu.ph address.',
                    'organization' => [
                        'name' => $name,
                        'genre' => $genre,
                        'bio' => $bio,
                        'technical_requirements' => $technicalRequirements,
                        'youtube_links' => $youtubeLinks,
                    ],
                    'presidentName' => $presidentName,
                    'presidentEmail' => $presidentEmail,
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
            ], $accessToken);

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
                        $savedImageRef = $this->organizationModel->saveImageReference($result, $imageUrl, $accessToken);
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

            $presidentMessage = '';
            if ($presidentName !== '' && $presidentEmail !== '') {
                $matchedPresident = $this->userModel->getByEmail($presidentEmail);
                $orgAdminRoleId = $this->userModel->getRoleIdByName('org_admin') ?? 2;

                if ($matchedPresident) {
                    $updatedPresident = $this->userModel->adminUpdateUserProfile(
                        $matchedPresident['id'],
                        $presidentName,
                        $presidentEmail,
                        $orgAdminRoleId,
                        (int) $result
                    );

                    if (!$updatedPresident) {
                        $presidentMessage = ' Existing president account could not be reassigned.';
                    } else {
                        $presidentMessage = ' Existing president account was reassigned to org admin for this organization.';
                    }
                } else {
                    $createPresident = $this->userModel->adminCreateUser($presidentEmail, $presidentName, $orgAdminRoleId, (int) $result);
                    if (!$createPresident['success']) {
                        $presidentMessage = ' President account creation failed: ' . ($createPresident['error'] ?? 'Unknown error');
                    } elseif (!empty($createPresident['existing_user_relinked'])) {
                        $presidentMessage = ' Existing auth account was linked and assigned as org admin for this organization.';
                    } else {
                        $presidentMessage = ' President account created. Temporary password: ' . ($createPresident['temp_password'] ?? 'generated');
                    }
                }
            }

            redirect('/admin/organizations?success=' . urlencode('Organization created.' . $presidentMessage));
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
            $currentPresident = $this->getOrganizationPresident($orgId);
            return view('admin/organization-form', [
                'organization' => $organization,
                'presidentName' => $currentPresident['full_name'] ?? '',
                'presidentEmail' => $currentPresident['email'] ?? '',
                'csrfToken' => csrf_token(),
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log('EDIT POST received');
            error_log('FILES: ' . print_r($_FILES, true));
            error_log('POST: ' . print_r($_POST, true));
            $accessToken = session_get('access_token');

            $name = $_POST['name'] ?? $organization['name'];
            $genre = $_POST['genre'] ?? $organization['genre'];
            $bio = $_POST['bio'] ?? $organization['bio'];
            $technicalRequirements = $_POST['technical_requirements'] ?? $organization['technical_requirements'];
            $youtubeLinks = $_POST['youtube_links'] ?? $organization['youtube_links'];
            $presidentName = trim((string) ($_POST['president_name'] ?? ''));
            $presidentEmail = trim((string) ($_POST['president_email'] ?? ''));

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
                        $savedImageRef = $this->organizationModel->saveImageReference($orgId, $imageUrl, $accessToken);
                        if (!$savedImageRef) {
                            return view('admin/organization-form', [
                                'organization' => $organization,
                                'error' => 'Image uploaded, but could not save image reference. Please ensure the organizations table has an image_url column.',
                                'csrfToken' => csrf_token(),
                            ]);
                        }

                        $updateData['image_url'] = $imageUrl;
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

            if (($presidentName !== '' && $presidentEmail === '') || ($presidentName === '' && $presidentEmail !== '')) {
                return view('admin/organization-form', [
                    'organization' => $organization,
                    'presidentName' => $presidentName,
                    'presidentEmail' => $presidentEmail,
                    'error' => 'Provide both president name and email, or leave both blank.',
                    'csrfToken' => csrf_token(),
                ]);
            }

            if ($presidentEmail !== '' && !$this->userModel->isValidMapuaEmail($presidentEmail)) {
                return view('admin/organization-form', [
                    'organization' => $organization,
                    'presidentName' => $presidentName,
                    'presidentEmail' => $presidentEmail,
                    'error' => 'President email must be a @mymail.mapua.edu.ph address.',
                    'csrfToken' => csrf_token(),
                ]);
            }

            $updated = $this->organizationModel->update($orgId, $updateData, $accessToken);

            if (!$updated) {
                return view('admin/organization-form', [
                    'organization' => $organization,
                    'error' => 'Failed to update organization',
                    'csrfToken' => csrf_token(),
                ]);
            }

            $presidentMessage = '';
            if ($presidentName !== '' && $presidentEmail !== '') {
                $currentPresident = $this->getOrganizationPresident($orgId);
                $matchedByEmail = $this->userModel->getByEmail($presidentEmail);

                if ($currentPresident && strcasecmp((string) ($currentPresident['email'] ?? ''), $presidentEmail) === 0) {
                    $updatedPresident = $this->userModel->adminUpdateUserProfile(
                        $currentPresident['id'],
                        $presidentName,
                        $presidentEmail,
                        2,
                        (int) $orgId
                    );
                    if (!$updatedPresident) {
                        $presidentMessage = ' President update failed.';
                    }
                } elseif ($matchedByEmail) {
                    $updatedPresident = $this->userModel->adminUpdateUserProfile(
                        $matchedByEmail['id'],
                        $presidentName,
                        $presidentEmail,
                        2,
                        (int) $orgId
                    );
                    if (!$updatedPresident) {
                        $presidentMessage = ' President assignment failed.';
                    }
                } else {
                    $createPresident = $this->userModel->adminCreateUser($presidentEmail, $presidentName, 2, (int) $orgId);
                    if (!$createPresident['success']) {
                        $presidentMessage = ' President account creation failed: ' . ($createPresident['error'] ?? 'Unknown error');
                    } else {
                        $presidentMessage = ' President account created. Temporary password: ' . ($createPresident['temp_password'] ?? 'generated');
                    }
                }
            }

            $this->auditLog->logOrganization(get_user()['id'], 'updated', $orgId, $organization, [
                'name' => $name,
                'genre' => $genre,
                'bio' => $bio,
                'technical_requirements' => $technicalRequirements,
                'youtube_links' => $youtubeLinks,
                'image_url' => $updateData['image_url'] ?? ($organization['image_url'] ?? null),
            ]);

            redirect('/admin/organizations?success=' . urlencode('Organization updated.' . $presidentMessage));
        }
    }

    private function getOrganizationPresident($orgId)
    {
        $orgUsers = $this->userModel->getByOrganization($orgId);
        if (!is_array($orgUsers)) {
            return null;
        }

        foreach ($orgUsers as $candidate) {
            $candidateId = $candidate['id'] ?? null;
            if (!$candidateId) {
                continue;
            }

            $role = $this->userModel->getRole($candidateId);
            if (($role['name'] ?? '') === 'org_admin') {
                return $candidate;
            }
        }

        return null;
    }

    public function deleteOrganization($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $orgId = $_POST['id'] ?? null;
        if (!$orgId) {
            redirect('/admin/organizations?error=' . urlencode('Organization ID required'));
        }

        $accessToken = session_get('access_token');
        $organization = $this->organizationModel->getByIdForAdmin($orgId, $accessToken);
        if (!$organization) {
            redirect('/admin/organizations?error=' . urlencode('Organization not found'));
        }

        $previousPresident = $this->getOrganizationPresident($orgId);
        if (!empty($previousPresident['id'])) {
            $organizerRoleId = $this->userModel->getRoleIdByName('organizer') ?? 3;
            $downgraded = $this->userModel->adminUpdateRole($previousPresident['id'], $organizerRoleId, null);
            if (!$downgraded) {
                redirect('/admin/organizations?error=' . urlencode('Failed to reassign the current president to organizer before deleting organization.'));
            }
        }

        $deleted = $this->organizationModel->delete($orgId, $accessToken);

        if (!$deleted) {
            redirect('/admin/organizations?error=' . urlencode('Failed to delete organization. If users are still assigned to this organization, deactivate it instead.'));
        }

        $this->auditLog->logOrganization(get_user()['id'], 'deleted', $orgId, $organization, []);

        $successMessage = 'Organization deleted';
        if (!empty($previousPresident['email'])) {
            $successMessage .= ' and assigned president role was reverted to organizer';
        }

        redirect('/admin/organizations?success=' . urlencode($successMessage));
    }

    public function activateOrganization($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $orgId = $_POST['id'] ?? null;
        if (!$orgId) {
            redirect('/admin/dashboard?error=' . urlencode('Organization ID required'));
        }

        $accessToken = session_get('access_token');
        $organization = $this->organizationModel->getByIdForAdmin($orgId, $accessToken);
        if (!$organization) {
            redirect('/admin/dashboard?error=' . urlencode('Organization not found'));
        }

        $updated = $this->organizationModel->setActiveStatus($orgId, true, $accessToken);
        if (!$updated) {
            redirect('/admin/dashboard?error=' . urlencode('Failed to activate organization'));
        }

        $this->auditLog->logOrganization(get_user()['id'], 'activated', $orgId, $organization, [
            'is_active' => true,
        ]);

        redirect('/admin/dashboard?success=' . urlencode('Organization activated'));
    }

    public function deactivateOrganization($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $orgId = $_POST['id'] ?? null;
        if (!$orgId) {
            redirect('/admin/dashboard?error=' . urlencode('Organization ID required'));
        }

        $accessToken = session_get('access_token');
        $organization = $this->organizationModel->getByIdForAdmin($orgId, $accessToken);
        if (!$organization) {
            redirect('/admin/dashboard?error=' . urlencode('Organization not found'));
        }

        $updated = $this->organizationModel->setActiveStatus($orgId, false, $accessToken);
        if (!$updated) {
            redirect('/admin/dashboard?error=' . urlencode('Failed to deactivate organization'));
        }

        $this->auditLog->logOrganization(get_user()['id'], 'deactivated', $orgId, $organization, [
            'is_active' => false,
        ]);

        redirect('/admin/dashboard?success=' . urlencode('Organization deactivated'));
    }

    public function listUsers($params = [])
    {
        $success = $_GET['success'] ?? null;
        $error = $_GET['error'] ?? null;

        $users = $this->userModel->getAll();
        $organizations = $this->organizationModel->getAllForAdmin(session_get('access_token'));

        try {
            $supabase = \App\Utils\Supabase::getInstance();
            $rolesRaw = $supabase->adminRequest('GET', '/rest/v1/roles?select=id,name&order=id.asc');
            $roles = is_array($rolesRaw) ? $rolesRaw : [];
        } catch (\Exception $e) {
            $roles = [];
        }

        $roleNameById = [];
        foreach ($roles as $role) {
            if (!is_array($role) || !isset($role['id'])) {
                continue;
            }

            $roleNameById[(int) $role['id']] = (string) ($role['name'] ?? 'unknown');
        }

        $orgNameById = [];
        foreach ($organizations as $organization) {
            if (!is_array($organization) || !isset($organization['id'])) {
                continue;
            }

            $orgNameById[(int) $organization['id']] = (string) ($organization['name'] ?? '—');
        }

        foreach ($users as &$user) {
            if (!is_array($user)) {
                continue;
            }

            $roleId = isset($user['role_id']) ? (int) $user['role_id'] : null;
            $orgId = isset($user['org_id']) ? (int) $user['org_id'] : null;

            if (empty($user['roles']) || !is_array($user['roles'])) {
                $user['roles'] = [];
            }

            if (empty($user['organizations']) || !is_array($user['organizations'])) {
                $user['organizations'] = [];
            }

            if (empty($user['roles']['name'])) {
                $user['roles']['name'] = $roleId !== null && isset($roleNameById[$roleId])
                    ? $roleNameById[$roleId]
                    : 'unknown';
            }

            if (empty($user['organizations']['name'])) {
                $user['organizations']['name'] = $orgId !== null && isset($orgNameById[$orgId])
                    ? $orgNameById[$orgId]
                    : '—';
            }
        }
        unset($user);

        return view('admin/users-list', [
            'users'         => $users,
            'organizations' => $organizations,
            'roles'         => $roles,
            'csrfToken'     => csrf_token(),
            'success'       => $success,
            'error'         => $error,
            'currentUserId' => get_user()['id'] ?? null,
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
            redirect('/admin/users?error=' . urlencode('User ID and Role ID required'));
        }

        $user = $this->userModel->getById($userId);
        if (!$user) {
            redirect('/admin/users?error=' . urlencode('User not found'));
        }

        $oldRole = $this->userModel->getRole($userId);

        $updated = $this->userModel->adminUpdateRole($userId, (int)$roleId, $orgId ? (int)$orgId : null);

        if (!$updated) {
            redirect('/admin/users?error=' . urlencode('Failed to assign role'));
        }

        $this->auditLog->logUser(get_user()['id'], 'role_assigned', $userId, $user, [
            'old_role' => $oldRole['name'] ?? null,
            'new_role' => $roleId,
            'org_id' => $orgId,
        ]);

        redirect('/admin/users?success=' . urlencode('Role assigned successfully'));
    }

    public function deleteUser($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return 'Method not allowed';
        }

        $userId = trim((string) ($_POST['user_id'] ?? ''));
        if ($userId === '') {
            redirect('/admin/users?error=' . urlencode('User ID required'));
        }

        $currentUserId = (string) (get_user()['id'] ?? '');
        if ($currentUserId !== '' && $currentUserId === $userId) {
            redirect('/admin/users?error=' . urlencode('You cannot delete your own account'));
        }

        $user = $this->userModel->getById($userId);
        if (!$user) {
            redirect('/admin/users?error=' . urlencode('User not found'));
        }

        $deleted = $this->userModel->adminDeleteUser($userId);
        if (!$deleted) {
            redirect('/admin/users?error=' . urlencode('Failed to delete user'));
        }

        $this->auditLog->logUser(get_user()['id'], 'deleted', $userId, $user, [
            'deleted' => true,
        ]);

        redirect('/admin/users?success=' . urlencode('User deleted successfully'));
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
        $limit = (int) ($_GET['limit'] ?? 100);
        if ($limit < 1) {
            $limit = 100;
        }
        if ($limit > 1000) {
            $limit = 1000;
        }

        $userId = trim((string) ($_GET['user_id'] ?? ''));
        $users = $this->userModel->getAll();

        if ($userId) {
            $logs = $this->auditLog->getByUser($userId, $limit);
        } else {
            $logs = $this->auditLog->getAll($limit);
        }

        return view('admin/audit-logs', [
            'logs' => $logs,
            'users' => $users,
            'selectedUserId' => $userId,
            'selectedLimit' => $limit,
            'csrfToken' => csrf_token(),
        ]);
    }
}