<?php
/**
 * Verify booking visibility across roles.
 *
 * Usage:
 *   php scripts/verify_booking_visibility.php
 *
 * Optional .env overrides (only needed if auto-discovery cannot sign in):
 *   VERIFY_SYSTEM_ADMIN_EMAIL
 *   VERIFY_SYSTEM_ADMIN_PASSWORD
 *   VERIFY_ORG_ADMIN_EMAIL
 *   VERIFY_ORG_ADMIN_PASSWORD
 *   VERIFY_ORGANIZER_EMAIL
 *   VERIFY_ORGANIZER_PASSWORD
 *   VERIFY_DEFAULT_PASSWORD
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Models\BookingRequest;
use App\Models\User;
use App\Utils\Supabase;

function outputLine($message = '')
{
    echo $message . PHP_EOL;
}

function boolLabel($value)
{
    return $value ? 'PASS' : 'FAIL';
}

function normalizeBookingId($booking)
{
    return (string) ($booking['id'] ?? '');
}

function ownerIdOf($booking)
{
    return (string) ($booking['organizer_id'] ?? $booking['user_id'] ?? '');
}

function orgIdOf($booking)
{
    return (string) ($booking['organization_id'] ?? $booking['org_id'] ?? '');
}

function envOrNull($key)
{
    $value = env($key);
    if ($value === null) {
        return null;
    }

    $trimmed = trim((string) $value);
    return $trimmed === '' ? null : $trimmed;
}

function roleEnvPrefix($roleName)
{
    if ($roleName === 'system_admin') {
        return 'VERIFY_SYSTEM_ADMIN';
    }

    if ($roleName === 'org_admin') {
        return 'VERIFY_ORG_ADMIN';
    }

    return 'VERIFY_ORGANIZER';
}

function firstSignInSuccess($supabase, $email, array $passwordCandidates)
{
    foreach ($passwordCandidates as $password) {
        $result = $supabase->signIn($email, $password);
        if (!empty($result['success'])) {
            return [
                'result' => $result,
                'usedPassword' => $password,
            ];
        }
    }

    return null;
}

outputLine('=== Booking Visibility Verification ===');

$supabase = Supabase::getInstance();
$bookingModel = new BookingRequest();
$userModel = new User();

outputLine('Supabase available: ' . ($supabase->isAvailable() ? 'YES' : 'NO'));
outputLine('');

if (!$supabase->isAvailable()) {
    outputLine('FAIL: Supabase is not available from current environment.');
    exit(1);
}

outputLine('Loading baseline dataset using service role key ...');

try {
    $roles = $supabase->adminRequest('GET', '/rest/v1/roles?select=id,name');
    $allUsers = $supabase->adminRequest('GET', '/rest/v1/users_extended?select=id,email,org_id,role_id,is_active');
    $allBookings = $supabase->adminRequest('GET', '/rest/v1/booking_requests?select=*');
} catch (\Exception $e) {
    outputLine('FAIL: unable to load data using SUPABASE_SECRET_KEY');
    outputLine('Reason: ' . $e->getMessage());
    exit(1);
}

if (!is_array($roles)) {
    $roles = [];
}
if (!is_array($allUsers)) {
    $allUsers = [];
}
if (!is_array($allBookings)) {
    $allBookings = [];
}

$roleIds = [];
foreach ($roles as $role) {
    if (!empty($role['name']) && isset($role['id'])) {
        $roleIds[(string) $role['name']] = (string) $role['id'];
    }
}

foreach (['system_admin', 'org_admin', 'organizer'] as $requiredRole) {
    if (empty($roleIds[$requiredRole])) {
        outputLine('FAIL: missing role in roles table: ' . $requiredRole);
        exit(1);
    }
}

$ownersWithBookings = [];
$orgsWithBookings = [];
foreach ($allBookings as $booking) {
    $ownerId = ownerIdOf($booking);
    $orgId = orgIdOf($booking);
    if ($ownerId !== '') {
        $ownersWithBookings[$ownerId] = true;
    }
    if ($orgId !== '') {
        $orgsWithBookings[$orgId] = true;
    }
}

$accounts = [
    'system_admin' => [
        'expectedRole' => 'system_admin',
        'emailOverride' => envOrNull('VERIFY_SYSTEM_ADMIN_EMAIL'),
        'passwordOverride' => envOrNull('VERIFY_SYSTEM_ADMIN_PASSWORD'),
    ],
    'org_admin' => [
        'expectedRole' => 'org_admin',
        'emailOverride' => envOrNull('VERIFY_ORG_ADMIN_EMAIL'),
        'passwordOverride' => envOrNull('VERIFY_ORG_ADMIN_PASSWORD'),
    ],
    'organizer' => [
        'expectedRole' => 'organizer',
        'emailOverride' => envOrNull('VERIFY_ORGANIZER_EMAIL'),
        'passwordOverride' => envOrNull('VERIFY_ORGANIZER_PASSWORD'),
    ],
];

$defaultPassword = envOrNull('VERIFY_DEFAULT_PASSWORD');

$context = [];
foreach ($accounts as $accountKey => $account) {
    $expectedRole = (string) $account['expectedRole'];
    $roleId = (string) $roleIds[$expectedRole];

    $candidates = array_values(array_filter($allUsers, function ($user) use ($roleId) {
        $isRoleMatch = (string) ($user['role_id'] ?? '') === $roleId;
        $isActive = !array_key_exists('is_active', $user) || (bool) $user['is_active'];
        return $isRoleMatch && $isActive && !empty($user['email']);
    }));

    usort($candidates, function ($left, $right) {
        return strcmp((string) ($left['email'] ?? ''), (string) ($right['email'] ?? ''));
    });

    $candidateEmails = [];
    if (!empty($account['emailOverride'])) {
        $candidateEmails[] = (string) $account['emailOverride'];
    }

    if ($expectedRole === 'organizer') {
        foreach ($candidates as $candidate) {
            if (!empty($ownersWithBookings[(string) ($candidate['id'] ?? '')])) {
                $candidateEmails[] = (string) ($candidate['email'] ?? '');
            }
        }
    } elseif ($expectedRole === 'org_admin') {
        foreach ($candidates as $candidate) {
            if (!empty($orgsWithBookings[(string) ($candidate['org_id'] ?? '')])) {
                $candidateEmails[] = (string) ($candidate['email'] ?? '');
            }
        }
    }

    foreach ($candidates as $candidate) {
        $candidateEmails[] = (string) ($candidate['email'] ?? '');
    }

    $candidateEmails = array_values(array_unique(array_filter($candidateEmails, function ($value) {
        return trim((string) $value) !== '';
    })));

    if (empty($candidateEmails)) {
        outputLine('FAIL: no candidate account found for role ' . $expectedRole);
        outputLine('Set ' . roleEnvPrefix($expectedRole) . '_EMAIL in .env to force an account.');
        exit(1);
    }

    $passwordCandidates = [];
    if (!empty($account['passwordOverride'])) {
        $passwordCandidates[] = $account['passwordOverride'];
    }
    if (!empty($defaultPassword)) {
        $passwordCandidates[] = $defaultPassword;
    }
    $passwordCandidates[] = 'ChangeMe2025!';
    $passwordCandidates = array_values(array_unique(array_filter($passwordCandidates, function ($value) {
        return trim((string) $value) !== '';
    })));

    $signIn = null;
    $selectedEmail = null;
    foreach ($candidateEmails as $emailCandidate) {
        outputLine('Signing in ' . $accountKey . ': ' . $emailCandidate);
        $signIn = firstSignInSuccess($supabase, $emailCandidate, $passwordCandidates);
        if ($signIn) {
            $selectedEmail = $emailCandidate;
            break;
        }
    }

    if (!$signIn || !$selectedEmail) {
        outputLine('  Auto sign-in failed for this role.');
        outputLine('  Tried ' . count($candidateEmails) . ' account(s) x ' . count($passwordCandidates) . ' password candidate(s).');

        $manualPassword = null;
        if (function_exists('readline')) {
            $manualPassword = readline('  Enter password for role ' . $expectedRole . ' (or press Enter to abort): ');
        }

        if ($manualPassword !== null && trim((string) $manualPassword) !== '') {
            $manualPassword = trim((string) $manualPassword);
            foreach ($candidateEmails as $emailCandidate) {
                outputLine('  Retrying with provided password: ' . $emailCandidate);
                $manualSignIn = firstSignInSuccess($supabase, $emailCandidate, [$manualPassword]);
                if ($manualSignIn) {
                    $signIn = $manualSignIn;
                    $selectedEmail = $emailCandidate;
                    break;
                }
            }
        }

        if (!$signIn || !$selectedEmail) {
            outputLine('  FAIL: sign-in still failed.');
            outputLine('  Set ' . roleEnvPrefix($expectedRole) . '_PASSWORD or VERIFY_DEFAULT_PASSWORD in .env, then rerun.');
            exit(1);
        }
    }

    $result = $signIn['result'];
    $accessToken = $result['data']['access_token'] ?? null;
    $userId = $result['data']['user']['id'] ?? null;

    if (!$accessToken || !$userId) {
        outputLine('  FAIL: missing access token or user id after sign-in');
        exit(1);
    }

    $role = $userModel->getRole($userId);
    $roleName = $role['name'] ?? null;
    $roleOk = ($roleName === $expectedRole);
    $userRecord = $userModel->getById($userId);

    outputLine('  Role check: ' . boolLabel($roleOk) . ' (expected ' . $expectedRole . ', got ' . ($roleName ?: 'null') . ')');
    if (!$roleOk) {
        exit(1);
    }

    $context[$accountKey] = [
        'userId' => (string) $userId,
        'orgId' => (string) (($userRecord['org_id'] ?? '') ?: ''),
        'accessToken' => $accessToken,
        'email' => $selectedEmail,
    ];
}

outputLine('');
outputLine('Total bookings in dataset: ' . count($allBookings));

$systemAdminVisible = $bookingModel->getAll($context['system_admin']['accessToken']);
$systemAdminVisibleIds = array_values(array_unique(array_filter(array_map('normalizeBookingId', $systemAdminVisible))));
$allBookingIds = array_values(array_unique(array_filter(array_map('normalizeBookingId', $allBookings))));
sort($systemAdminVisibleIds);
sort($allBookingIds);
$systemAdminOk = ($systemAdminVisibleIds === $allBookingIds);

outputLine('');
outputLine('System admin visibility: ' . boolLabel($systemAdminOk));
outputLine('  Visible: ' . count($systemAdminVisibleIds) . ' / Expected: ' . count($allBookingIds));

$orgAdminOrgId = $context['org_admin']['orgId'];
$orgAdminVisible = $bookingModel->getByOrganization($orgAdminOrgId, $context['org_admin']['accessToken']);
$orgAdminLeaked = array_values(array_filter($orgAdminVisible, function ($booking) use ($orgAdminOrgId) {
    return orgIdOf($booking) !== (string) $orgAdminOrgId;
}));
$orgAdminExpectedCount = count(array_filter($allBookings, function ($booking) use ($orgAdminOrgId) {
    return orgIdOf($booking) === (string) $orgAdminOrgId;
}));
$orgAdminOk = empty($orgAdminLeaked) && count($orgAdminVisible) === $orgAdminExpectedCount;

outputLine('');
outputLine('Org admin visibility: ' . boolLabel($orgAdminOk));
outputLine('  Visible: ' . count($orgAdminVisible) . ' / Expected for org ' . $orgAdminOrgId . ': ' . $orgAdminExpectedCount);
outputLine('  Leaked from other orgs: ' . count($orgAdminLeaked));

$organizerId = $context['organizer']['userId'];
$organizerVisible = $bookingModel->getByOrganizer($organizerId, $context['organizer']['accessToken']);
$organizerLeaks = array_values(array_filter($organizerVisible, function ($booking) use ($organizerId) {
    return ownerIdOf($booking) !== (string) $organizerId;
}));
$organizerExpectedCount = count(array_filter($allBookings, function ($booking) use ($organizerId) {
    return ownerIdOf($booking) === (string) $organizerId;
}));
$organizerOk = empty($organizerLeaks) && count($organizerVisible) === $organizerExpectedCount;

outputLine('');
outputLine('Organizer visibility: ' . boolLabel($organizerOk));
outputLine('  Visible: ' . count($organizerVisible) . ' / Expected owned: ' . $organizerExpectedCount);
outputLine('  Leaked bookings from other owners: ' . count($organizerLeaks));

$crossChecksOk = true;
$otherOrgBooking = null;
foreach ($allBookings as $booking) {
    if (orgIdOf($booking) !== (string) $orgAdminOrgId) {
        $otherOrgBooking = $booking;
        break;
    }
}

if ($otherOrgBooking) {
    $deniedToOrgAdmin = $bookingModel->getById($otherOrgBooking['id'], $context['org_admin']['accessToken']) === null;
    outputLine('');
    outputLine('Org admin cross-org detail access: ' . boolLabel($deniedToOrgAdmin));
    $crossChecksOk = $crossChecksOk && $deniedToOrgAdmin;
} else {
    outputLine('');
    outputLine('Org admin cross-org detail access: SKIPPED (no booking outside org found)');
}

$otherOwnerBooking = null;
foreach ($allBookings as $booking) {
    if (ownerIdOf($booking) !== (string) $organizerId) {
        $otherOwnerBooking = $booking;
        break;
    }
}

if ($otherOwnerBooking) {
    $deniedToOrganizer = $bookingModel->getById($otherOwnerBooking['id'], $context['organizer']['accessToken']) === null;
    outputLine('Organizer cross-owner detail access: ' . boolLabel($deniedToOrganizer));
    $crossChecksOk = $crossChecksOk && $deniedToOrganizer;
} else {
    outputLine('Organizer cross-owner detail access: SKIPPED (no booking from other owner found)');
}

$summaryPass = $systemAdminOk && $orgAdminOk && $organizerOk && $crossChecksOk;
outputLine('');
outputLine('=== SUMMARY: ' . ($summaryPass ? 'PASS' : 'FAIL') . ' ===');

exit($summaryPass ? 0 : 2);
