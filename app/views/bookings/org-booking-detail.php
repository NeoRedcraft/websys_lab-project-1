<?php
$title = 'Incoming Booking Details';
ob_start();
?>

<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($booking['event_name'] ?? 'Booking'); ?></h1>

    <div class="bg-white border rounded p-4 mb-4">
        <div class="text-sm text-gray-500 mb-1">Date: <?php echo htmlspecialchars($booking['event_date'] ?? ''); ?></div>
        <div class="text-sm text-gray-500 mb-1">Venue: <?php echo htmlspecialchars($booking['venue'] ?? ''); ?></div>
        <div class="text-sm text-gray-500 mb-3">Status: <?php echo htmlspecialchars(ucfirst($booking['status'] ?? 'pending')); ?></div>
        <div class="mt-3 text-gray-700"><?php echo nl2br(htmlspecialchars($booking['technical_needs'] ?? '')); ?></div>
    </div>

    <?php if (!empty($organizer)): ?>
        <div class="bg-white border rounded p-4 mb-4">
            <h2 class="font-semibold mb-2">Organizer</h2>
            <p class="text-sm text-gray-700"><?php echo htmlspecialchars($organizer['full_name'] ?? $organizer['email'] ?? ''); ?></p>
            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($organizer['email'] ?? ''); ?></p>
        </div>
    <?php endif; ?>

    <div class="flex items-center space-x-3">
        <?php if (($booking['status'] ?? '') === 'pending'): ?>
            <form method="post" action="/org-admin/bookings/accept" class="inline">
                <input type="hidden" name="booking_id" value="<?php echo (int) ($booking['id'] ?? 0); ?>">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Accept</button>
            </form>
            <form method="post" action="/org-admin/bookings/decline" class="inline">
                <input type="hidden" name="booking_id" value="<?php echo (int) ($booking['id'] ?? 0); ?>">
                <button type="submit" class="bg-gray-200 px-4 py-2 rounded">Decline</button>
            </form>
        <?php endif; ?>

        <a href="/org-admin/bookings" class="text-sm text-gray-600">Back to Inbox</a>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
