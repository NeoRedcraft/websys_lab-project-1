<?php
/**
 * Reset passwords for existing auth users.
 * Run: php scripts/reset_auth_passwords.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;
use App\Models\User;

$supabase = Supabase::getInstance();
$userModel = new User();

echo "=== Resetting Auth User Passwords ===\n\n";

$users = [
    'jbavida@mymail.mapua.edu.ph',
    'chaylesantiago@mymail.mapua.edu.ph',
];

foreach ($users as $email) {
    echo "Resetting: {$email}\n";
    
    // Get user from users_extended to get the UUID
    $userRecord = $userModel->getByEmail($email);
    if (!$userRecord) {
        echo "  [✗] User not found in users_extended\n\n";
        continue;
    }
    
    $userId = $userRecord['id'];
    echo "  UUID: {$userId}\n";
    
    try {
        $response = $supabase->adminRequest('PUT', "/auth/v1/admin/users/{$userId}", [
            'password' => 'ChangeMe2025!',
        ]);
        
        if (isset($response['id'])) {
            echo "  [✓] Password reset successfully\n";
        } else {
            echo "  [✗] Error: " . json_encode($response) . "\n";
        }
    } catch (\Exception $e) {
        echo "  [✗] Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Passwords Reset ===\n";
echo "Try signing in now:\n";
echo "  Email: jbavida@mymail.mapua.edu.ph\n";
echo "  Password: ChangeMe2025!\n";
