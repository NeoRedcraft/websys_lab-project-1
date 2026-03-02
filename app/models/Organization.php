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
            return $response['success'] ? $response['data'][0] ?? null : null;
        } catch (\Exception $e) {
            error_log('Error fetching organization: ' . $e->getMessage());
            return null;
        }
    }

    public function getAll()
    {
        try {
            $response = $this->supabase->query('organizations', '*', ['is_active' => 'true']);
            return $response['success'] ? $response['data'] : [];
        } catch (\Exception $e) {
            error_log('Error fetching organizations: ' . $e->getMessage());
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

    public function delete($orgId, $accessToken = null)
    {
        try {
            return $this->update($orgId, ['is_active' => false], $accessToken);
        } catch (\Exception $e) {
            error_log('Error deleting organization: ' . $e->getMessage());
            return false;
        }
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

            return $this->enrichOrganizations($organizations);
        } catch (\Exception $e) {
            error_log('Error searching organizations: ' . $e->getMessage());
            return [];
        }
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
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            error_log('Upload error code: ' . ($file['error'] ?? 'no file'));
            return null;
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

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $path = "org-{$orgId}-" . time() . '.' . $ext;
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
            $org['admin'] = $this->getAdmin($org['id']);

            $bookings = $bookingModel->getByOrganization($org['id']);
            $org['bookings'] = $bookings;
            $org['upcoming_bookings_count'] = count(array_filter($bookings, function($booking) {
                return $booking['status'] === 'accepted' && strtotime($booking['event_date']) >= time();
            }));
        }

        return $organizations;
    }
}