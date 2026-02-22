<?php
/**
 * Update org_admin user with an organization.
 * Run: php scripts/assign_org_admin.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;
use App\Models\User;
use App\Models\Organization;

$supabase = Supabase::getInstance();
$userModel = new User();
$orgModel = new Organization();

echo "=== Assigning Organization to org_admin ===\n\n";

// Step 1: Get or create an organization
echo "[1] Checking for organizations...\n";
$orgs = $orgModel->getAll();
$org = null;

if (empty($orgs)) {
    echo "  [i] No organizations found, creating one...\n";
    
    $response = $supabase->insert('organizations', [
        'name' => 'Cardinal Stage',
        'bio' => 'Main organization',
        'is_active' => true,
    ]);
    
    if ($response['success']) {
        $org = $response['data'][0] ?? $response['data'];
        echo "  [✓] Organization created: {$org['id']}\n";
    } else {
        echo "  [✗] Failed to create organization\n";
        exit(1);
    }
} else {
    $org = $orgs[0];
    echo "  [✓] Found organization: {$org['id']} ({$org['name']})\n";
}

$orgId = $org['id'];

echo "\n[2] Updating org_admin user...\n";

// Step 2: Update org_admin with organization
$userRecord = $userModel->getByEmail('chaylesantiago@mymail.mapua.edu.ph');
if (!$userRecord) {
    echo "  [✗] org_admin user not found\n";
    exit(1);
}

$userId = $userRecord['id'];
echo "  User ID: {$userId}\n";
echo "  Setting org_id to: {$orgId}\n";

$updateResult = $userModel->update($userId, ['org_id' => $orgId]);

if ($updateResult) {
    echo "  [✓] Updated successfully\n";
} else {
    echo "  [✗] Update failed\n";
    exit(1);
}

echo "\n=== Assignment Complete ===\n";
echo "Now test signin:\n";
echo "  php scripts/test_signin.php\n";
