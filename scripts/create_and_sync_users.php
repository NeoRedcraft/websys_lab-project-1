<?php
/**
 * Create auth users and get their UUIDs.
 * Run: php scripts/create_and_sync_users.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;

$supabase = Supabase::getInstance();

echo "=== Creating Auth Users ===\n\n";

$usersToCreate = [
    [
        'email' => 'jbavida@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'name' => 'John Benedict Vida',
    ],
    [
        'email' => 'chaylesantiago@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'name' => 'Chayle Santiago',
    ],
];

$createdUsers = [];

foreach ($usersToCreate as $user) {
    $email = $user['email'];
    $password = $user['password'];
    $name = $user['name'];
    
    echo "Creating: {$email}\n";
    
    try {
        $response = $supabase->adminRequest('POST', '/auth/v1/admin/users', [
            'email' => $email,
            'password' => $password,
            'user_metadata' => [
                'full_name' => $name,
                'name' => $name,
            ],
            'email_confirm' => true,
        ]);
        
        if (isset($response['id'])) {
            echo "  [✓] Created\n";
            echo "    Auth UUID: {$response['id']}\n";
            
            $createdUsers[$email] = $response['id'];
        } else {
            echo "  [✗] Error: " . json_encode($response) . "\n";
        }
    } catch (\Exception $e) {
        echo "  [✗] Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Created Users ===\n";
foreach ($createdUsers as $email => $uuid) {
    echo "{$email} = {$uuid}\n";
}

echo "\n=== Next Steps ===\n";
echo "Update users_extended with the auth UUIDs:\n\n";

foreach ($createdUsers as $email => $uuid) {
    echo "UPDATE users_extended SET id = '{$uuid}' WHERE email = '{$email}';\n";
}

echo "\nOr run: php scripts/test_signin.php\n";
