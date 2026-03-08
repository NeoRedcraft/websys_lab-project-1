<?php

namespace App\Models;

use App\Utils\Supabase;

class Organization
{
    private $supabase;

    public function __construct()
    {
        $this->supabase = Supabase::getInstance();
    }

    public function getById($orgId)
    {
        try {
            $response = $this->supabase->query('organizations', '*', ['id' => $orgId]);
            if (!$response['success']) {
                return null;
            }

            $organization = $response['data'][0] ?? null;
            if (!$organization) {
                return null;
            }

            return $this->normalizeImageUrl($organization);
        } catch (\Exception $e) {
            error_log('Error fetching organization: ' . $e->getMessage());
            return null;
        }
    }

    public function getByIdForAdmin($orgId, $accessToken = null)
    {
        if (!$accessToken) {
            return $this->getById($orgId);
        }

        try {
            $endpoint = '/rest/v1/organizations?select=*&id=eq.' . rawurlencode((string) $orgId) . '&limit=1';
            $organizations = $this->supabase->makeRequest('GET', $endpoint, [], [
                'Authorization' => 'Bearer ' . $accessToken,
            ]);

            if (!is_array($organizations) || empty($organizations[0])) {
                return null;
            }

            return $this->normalizeImageUrl($organizations[0]);
        } catch (\Exception $e) {
            error_log('Error fetching organization for admin: ' . $e->getMessage());
            return null;
        }
    }

    public function getAll()
    {
        try {
            $response = $this->supabase->query('organizations', '*', ['is_active' => 'true']);
            if (!$response['success']) {
                return [];
            }

            $organizations = is_array($response['data']) ? $response['data'] : [];

            foreach ($organizations as &$organization) {
                if (!is_array($organization)) {
                    continue;
                }

                $organization = $this->normalizeImageUrl($organization);
            }

            return $this->sortOrganizationsByName($organizations);
        } catch (\Exception $e) {
            error_log('Error fetching organizations: ' . $e->getMessage());
            return [];
        }
    }

    public function getAllForAdmin($accessToken = null)
    {
        if (!$accessToken) {
            return $this->getAll();
        }

        try {
            $endpoint = '/rest/v1/organizations?select=*&order=name.asc';
            $organizations = $this->supabase->makeRequest('GET', $endpoint, [], [
                'Authorization' => 'Bearer ' . $accessToken,
            ]);

            if (!is_array($organizations)) {
                return [];
            }

            foreach ($organizations as &$organization) {
                if (!is_array($organization)) {
                    continue;
                }

                $organization = $this->normalizeImageUrl($organization);
            }

            return $this->sortOrganizationsByName($organizations);
        } catch (\Exception $e) {
            error_log('Error fetching admin organizations: ' . $e->getMessage());
            return [];
        }
    }

    public function create($data, $accessToken = null)
    {
        try {
            $orgData = array_merge($data, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'is_active' => $data['is_active'] ?? true
            ]);

            $response = $this->supabase->insert('organizations', $orgData, $accessToken);
            return $response['success'] ? ($response['data'][0]['id'] ?? true) : false;
        } catch (\Exception $e) {
            error_log('Error creating organization: ' . $e->getMessage());
            return false;
        }
    }

    public function update($orgId, $data, $accessToken = null)
    {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $response = $this->supabase->update('organizations', $orgId, $data, $accessToken);
            return $response['success'];
        } catch (\Exception $e) {
            error_log('Error updating organization: ' . $e->getMessage());
            return false;
        }
    }

    public function saveImageReference($orgId, $imageUrl, $accessToken = null)
    {
        $normalizedUrl = trim((string) $imageUrl);
        if ($normalizedUrl === '') {
            return false;
        }

        $candidateFields = ['image_url', 'image', 'logo_url'];

        foreach ($candidateFields as $field) {
            try {
                if ($this->update($orgId, [$field => $normalizedUrl], $accessToken)) {
                    return true;
                }
            } catch (\Exception $e) {
                error_log('Failed saving image reference to field ' . $field . ': ' . $e->getMessage());
            }
        }

        return false;
    }

    public function delete($orgId, $accessToken = null)
    {
        try {
            $response = $this->supabase->delete('organizations', $orgId, $accessToken);
            return !empty($response['success']);
        } catch (\Exception $e) {
            error_log('Error deleting organization: ' . $e->getMessage());
            return false;
        }
    }

    public function setActiveStatus($orgId, $isActive, $accessToken = null)
    {
        return $this->update($orgId, ['is_active' => (bool) $isActive], $accessToken);
    }

    public function getAdmin($orgId)
    {
        try {
            $userModel = new User();
            $admins = $userModel->getByOrganization($orgId);

            foreach ($admins as $admin) {
                $role = $userModel->getRole($admin['id']);
                if ($role && $role['name'] === 'org_admin') {
                    return $admin;
                }
            }

            return null;
        } catch (\Exception $e) {
            error_log('Error fetching org admin: ' . $e->getMessage());
            return null;
        }
    }

    public function exists($orgId)
    {
        $org = $this->getById($orgId);
        return $org !== null && $org['is_active'] === true;
    }

    public function getAllWithDetails()
    {
        try {
            $organizations = $this->getAll();
            return $this->enrichOrganizations($organizations);
        } catch (\Exception $e) {
            error_log('Error fetching organizations with details: ' . $e->getMessage());
            return [];
        }
    }

    public function searchActiveByTerm($query)
    {
        try {
            $term = trim((string) $query);
            if ($term === '') {
                return [];
            }

            $term = str_replace(['(', ')', ','], ' ', $term);
            $pattern = '*' . $term . '*';
            $orExpression = sprintf(
                '(name.ilike.%s,genre.ilike.%s,bio.ilike.%s)',
                $pattern,
                $pattern,
                $pattern
            );

            $endpoint = '/rest/v1/organizations?select=*'
                . '&is_active=eq.true'
                . '&or=' . urlencode($orExpression)
                . '&order=name.asc';

            $organizations = $this->supabase->makeRequest('GET', $endpoint, [], []);

            if (!is_array($organizations)) {
                return [];
            }

            foreach ($organizations as &$organization) {
                $organization = $this->normalizeImageUrl($organization);
            }

            return $this->sortOrganizationsByName($this->enrichOrganizations($organizations));
        } catch (\Exception $e) {
            error_log('Error searching organizations: ' . $e->getMessage());
            return [];
        }
    }

    private function sortOrganizationsByName(array $organizations)
    {
        usort($organizations, function ($left, $right) {
            return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        return $organizations;
    }

    public function getWithAcceptedBookings($orgId)
    {
        try {
            $org = $this->getById($orgId);
            if (!$org) {
                return null;
            }

            $org['admin'] = $this->getAdmin($orgId);

            $bookingModel = new BookingRequest();
            $allBookings = $bookingModel->getByOrganization($orgId);

            $org['accepted_bookings'] = array_filter($allBookings, function($b) {
                return $b['status'] === 'accepted';
            });

            return $org;
        } catch (\Exception $e) {
            error_log('Error fetching organization with bookings: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload org image to Supabase Storage and return public URL
     */
    public function uploadImage($orgId, $file)
    {
        if (empty($file['tmp_name']) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $uploadError = $file['error'] ?? null;
            error_log('Upload error code: ' . ($uploadError ?? 'no file'));

            $messages = [
                UPLOAD_ERR_INI_SIZE => 'Upload failed: file exceeds server upload_max_filesize limit.',
                UPLOAD_ERR_FORM_SIZE => 'Upload failed: file exceeds MAX_FILE_SIZE limit.',
                UPLOAD_ERR_PARTIAL => 'Upload failed: file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'Upload failed: no file was selected.',
                UPLOAD_ERR_NO_TMP_DIR => 'Upload failed: missing temporary upload directory on server.',
                UPLOAD_ERR_CANT_WRITE => 'Upload failed: server cannot write uploaded file to disk.',
                UPLOAD_ERR_EXTENSION => 'Upload failed: a PHP extension stopped the upload.',
            ];

            if (isset($messages[$uploadError])) {
                throw new \Exception($messages[$uploadError]);
            }

            throw new \Exception('Upload failed due to an unknown server upload error.');
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mimeType = mime_content_type($file['tmp_name']);

        error_log('Detected mime type: ' . $mimeType);

        if (!in_array($mimeType, $allowedTypes)) {
            error_log('Invalid mime type: ' . $mimeType);
            throw new \Exception('Invalid file type. Only JPG, PNG, GIF, WEBP allowed.');
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            error_log('File too large: ' . $file['size']);
            throw new \Exception('File too large. Maximum 2MB.');
        }

        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        $ext = $extensionMap[$mimeType] ?? strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }

        $path = 'org-' . (int) $orgId . '.' . $ext;
        $fileContent = file_get_contents($file['tmp_name']);

        error_log('Attempting upload to bucket: organization-images, path: ' . $path);
        $url = $this->supabase->uploadFile('organization-images', $path, $fileContent, $mimeType);
        error_log('Upload result URL: ' . $url);

        return $url;
    }

    private function enrichOrganizations(array $organizations)
    {
        $bookingModel = new BookingRequest();

        foreach ($organizations as &$org) {
            $org = $this->normalizeImageUrl($org);
            $org['admin'] = $this->getAdmin($org['id']);

            $bookings = $bookingModel->getByOrganization($org['id']);
            $org['bookings'] = $bookings;
            $org['upcoming_bookings_count'] = $this->getUpcomingAcceptedBookingsCount((int) ($org['id'] ?? 0), $bookings);
        }

        return $organizations;
    }

    private function getUpcomingAcceptedBookingsCount($orgId, array $fallbackBookings = [])
    {
        $orgId = (int) $orgId;
        if ($orgId <= 0) {
            return 0;
        }

        try {
            $today = date('Y-m-d');
            $endpoint = '/rest/v1/booking_requests?select=id'
                . '&organization_id=eq.' . rawurlencode((string) $orgId)
                . '&status=eq.accepted'
                . '&event_date=gte.' . rawurlencode($today);

            $rows = $this->supabase->adminRequest('GET', $endpoint, [], ['Prefer' => 'return=representation']);
            if (is_array($rows)) {
                return count($rows);
            }
        } catch (\Exception $e) {
            error_log('Falling back to role-scoped booking count for organization ' . $orgId . ': ' . $e->getMessage());
        }

        // Fallback for environments without service role key.
        return count(array_filter($fallbackBookings, function ($booking) {
            return ($booking['status'] ?? '') === 'accepted' && strtotime((string) ($booking['event_date'] ?? '')) >= time();
        }));
    }

    private function normalizeImageUrl(array $organization)
    {
        $rawImage = $organization['image_url']
            ?? $organization['image']
            ?? $organization['logo_url']
            ?? null;

        if (empty($rawImage) && !empty($organization['id'])) {
            $publicUrl = $this->getDeterministicPublicImageUrl((int) $organization['id']);
            if ($publicUrl) {
                $organization['image_url'] = $publicUrl;
                return $organization;
            }

            $rawImage = $this->getLatestUploadedImagePath((int) $organization['id']);
        }

        if (!empty($rawImage)) {
            $organization['image_url'] = $this->supabase->normalizeOrganizationImageUrl($rawImage);
        }

        return $organization;
    }

    private function getLatestUploadedImagePath($orgId)
    {
        $prefix = 'org-' . (int) $orgId . '-';
        return $this->supabase->findLatestStorageObjectByPrefix('organization-images', $prefix);
    }

    private function getDeterministicPublicImageUrl($orgId)
    {
        $orgId = (int) $orgId;
        if ($orgId <= 0) {
            return null;
        }

        $candidatePaths = [
            'org-' . $orgId . '.jpg',
            'org-' . $orgId . '.jpeg',
            'org-' . $orgId . '.png',
            'org-' . $orgId . '.webp',
            'org-' . $orgId . '.gif',
        ];

        return $this->supabase->findFirstExistingPublicStorageUrl('organization-images', $candidatePaths);
    }
}