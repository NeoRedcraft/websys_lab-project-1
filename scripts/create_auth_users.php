<?php
/**
 * Create auth users in Supabase using admin API.
 * Run: php scripts/create_auth_users.php
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
            'email_confirm' => true,  // Auto-confirm email
        ]);
        
        if (isset($response['id'])) {
            echo "  [✓] Created successfully\n";
            echo "    UUID: {$response['id']}\n";
            echo "    Email: {$response['email']}\n";
        } else {
            echo "  [✗] Error: " . json_encode($response) . "\n";
        }
    } catch (\Exception $e) {
        echo "  [✗] Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Auth Users Created ===\n";
echo "Now try signing in with these credentials:\n";
echo "  Email: jbavida@mymail.mapua.edu.ph\n";
echo "  Password: ChangeMe2025!\n";
echo "  Expected redirect: /admin/dashboard\n\n";
echo "  Email: chaylesantiago@mymail.mapua.edu.ph\n";
echo "  Password: ChangeMe2025!\n";
echo "  Expected redirect: /org-admin/dashboard\n";
