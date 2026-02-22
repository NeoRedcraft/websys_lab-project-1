<?php
/**
 * Debug admin request to see actual Supabase response.
 * Run: php scripts/debug_admin_request.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;
use App\Models\User;

$supabase = Supabase::getInstance();
$userModel = new User();

echo "=== Debug Admin Request ===\n\n";

$userRecord = $userModel->getByEmail('jbavida@mymail.mapua.edu.ph');
if (!$userRecord) {
    echo "[✗] User not found in users_extended\n";
    exit(1);
}

$userId = $userRecord['id'];
echo "Testing with UUID: {$userId}\n\n";

// Try using file_get_contents (same as Supabase.php)
$url = env('SUPABASE_URL') . "/auth/v1/admin/users/{$userId}";
$secretKey = env('SUPABASE_SECRET_KEY');

echo "URL: {$url}\n";
echo "Method: PUT\n\n";

$data = json_encode(['password' => 'ChangeMe2025!']);

$headers = [
    'Authorization: Bearer ' . $secretKey,
    'apikey: ' . $secretKey,
    'Content-Type: application/json',
];

$context = stream_context_create([
    'http' => [
        'method' => 'PUT',
        'header' => implode("\r\n", $headers) . "\r\n",
        'content' => $data,
        'ignore_errors' => true,
    ],
]);

$response = @file_get_contents($url, false, $context);

echo "Response: " . ($response ? $response : '(empty)') . "\n";
if ($response) {
    $decoded = json_decode($response, true);
    if (is_array($decoded)) {
        echo "\nDecoded:\n";
        foreach ($decoded as $key => $val) {
            if (is_array($val)) {
                echo "  {$key}: " . json_encode($val) . "\n";
            } else {
                echo "  {$key}: {$val}\n";
            }
        }
    }
}
