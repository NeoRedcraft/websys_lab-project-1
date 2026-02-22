<?php
$title = 'Dashboard - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <?php
    $displayName = 'User';
    if (!empty($user)) {
        if (!empty($user['full_name'])) {
            $displayName = $user['full_name'];
        } elseif (!empty($user['name'])) {
            $displayName = $user['name'];
        } elseif (!empty($user['user_metadata']) && !empty($user['user_metadata']['name'])) {
            $displayName = $user['user_metadata']['name'];
        } elseif (!empty($user['email'])) {
            $displayName = $user['email'];
        }
    }
    ?>

    <h1 class="text-4xl font-bold mb-8">Welcome, <?php echo htmlspecialchars($displayName); ?></h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-bold mb-2">Your Events</h3>
            <p class="text-3xl font-bold text-red-600">0</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-bold mb-2">Total Bookings</h3>
            <p class="text-3xl font-bold text-red-600">0</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-bold mb-2">Favorites</h3>
            <p class="text-3xl font-bold text-red-600">0</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold mb-4">Quick Links</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="/directory" class="text-red-600 hover:text-red-700 font-medium">
                → Browse Talent Directory
            </a>
            <a href="/account" class="text-red-600 hover:text-red-700 font-medium">
                → Update Account Settings
            </a>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
