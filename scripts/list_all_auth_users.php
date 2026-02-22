<?php
/**
 * Get ALL auth users from Supabase and find the ones we care about.
 * Run: php scripts/list_all_auth_users.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;

$supabase = Supabase::getInstance();

echo "=== Listing All Auth Users ===\n\n";

try {
    $response = $supabase->adminRequest('GET', '/auth/v1/admin/users');
    
    if (is_array($response) && isset($response['users'])) {
        $users = $response['users'];
        echo "Total users: " . count($users) . "\n\n";
        
        echo "=== All Users ===\n";
        foreach ($users as $user) {
            echo "- {$user['email']} (ID: {$user['id']})\n";
        }
        
        echo "\n=== Looking for targets ===\n";
        
        $targetEmails = [
            'jbavida@mymail.mapua.edu.ph',
            'chaylesantiago@mymail.mapua.edu.ph',
        ];
        
        foreach ($users as $user) {
            if (in_array($user['email'], $targetEmails)) {
                echo "✓ FOUND: {$user['email']}\n";
                echo "  UUID: {$user['id']}\n";
                echo "  Confirmed: " . ($user['email_confirmed_at'] ? 'YES' : 'NO') . "\n";
                echo "\n";
            }
        }
        
        if (count(array_filter($users, function($u) { return in_array($u['email'], $targetEmails); })) === 0) {
            echo "✗ Target users NOT found in auth.users\n";
        }
    } else {
        echo "Response:\n";
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "[✗] Exception: " . $e->getMessage() . "\n";
}
