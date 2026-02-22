<?php
require 'vendor/autoload.php';

if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

use App\Models\User;
use App\Utils\Supabase;

// Load environment variables from the root directory
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = Supabase::getInstance();
$userModel = new User();

// Define users with their specific passwords
$testUsers = [
    'egmlumabi@mymail.mapua.edu.ph' => '123456', 
    'czbmanuel@mymail.mapua.edu.ph' => '12345678'
];

foreach ($testUsers as $email => $password) {
    echo "--- Testing Organizer: $email ---\n";

    // 1. Attempt SignIn via Supabase Auth
    $auth = $supabase->signIn($email, $password);

    if ($auth['success']) {
        $userId = $auth['data']['user']['id'];
        echo "[✓] Auth Success! UUID: $userId\n";

        // 2. Check Role Mapping from public.users_extended
        $roleData = $userModel->getRole($userId);
        
        // Ensure we handle the structure returned by your User model
        $roleName = $roleData['role_name'] ?? 'NULL';

        echo "[✓] Role detected: $roleName\n";

        // 3. Test Expected Redirect Logic
        // Since you are a student/organizer, this should resolve to 'organizer'
        if ($roleName === 'organizer') {
            echo "[PASS] User correctly identified as Organizer.\n";
            echo "[→] Logic would redirect to: /organizer-dashboard\n";
        } else {
            echo "[FAIL] Expected 'organizer', got '$roleName'.\n";
        }
    } else {
        // This will trigger if the password mapped above is incorrect
        echo "[✗] Auth Failed: " . ($auth['error_description'] ?? $auth['error'] ?? 'Invalid login credentials') . "\n";
    }
    echo "\n";
}