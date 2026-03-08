<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

require __DIR__ . '/../app/helpers.php';

use App\Utils\Supabase;

function decodeAuditJson($value)
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

try {
    $supabase = Supabase::getInstance();

    $organizations = $supabase->makeRequest('GET', '/rest/v1/organizations?select=*&order=id.asc', [], []);
    if (!is_array($organizations)) {
        throw new RuntimeException('Failed to fetch organizations list.');
    }

    $updatedCount = 0;
    $unchangedCount = 0;
    $skippedCount = 0;

    foreach ($organizations as $org) {
        $orgId = $org['id'] ?? null;
        $orgName = (string) ($org['name'] ?? 'Unknown');

        if (!$orgId) {
            $skippedCount++;
            continue;
        }

        $logsEndpoint = '/rest/v1/audit_logs?select=id,action,created_at,new_values&entity_type=eq.organization&entity_id=eq.'
            . rawurlencode((string) $orgId)
            . '&order=created_at.desc&limit=50';

        $logs = $supabase->adminRequest('GET', $logsEndpoint, [], ['Prefer' => 'return=representation']);
        if (!is_array($logs) || empty($logs)) {
            $skippedCount++;
            continue;
        }

        $targetBio = null;
        $foundBioEntry = false;

        foreach ($logs as $log) {
            $action = strtolower((string) ($log['action'] ?? ''));
            if (!in_array($action, ['updated', 'profile_updated'], true)) {
                continue;
            }

            $newValues = decodeAuditJson($log['new_values'] ?? null);
            if (!is_array($newValues) || !array_key_exists('bio', $newValues)) {
                continue;
            }

            $targetBio = (string) ($newValues['bio'] ?? '');
            $foundBioEntry = true;
            break;
        }

        if (!$foundBioEntry) {
            $legacyDescription = isset($org['description']) ? trim((string) $org['description']) : '';
            if ($legacyDescription !== '') {
                $targetBio = $legacyDescription;
                $foundBioEntry = true;
            }
        }

        if (!$foundBioEntry) {
            $skippedCount++;
            continue;
        }

        $currentBio = (string) ($org['bio'] ?? '');
        if ($currentBio === $targetBio) {
            $unchangedCount++;
            continue;
        }

        $payload = [
            'bio' => $targetBio,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $updateEndpoint = '/rest/v1/organizations?id=eq.' . rawurlencode((string) $orgId);
        $supabase->adminRequest('PATCH', $updateEndpoint, $payload, ['Prefer' => 'return=representation']);

        $updatedCount++;
        echo "[UPDATED] Org #{$orgId} {$orgName}\n";
    }

    echo "\nSync complete.\n";
    echo "Updated: {$updatedCount}\n";
    echo "Unchanged: {$unchangedCount}\n";
    echo "Skipped: {$skippedCount}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Sync failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
