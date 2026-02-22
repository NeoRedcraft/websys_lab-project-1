<?php
/**
 * Check all org_admin users and their organization assignments.
 * Run: php scripts/check_org_admins.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Models\User;
use App\Utils\Supabase;

$userModel = new User();
$supabase = Supabase::getInstance();

echo "=== Checking All Org_Admin Users ===\n\n";

try {
    // Get all users with org_admin role (role_id = 2)
    $response = $supabase->query('users_extended', '*', ['role_id' => 2]);
    
    if (!$response['success']) {
        echo "[✗] Error querying users_extended: {$response['error']}\n";
        exit(1);
    }
    
    $users = $response['data'];
    
    if (empty($users)) {
        echo "[✓] No org_admin users found\n";
        exit(0);
    }
    
    echo "Found " . count($users) . " org_admin user(s):\n\n";
    
    foreach ($users as $user) {
        echo "- {$user['email']}\n";
        echo "  UUID: {$user['id']}\n";
        echo "  Org ID: " . ($user['org_id'] ? $user['org_id'] : '(none)') . "\n";
        
        if (!$user['org_id']) {
            echo "  [⚠] WARNING: No organization assigned - user will get 403 error\n";
        } else {
            echo "  [✓] Assigned to organization\n";
        }
        echo "\n";
    }
    
    echo "=== Next Steps ===\n";
    echo "For any org_admin with no org_id, update:\n";
    echo "  UPDATE users_extended SET org_id = <org_id> WHERE id = '<user_uuid>';\n";
    
} catch (\Exception $e) {
    echo "[✗] Exception: " . $e->getMessage() . "\n";
}
