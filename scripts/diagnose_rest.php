<?php
/**
 * Detailed diagnostic of Supabase connectivity and data retrieval.
 * Run: php scripts/diagnose_rest.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;
use App\Models\User;

$supabase = Supabase::getInstance();
$userModel = new User();

echo "=== Supabase REST API Diagnostic ===\n\n";

// Test 1: Check Supabase connection
echo "[1] Checking Supabase URL and Keys\n";
$url = $supabase->getUrl();
$key = $supabase->getPublicKey();
echo "  URL: {$url}\n";
echo "  Key: " . substr($key, 0, 20) . "...\n";
echo "  Available: " . ($supabase->isAvailable() ? 'YES' : 'NO') . "\n\n";

// Test 2: Try raw REST query
echo "[2] Testing raw Supabase REST query\n";
$testEmails = [
    'jbavida@mymail.mapua.edu.ph',
    'chaylesantiago@mymail.mapua.edu.ph',
];

foreach ($testEmails as $email) {
    echo "  Query for: {$email}\n";
    
    try {
        $response = $supabase->query('users_extended', '*', ['email' => $email]);
        
        if ($response['success']) {
            $data = $response['data'];
            echo "    Data type: " . gettype($data) . "\n";
            echo "    JSON: " . json_encode($data) . "\n";
            
            if (is_array($data) && !empty($data)) {
                echo "    Array keys: " . implode(", ", array_keys($data)) . "\n";
            }
        } else {
            echo "    Error: {$response['error']}\n";
        }
    } catch (\Exception $e) {
        echo "    Exception: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// Test 3: Try the Model method
echo "[3] Testing User Model methods\n";
foreach ($testEmails as $email) {
    echo "  getByEmail('{$email}')\n";
    $result = $userModel->getByEmail($email);
    if ($result) {
        echo "    Found: {$result['email']}\n";
    } else {
        echo "    Not found\n";
    }
}

echo "\n";

// Test 4: Try signin
echo "[4] Testing SignIn\n";
$signinResult = $supabase->signIn('jbavida@mymail.mapua.edu.ph', 'ChangeMe2025!');
if ($signinResult['success']) {
    echo "  SignIn: SUCCESS\n";
    $data = $signinResult['data'];
    echo "  User ID: " . $data['user']['id'] . "\n";
    echo "  Email: " . $data['user']['email'] . "\n";
} else {
    echo "  SignIn: FAILED\n";
    echo "  Error: {$signinResult['error']}\n";
}

echo "\n=== Troubleshooting ===\n";
echo "If REST queries return empty:\n";
echo "  1. Check Supabase RLS policies\n";
echo "  2. Check if the anon key has SELECT permission on users_extended\n";
echo "  3. Try querying in Supabase console\n";
echo "\n";
echo "If SignIn fails:\n";
echo "  1. Verify user exists in Authentication → Users\n";
echo "  2. Check email confirmation setting\n";
echo "  3. Verify password is correct\n";
