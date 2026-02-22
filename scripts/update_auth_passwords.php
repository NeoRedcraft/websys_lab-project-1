<?php
/**
 * Update passwords for existing auth users by UUID.
 * Run: php scripts/update_auth_passwords.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;
use App\Models\User;

$supabase = Supabase::getInstance();
$userModel = new User();

echo "=== Updating Auth User Passwords ===\n\n";

$emails = [
    'jbavida@mymail.mapua.edu.ph',
    'chaylesantiago@mymail.mapua.edu.ph',
];

foreach ($emails as $email) {
    echo "Updating: {$email}\n";
    
    // Get UUID from users_extended
    $userRecord = $userModel->getByEmail($email);
    if (!$userRecord) {
        echo "  [✗] User not found in users_extended\n\n";
        continue;
    }
    
    $userId = $userRecord['id'];
    echo "  UUID: {$userId}\n";
    
    // Update password
    try {
        $response = $supabase->adminRequest('PUT', "/auth/v1/admin/users/{$userId}", [
            'password' => 'ChangeMe2025!',
        ]);
        
        if (isset($response['id'])) {
            echo "  [✓] Password updated\n";
        } elseif (is_array($response) && isset($response['code'])) {
            echo "  [✗] Error: {$response['message']}\n";
        } else {
            echo "  [✗] Error: " . json_encode($response) . "\n";
        }
    } catch (\Exception $e) {
        echo "  [✗] Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Password Updates Complete ===\n";
echo "Now test signin again:\n";
echo "  php scripts/test_signin.php\n";
