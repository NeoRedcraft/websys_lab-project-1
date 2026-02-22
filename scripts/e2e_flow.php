<?php
// E2E flow script
// Steps: create org -> signup -> create booking -> promote user to org_admin -> accept booking

$base = 'http://localhost:8000';
$cookie = __DIR__ . '/e2e_cookies.txt';
@unlink($cookie);

$dotenv = parse_ini_file(__DIR__ . '/../.env');
$supabaseUrl = trim($dotenv['SUPABASE_URL'] ?? '');
$secret = trim($dotenv['SUPABASE_SECRET_KEY'] ?? $dotenv['SUPABASE_SECRET_KEY'] ?? '');
if (!$supabaseUrl || !$secret) {
    echo "Supabase credentials missing in .env\n";
    exit(1);
}

function curl_req($url, $method = 'GET', $data = null, $headers = [], $cookie = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    }
    if ($data !== null) {
        $payload = is_array($data) ? http_build_query($data) : $data;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return [$resp, $info, $err];
}

echo "1) Creating organization via Supabase REST...\n";
$orgData = ['name' => 'E2E Test Org ' . time(), 'bio' => 'Created by E2E script', 'genre' => 'Test'];
$orgUrl = rtrim($supabaseUrl, '/') . '/rest/v1/organizations';
$orgHeaders = [
    'Content-Type: application/json',
    'apikey: ' . $secret,
    'Authorization: Bearer ' . $secret,
    'Prefer: return=representation'
];
list($orgResp, $orgInfo, $orgErr) = curl_req($orgUrl, 'POST', json_encode($orgData), $orgHeaders);
if ($orgErr) { echo "Org create curl error: $orgErr\n"; }
$orgDecoded = json_decode($orgResp, true);
if (!$orgDecoded || !isset($orgDecoded[0]['id'])) {
    echo "Failed to create org: response=" . substr($orgResp,0,300) . "\n";
    exit(1);
}
$orgId = $orgDecoded[0]['id'];
echo "Created org id: $orgId\n";

// Prepare test user
$testEmail = 'e2e_user_' . rand(1000,9999) . '@mymail.mapua.edu.ph';
$testPass = 'Password1!';
$testName = 'E2E Tester';

// 2) Signup
echo "2) Signing up as $testEmail ...\n";
list($getSignup, $gInfo, $gErr) = curl_req($base . '/signup', 'GET', null, [], $cookie);
// POST signup form
$postData = [
    'name' => $testName,
    'email' => $testEmail,
    'password' => $testPass,
    'role' => 'organizer'
];
list($signupResp, $signupInfo, $signupErr) = curl_req($base . '/signup', 'POST', $postData, [], $cookie);
if ($signupErr) { echo "Signup curl error: $signupErr\n"; }
// Check if redirected to dashboard
echo "Signup HTTP code: " . ($signupInfo['http_code'] ?? 'NA') . "\n";

// 3) Find auth user id via Supabase admin
echo "3) Querying Supabase for user id...\n";
$adminUrl = rtrim($supabaseUrl, '/') . '/auth/v1/admin/users?email=eq.' . urlencode($testEmail);
$adminHeaders = [
    'apikey: ' . $secret,
    'Authorization: Bearer ' . $secret,
];
list($adminResp, $adminInfo, $adminErr) = curl_req($adminUrl, 'GET', null, $adminHeaders);
if ($adminErr) { echo "Admin user curl error: $adminErr\n"; }
$adminDecoded = json_decode($adminResp, true);
if (empty($adminDecoded) || !isset($adminDecoded[0]['id'])) {
    echo "Failed to find auth user: " . substr($adminResp,0,300) . "\n";
    exit(1);
}
$userId = $adminDecoded[0]['id'];
echo "Auth user id: $userId\n";

// 4) Upsert into users_extended to create profile (role organizer)
echo "4) Creating users_extended profile...\n";
$userExtUrl = rtrim($supabaseUrl, '/') . '/rest/v1/users_extended';
$userExtData = [
    'id' => $userId,
    'email' => $testEmail,
    'full_name' => $testName,
    'role_id' => 3,
    'org_id' => null,
    'is_active' => true,
];
list($ueResp, $ueInfo, $ueErr) = curl_req($userExtUrl, 'POST', json_encode($userExtData), array_merge($orgHeaders));
$ueDecoded = json_decode($ueResp, true);
if ($ueErr) { echo "users_extended curl err: $ueErr\n"; }
if (isset($ueDecoded[0]['id'])) {
    echo "Created users_extended id: " . $ueDecoded[0]['id'] . "\n";
} else {
    echo "users_extended response: " . substr($ueResp,0,300) . "\n";
}

// 5) Create booking as organizer: POST /bookings/create
echo "5) Creating booking (organizer)...\n";
// Fetch booking form to ensure cookie/session
list($bf, $bfInfo, $bfErr) = curl_req($base . '/bookings/create', 'GET', null, [], $cookie);
$postBooking = [
    'org_id' => $orgId,
    'event_name' => 'E2E Event',
    'event_date' => date('Y-m-d', strtotime('+14 days')),
    'venue' => 'Main Hall',
    'technical_needs' => 'PA, lights',
    'expected_attendees' => 100,
    'additional_notes' => 'Automated test booking'
];
list($createBookingResp, $createBookingInfo, $createBookingErr) = curl_req($base . '/bookings/create', 'POST', $postBooking, [], $cookie);
if ($createBookingErr) { echo "Create booking curl err: $createBookingErr\n"; }
echo "Create booking HTTP code: " . ($createBookingInfo['http_code'] ?? 'NA') . "\n";

// Fetch my bookings page to find booking id
list($myBookingsPage, $mbInfo, $mbErr) = curl_req($base . '/bookings/my-bookings', 'GET', null, [], $cookie);
if ($mbErr) { echo "My bookings curl err: $mbErr\n"; }
$foundBookingId = null;
if (preg_match('/bookings\/view\/(\d+)/', $myBookingsPage, $m)) {
    $foundBookingId = $m[1];
}
// As fallback try to query supabase booking_requests table for organizer_id eq userId
if (!$foundBookingId) {
    echo "Attempting to query booking_requests via Supabase admin...\n";
    $bkUrl = rtrim($supabaseUrl, '/') . '/rest/v1/booking_requests?user_id=eq.' . $userId;
    list($bkResp, $bkInfo, $bkErr) = curl_req($bkUrl, 'GET', null, $orgHeaders);
    $bkDecoded = json_decode($bkResp, true);
    if (!empty($bkDecoded) && isset($bkDecoded[0]['id'])) {
        $foundBookingId = $bkDecoded[0]['id'];
    }
}
if (!$foundBookingId) {
    echo "Failed to determine booking id.\n";
    exit(1);
}
echo "Found booking id: $foundBookingId\n";

// 6) Promote user to org_admin for the org
echo "6) Promoting user to org_admin for org $orgId ...\n";
$patchUrl = rtrim($supabaseUrl, '/') . '/rest/v1/users_extended?id=eq.' . $userId;
$patchData = ['role_id' => 2, 'org_id' => $orgId];
list($patchResp, $patchInfo, $patchErr) = curl_req($patchUrl, 'PATCH', json_encode($patchData), array_merge($orgHeaders));
if ($patchErr) { echo "Patch err: $patchErr\n"; }
echo "Promote HTTP code: " . ($patchInfo['http_code'] ?? 'NA') . "\n";

// 7) Accept booking as org_admin
echo "7) Accepting booking via /org-admin/bookings/accept ...\n";
$acceptData = ['booking_id' => $foundBookingId, 'notes' => 'Accepted by E2E script'];
list($acceptResp, $acceptInfo, $acceptErr) = curl_req($base . '/org-admin/bookings/accept', 'POST', $acceptData, [], $cookie);
if ($acceptErr) { echo "Accept curl err: $acceptErr\n"; }
echo "Accept HTTP code: " . ($acceptInfo['http_code'] ?? 'NA') . "\n";

// 8) Verify booking status via Supabase
$bkVerifyUrl = rtrim($supabaseUrl, '/') . '/rest/v1/booking_requests?id=eq.' . $foundBookingId;
list($bkVerifyResp, $bkVerifyInfo, $bkVerifyErr) = curl_req($bkVerifyUrl, 'GET', null, $orgHeaders);
$bkVerifyDecoded = json_decode($bkVerifyResp, true);
echo "Booking record: " . substr($bkVerifyResp,0,400) . "\n";

echo "E2E flow completed.\n";
