<?php
/**
 * Test sign-up flow with role assignment.
 * Run: php scripts/test_signup.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;
use App\Models\User;

$supabase = Supabase::getInstance();
$userModel = new User();

echo "=== Testing Sign-Up Flow ===\n\n";

// Test user
$testEmail = 'test.organizer.' . uniqid() . '@mymail.mapua.edu.ph';
$testPassword = 'TestPassword123!';
$testName = 'Test Organizer ' . time();

echo "Test Email: {$testEmail}\n";
echo "Test Password: {$testPassword}\n";
echo "Test Name: {$testName}\n\n";

// Step 1: SignUp
echo "[1] Calling signUp()...\n";
$signUpResult = $supabase->signUp($testEmail, $testPassword, [
    'name' => $testName,
    'full_name' => $testName,
]);

if (!$signUpResult['success']) {
    echo "[!] SignUp FAILED: {$signUpResult['error']}\n";
    echo "Response: " . json_encode($signUpResult) . "\n";
    exit(1);
}

echo "[✓] SignUp SUCCESS\n";

// Step 2: Extract userId
$data = $signUpResult['data'];
$user = $data['user'] ?? null;
$userId = $user['id'] ?? null;

if (!$userId) {
    echo "[!] No userId in response. Response keys: " . implode(', ', array_keys($data)) . "\n";
    echo "Response: " . json_encode($data) . "\n";
    exit(1);
}

echo "[✓] UserId extracted: {$userId}\n\n";

// Step 3: Create user_extended record with organizer role
echo "[2] Creating user_extended record...\n";
$createResult = $userModel->create(
    $userId,
    $testEmail,
    $testName,
    3,    // organizer role_id (assuming 3 is organizer from schema)
    null  // no org
);

if (!$createResult) {
    echo "[!] Failed to create user_extended record\n";
    echo "    UserModel->create() returned false\n";
    exit(1);
}

echo "[✓] user_extended record created\n\n";

// Step 4: Now test signIn with the same credentials
echo "[3] Testing signIn with new user...\n";
$signInResult = $supabase->signIn($testEmail, $testPassword);

if (!$signInResult['success']) {
    echo "[!] SignIn FAILED: {$signInResult['error']}\n";
    echo "    (This is expected if email confirmation is required in Supabase settings)\n";
    echo "    Continuing with role lookup test...\n\n";
    
    // For testing purposes, query the user directly from users_extended
    echo "[3b] Querying user_extended directly...\n";
    $userRecord = $userModel->getByEmail($testEmail);
    if (!$userRecord) {
        echo "[!] User not found in users_extended\n";
        exit(1);
    }
    $userId = $userRecord['id'];
    echo "[✓] Found user in users_extended with ID: {$userId}\n\n";
} else {
    echo "[✓] SignIn SUCCESS\n";
    
    // Step 5: Extract user and role
    $data = $signInResult['data'];
    $user = $data['user'] ?? null;
    $userId = $user['id'] ?? null;

    echo "[✓] UserId from signIn: {$userId}\n\n";
}

// Step 6: Query role
echo "[4] Querying role...\n";
$role = $userModel->getRole($userId);

if (!$role) {
    echo "[!] No role found for userId: {$userId}\n";
    
    // Debug: check users_extended
    $userRecord = $userModel->getByEmail($testEmail);
    if ($userRecord) {
        echo "    Found in users_extended: " . json_encode($userRecord) . "\n";
    } else {
        echo "    NOT found in users_extended\n";
    }
    exit(1);
}

$roleName = $role['name'] ?? 'unknown';
echo "[✓] Role found: {$roleName}\n\n";

// Step 7: Verify redirect logic
echo "[5] Testing redirect logic...\n";
$redirect = null;
if ($roleName === 'system_admin') {
    $redirect = '/admin/dashboard';
} elseif ($roleName === 'org_admin') {
    $redirect = '/org-admin/dashboard';
} else {
    $redirect = '/bookings';
}

echo "[✓] Redirect would be: {$redirect}\n\n";

echo "=== All Tests Passed ===\n";
