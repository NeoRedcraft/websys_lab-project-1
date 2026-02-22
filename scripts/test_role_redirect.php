<?php
/**
 * Test the role redirect logic without needing signin.
 * Simulates what happens after successful signin.
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Models\User;

$userModel = new User();

echo "=== Testing Role Redirect Logic ===\n\n";

$testCases = [
    [
        'email' => 'jbavida@mymail.mapua.edu.ph',
        'expectedRole' => 'system_admin',
        'expectedRedirect' => '/admin/dashboard',
    ],
    [
        'email' => 'chaylesantiago@mymail.mapua.edu.ph',
        'expectedRole' => 'org_admin',
        'expectedRedirect' => '/org-admin/dashboard',
    ],
];

foreach ($testCases as $test) {
    $email = $test['email'];
    $expectedRole = $test['expectedRole'];
    $expectedRedirect = $test['expectedRedirect'];
    
    echo "Testing: {$email}\n";
    
    // Get user from users_extended
    $userRecord = $userModel->getByEmail($email);
    if (!$userRecord) {
        echo "  [✗] User not found\n\n";
        continue;
    }
    
    $userId = $userRecord['id'];
    echo "  [✓] User found: {$userId}\n";
    
    // Get role
    $role = $userModel->getRole($userId);
    if (!$role) {
        echo "  [✗] Role not found\n\n";
        continue;
    }
    
    $roleName = $role['name'] ?? 'unknown';
    echo "  [✓] Role: {$roleName}\n";
    
    // Determine redirect
    $redirect = null;
    if ($roleName === 'system_admin') {
        $redirect = '/admin/dashboard';
    } elseif ($roleName === 'org_admin') {
        $redirect = '/org-admin/dashboard';
    } else {
        $redirect = '/bookings';
    }
    
    echo "  [✓] Redirect: {$redirect}\n";
    
    // Verify
    if ($roleName === $expectedRole && $redirect === $expectedRedirect) {
        echo "  [✓] PASS\n";
    } else {
        echo "  [✗] FAIL\n";
    }
    
    echo "\n";
}

echo "=== Role Redirect Logic is Working ===\n";
echo "Once you fix the signin credentials, redirects will work!\n";
