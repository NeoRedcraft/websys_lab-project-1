<?php
/**
 * Get the actual auth user UUIDs from Supabase.
 * Run: php scripts/get_auth_uuids.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;

$supabase = Supabase::getInstance();

echo "=== Querying Auth Users ===\n\n";

$emails = [
    'jbavida@mymail.mapua.edu.ph',
    'chaylesantiago@mymail.mapua.edu.ph',
];

foreach ($emails as $email) {
    echo "Querying: {$email}\n";
    
    try {
        $response = $supabase->adminRequest('GET', '/auth/v1/admin/users?filter=' . urlencode("email=$email"));
        
        echo "  Response type: " . gettype($response) . "\n";
        echo "  JSON: " . json_encode($response) . "\n";
        
        if (is_array($response) && isset($response['users']) && is_array($response['users'])) {
            foreach ($response['users'] as $user) {
                echo "    [✓] Found: {$user['id']}\n";
                echo "        Email: {$user['email']}\n";
            }
        } elseif (is_array($response) && isset($response[0])) {
            // Response is array of users
            foreach ($response as $user) {
                if (isset($user['id'])) {
                    echo "    [✓] Found: {$user['id']}\n";
                    $userEmail = isset($user['email']) ? $user['email'] : 'N/A';
                    echo "        Email: {$userEmail}\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "  [✗] Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}
