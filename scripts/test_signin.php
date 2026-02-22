<?php
/**
 * Test sign-in flow for registered org-admin and system_admin users.
 * Run: php scripts/test_signin.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;
use App\Models\User;

$supabase = Supabase::getInstance();
$userModel = new User();

// Test users (adjust emails as needed)
$testUsers = [
    [
        'email' => 'jbavida@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'expectedRole' => 'system_admin',
    ],
    [
        'email' => 'chaylesantiago@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'expectedRole' => 'org_admin',
    ],
    [
        'email' => 'esoquidong@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'expectedRole' => 'org_admin',
    ],
    [
        'email' => 'jtcjasmin@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'expectedRole' => 'org_admin',
    ],
    [
        'email' => 'inageonanga@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'expectedRole' => 'org_admin',
    ],
    [
        'email' => 'rrbalajadia@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'expectedRole' => 'org_admin',
    ],
    [
        'email' => 'scmgutierrez@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'expectedRole' => 'org_admin',
    ],
    [
        'email' => 'mgfasasis@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'expectedRole' => 'org_admin',
    ],
    [
        'email' => 'jalsantos@mymail.mapua.edu.ph',
        'password' => 'ChangeMe2025!',
        'expectedRole' => 'org_admin',
    ],
];

echo "=== Testing Sign-In Flow ===\n";
echo "Supabase Available: " . ($supabase->isAvailable() ? 'YES' : 'NO') . "\n";
echo "Supabase URL: " . env('SUPABASE_URL') . "\n";
echo "\n";

foreach ($testUsers as $testUser) {
    $email = $testUser['email'];
    $password = $testUser['password'];
    $expectedRole = $testUser['expectedRole'];

    echo "Testing: {$email}\n";
    echo "Expected Role: {$expectedRole}\n";

    // Step 1: SignIn
    echo "  [1] Calling signIn()...\n";
    $signInResult = $supabase->signIn($email, $password);

    if (!$signInResult['success']) {
        echo "  [!] SignIn FAILED: {$signInResult['error']}\n";
        // Dump full result for debugging
        echo "      Full response: " . json_encode($signInResult) . "\n";
        echo "\n";
        continue;
    }

    echo "  [✓] SignIn SUCCESS\n";

    // Step 2: Extract userId
    $data = $signInResult['data'];
    $user = $data['user'] ?? null;
    $userId = $user['id'] ?? null;

    if (!$userId) {
        echo "  [!] No userId in response. Response keys: " . implode(', ', array_keys($data)) . "\n";
        if ($user) {
            echo "      User keys: " . implode(', ', array_keys($user)) . "\n";
        }
        echo "\n";
        continue;
    }

    echo "  [✓] UserId extracted: {$userId}\n";

    // Step 3: Query role
    echo "  [2] Calling userModel->getRole()...\n";
    $role = $userModel->getRole($userId);

    if (!$role) {
        echo "  [!] No role found for userId: {$userId}\n";
        echo "      The user may not be in users_extended table.\n";
        
        // Debug: try to query users_extended directly
        echo "  [DEBUG] Querying users_extended for user {$email}...\n";
        $userRecord = $userModel->getByEmail($email);
        if ($userRecord) {
            echo "      Found: " . json_encode($userRecord) . "\n";
            echo "      role_id: {$userRecord['role_id']}\n";
        } else {
            echo "      NOT found in users_extended\n";
        }
        
        echo "\n";
        continue;
    }

    $roleName = $role['name'] ?? 'unknown';
    echo "  [✓] Role found: {$roleName}\n";

    // Step 4: Verify redirect logic
    $redirect = null;
    if ($roleName === 'system_admin') {
        $redirect = '/admin/dashboard';
    } elseif ($roleName === 'org_admin') {
        $redirect = '/org-admin/dashboard';
    } else {
        $redirect = '/bookings';
    }

    echo "  [3] Redirect would be: {$redirect}\n";

    if ($roleName === $expectedRole) {
        echo "  [✓] PASS: Role matches expected\n";
    } else {
        echo "  [!] FAIL: Expected {$expectedRole}, got {$roleName}\n";
    }

    echo "\n";
}

echo "=== Test Complete ===\n";
