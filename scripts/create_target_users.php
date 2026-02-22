<?php
/**
 * Create the target auth users fresh.
 * Run: php scripts/create_target_users.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;

$supabase = Supabase::getInstance();

echo "=== Creating Target Auth Users ===\n\n";

$users = [
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
    [
        'email' => 'esoquidong@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'name' => 'Elaiza Quidong',
    ],
    [
        'email' => 'jtcjasmin@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'name' => 'Jasmine Taye Jasmin',
    ],
    [
        'email' => 'inageonanga@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'name' => 'Irish Nicole Geonaga',
    ],
    [
        'email' => 'rrbalajadia@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'name' => 'Regie Balajadia',
    ],
    [
        'email' => 'scmgutierrez@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'name' => 'Sean Gutierrez',
    ],
    [
        'email' => 'mgfasasis@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'name' => 'Felicity Sasis',
    ],
    [
        'email' => 'jalsantos@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'name' => 'Jade Santos',
    ],
];

foreach ($users as $user) {
    echo "Creating: {$user['email']}\n";
    
    try {
        $response = $supabase->adminRequest('POST', '/auth/v1/admin/users', [
            'email' => $user['email'],
            'password' => $user['password'],
            'user_metadata' => [
                'full_name' => $user['name'],
                'name' => $user['name'],
            ],
            'email_confirm' => true,
        ]);
        
        if (isset($response['id'])) {
            echo "  [✓] Created with UUID: {$response['id']}\n";
        } elseif (is_array($response) && isset($response['code'])) {
            echo "  [✗] Error: {$response['message']}\n";
        } else {
            echo "  [✗] Unexpected response: " . json_encode($response) . "\n";
        }
    } catch (\Exception $e) {
        echo "  [✗] Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Auth Users Created ===\n";
echo "Now try: php scripts/test_signin.php\n";
