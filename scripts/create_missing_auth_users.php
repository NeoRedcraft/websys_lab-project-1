<?php
/**
 * Create missing auth users for org_admins.
 * Run: php scripts/create_missing_auth_users.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;
use App\Models\User;

$supabase = Supabase::getInstance();
$userModel = new User();

echo "=== Creating Missing Auth Users ===\n\n";

// Get all org_admins
$response = $supabase->query('users_extended', '*', ['role_id' => 2]);

if (!$response['success']) {
    echo "[✗] Error querying users_extended: {$response['error']}\n";
    exit(1);
}

$orgAdmins = $response['data'];

echo "Found " . count($orgAdmins) . " org_admins in users_extended\n\n";

foreach ($orgAdmins as $user) {
    $email = $user['email'];
    $name = $user['full_name'];
    
    echo "Creating auth user: {$email}\n";
    
    try {
        $response = $supabase->adminRequest('POST', '/auth/v1/admin/users', [
            'id' => $user['id'],  // Use the same UUID from users_extended
            'email' => $email,
            'password' => 'ChangeMe2025!',
            'user_metadata' => [
                'full_name' => $name,
                'name' => $name,
            ],
            'email_confirm' => true,
        ]);
        
        if (isset($response['id'])) {
            echo "  [✓] Created\n";
        } elseif (is_array($response) && isset($response['code'])) {
            if ($response['code'] === '23505') {
                echo "  [↻] Already exists (skipped)\n";
            } else {
                echo "  [✗] Error: {$response['message']}\n";
            }
        } else {
            echo "  [✗] Unexpected error: " . json_encode($response) . "\n";
        }
    } catch (\Exception $e) {
        echo "  [✗] Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Done ===\n";
echo "All org_admins now have auth accounts.\n";
echo "Password for all: ChangeMe2025!\n";
