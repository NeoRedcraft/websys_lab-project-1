<?php
/**
 * Insert users into users_extended with the correct auth UUIDs.
 * Run: php scripts/insert_users_extended.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;

$supabase = Supabase::getInstance();

echo "=== Inserting into users_extended ===\n\n";

$now = date('Y-m-d H:i:s');

$users = [
    [
        'id' => '5b6c1966-cd33-4d77-bc49-962dead44d04',
        'email' => 'jbavida@mymail.mapua.edu.ph',
        'full_name' => 'John Benedict Vida',
        'role_id' => 1,  // system_admin
        'org_id' => null,
        'is_active' => true,
    ],
    [
        'id' => '73df4eb8-6a2a-4149-8775-d1fa5786fd1b',
        'email' => 'chaylesantiago@mymail.mapua.edu.ph',
        'full_name' => 'Chayle Santiago',
        'role_id' => 2,  // org_admin
        'org_id' => null,
        'is_active' => true,
    ],
];

foreach ($users as $user) {
    echo "Inserting: {$user['email']}\n";
    echo "  UUID: {$user['id']}\n";
    echo "  Role: {$user['role_id']}\n";
    
    try {
        // Use admin request to bypass RLS
        $response = $supabase->adminRequest('POST', '/rest/v1/users_extended', $user);
        
        if (isset($response[0]) || isset($response['id'])) {
            echo "  [✓] Inserted\n";
        } else {
            echo "  [✗] Error: " . json_encode($response) . "\n";
        }
    } catch (\Exception $e) {
        echo "  [✗] Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Test the signin again ===\n";
echo "php scripts/test_signin.php\n";
