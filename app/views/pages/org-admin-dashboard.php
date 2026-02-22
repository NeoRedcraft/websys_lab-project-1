<?php
$title = 'Organization Admin Dashboard - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-4xl font-bold mb-8">Organization Admin Dashboard</h1>

    <?php
    $org = isset($organization) ? $organization : null;
    $bookings = isset($bookingRequests) ? $bookingRequests : [];
    $stats = isset($stats) ? $stats : [];
    ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-medium mb-2">Members</h3>
            <p class="text-3xl font-bold text-red-600"><?php echo isset($org['members_count']) ? $org['members_count'] : '-'; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-medium mb-2">Pending</h3>
            <p class="text-3xl font-bold text-red-600"><?php echo isset($stats['pending']) ? $stats['pending'] : '-'; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-medium mb-2">Accepted</h3>
            <p class="text-3xl font-bold text-red-600"><?php echo isset($stats['accepted']) ? $stats['accepted'] : '-'; ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-4">Incoming Bookings</h2>
            <?php if (empty($bookings)): ?>
                <p class="text-gray-600">No booking requests yet.</p>
            <?php else: ?>
                <ul class="divide-y">
                    <?php foreach ($bookings as $b): ?>
                        <li class="py-3 flex justify-between items-start">
                            <div>
                                <div class="font-medium"><?php echo $b['event_name'] ?? '—'; ?></div>
                                <div class="text-sm text-gray-500"><?php echo $b['event_date'] ?? ''; ?> • <?php echo $b['venue'] ?? ''; ?></div>
                            </div>
                            <div class="space-x-2">
                                <form method="post" action="/org-admin/bookings/accept" class="inline">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id'] ?? ''; ?>">
                                    <button class="bg-green-600 text-white px-3 py-1 rounded">Accept</button>
                                </form>
                                <form method="post" action="/org-admin/bookings/decline" class="inline">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id'] ?? ''; ?>">
                                    <button class="bg-gray-200 px-3 py-1 rounded">Decline</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-4">Organization Info</h2>
            <?php if (!$org): ?>
                <p class="text-gray-600">No organization assigned.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <div class="font-medium"><?php echo $org['name'] ?? ''; ?></div>
                    <div class="text-sm text-gray-500"><?php echo $org['genre'] ?? ''; ?></div>
                    <p class="text-gray-700 mt-2 text-sm"><?php echo $org['bio'] ?? ''; ?></p>
                    <a href="/org-admin/profile/edit" class="inline-block mt-3 text-red-600">Edit Profile</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
