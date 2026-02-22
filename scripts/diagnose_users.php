<?php
/**
 * Diagnose user existence in users_extended table.
 * Run: php scripts/diagnose_users.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Utils\Supabase;
use App\Models\User;

$supabase = Supabase::getInstance();
$userModel = new User();

echo "=== Diagnosing User Existence ===\n\n";

$testEmails = [
    'jbavida@mymail.mapua.edu.ph',
    'chaylesantiago@mymail.mapua.edu.ph',
];

foreach ($testEmails as $email) {
    echo "Checking: {$email}\n";
    
    // Query users_extended
    $userRecord = $userModel->getByEmail($email);
    
    if (!$userRecord) {
        echo "  [✗] NOT found in users_extended\n";
        echo "     This user needs to be added to users_extended table.\n";
    } else {
        echo "  [✓] Found in users_extended\n";
        echo "      ID: {$userRecord['id']}\n";
        echo "      Email: {$userRecord['email']}\n";
        echo "      Full Name: {$userRecord['full_name']}\n";
        echo "      Role ID: {$userRecord['role_id']}\n";
        echo "      Org ID: {$userRecord['org_id']}\n";
        echo "      Is Active: {$userRecord['is_active']}\n";
        echo "      Created At: {$userRecord['created_at']}\n";
        
        // Try to get role name
        $role = $userModel->getRole($userRecord['id']);
        if ($role) {
            echo "      Role: {$role['name']}\n";
        }
    }
    
    echo "\n";
}

echo "=== Next Steps ===\n";
echo "If users are NOT found in users_extended:\n";
echo "  1. They must exist in Supabase auth.users (check Authentication → Users)\n";
echo "  2. You need to INSERT them into users_extended table:\n";
echo "     INSERT INTO users_extended (id, email, full_name, role_id, is_active)\n";
echo "     VALUES\n";
echo "       ('<uuid_from_auth.users>', 'jbavida@mymail.mapua.edu.ph', 'Name', 1, true),\n";
echo "       ('<uuid_from_auth.users>', 'chaylesantiago@mymail.mapua.edu.ph', 'Name', 2, true);\n";
echo "\n";
echo "Role IDs (from schema):\n";
echo "  1 = system_admin\n";
echo "  2 = org_admin\n";
echo "  3 = organizer\n";
