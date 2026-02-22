<?php
$title = 'Organizer Dashboard - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-4xl font-bold mb-8">Organizer Dashboard</h1>

    <?php
    $userBookings = isset($bookings) ? $bookings : [];
    ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-medium mb-2">My Bookings</h3>
            <p class="text-3xl font-bold text-red-600"><?php echo is_array($userBookings) ? count($userBookings) : 0; ?></p>
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

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-4">My Booking Requests</h2>
        <?php if (empty($userBookings)): ?>
            <p class="text-gray-600">You haven't made any booking requests yet.</p>
        <?php else: ?>
            <ul class="divide-y">
                <?php foreach ($userBookings as $b): ?>
                    <li class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-medium"><?php echo $b['event_name'] ?? ''; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $b['event_date'] ?? ''; ?> • <?php echo $b['venue'] ?? ''; ?></div>
                        </div>
                        <div class="text-sm text-gray-700">
                            <span class="px-3 py-1 rounded-full text-white <?php echo $b['status'] === 'accepted' ? 'bg-green-600' : ($b['status'] === 'declined' ? 'bg-gray-500' : 'bg-yellow-500'); ?>"><?php echo ucfirst($b['status']); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
