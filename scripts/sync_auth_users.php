<?php
/**
 * Create auth users with matching UUIDs from users_extended.
 * Run: php scripts/sync_auth_users.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;
use App\Models\User;

$supabase = Supabase::getInstance();
$userModel = new User();

echo "=== Syncing UUIDs from users_extended to auth.users ===\n\n";

$emails = [
    'jbavida@mymail.mapua.edu.ph',
    'chaylesantiago@mymail.mapua.edu.ph',
];

foreach ($emails as $email) {
    echo "Processing: {$email}\n";
    
    // Get UUID from users_extended
    $userRecord = $userModel->getByEmail($email);
    if (!$userRecord) {
        echo "  [✗] User not found in users_extended\n\n";
        continue;
    }
    
    $userId = $userRecord['id'];
    $fullName = $userRecord['full_name'];
    
    echo "  UUID: {$userId}\n";
    echo "  Name: {$fullName}\n";
    
    // Create auth user with matching UUID
    try {
        $response = $supabase->adminRequest('POST', '/auth/v1/admin/users', [
            'id' => $userId,  // Use the same UUID as in users_extended
            'email' => $email,
            'password' => 'ChangeMe2025!',
            'user_metadata' => [
                'full_name' => $fullName,
                'name' => $fullName,
            ],
            'email_confirm' => true,  // Auto-confirm so they can signin immediately
        ]);
        
        if (isset($response['id'])) {
            echo "  [✓] Created in auth.users\n";
            echo "    Auth UUID: {$response['id']}\n";
            
            // Verify UUIDs match
            if ($response['id'] === $userId) {
                echo "    [✓] UUIDs match!\n";
            } else {
                echo "    [✗] UUID mismatch!\n";
            }
        } else {
            echo "  [✗] Error: " . json_encode($response) . "\n";
        }
    } catch (\Exception $e) {
        echo "  [✗] Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Sync Complete ===\n";
echo "Now test signin:\n";
echo "  php scripts/test_signin.php\n";
