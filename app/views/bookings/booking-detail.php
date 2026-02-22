<?php
$title = 'Booking Details';
ob_start();
?>

<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($booking['event_name'] ?? 'Booking') ?></h1>

    <div class="bg-white border rounded p-4 mb-4">
        <div class="text-sm text-gray-500">Date: <?= htmlspecialchars($booking['event_date'] ?? '') ?></div>
        <div class="text-sm text-gray-500">Venue: <?= htmlspecialchars($booking['venue'] ?? '') ?></div>
        <div class="mt-3 text-gray-700"><?= nl2br(htmlspecialchars($booking['additional_notes'] ?? $booking['technical_needs'] ?? '')) ?></div>
    </div>

    <div class="flex items-center space-x-3">
        <?php if ($isOwner ?? false && ($booking['status'] ?? '') === 'pending'): ?>
            <a href="/bookings/edit/<?= $booking['id'] ?>" class="bg-red-600 text-white px-4 py-2 rounded">Edit</a>
            <form method="post" action="/bookings/delete" onsubmit="return confirm('Delete this pending booking?');">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <button type="submit" class="bg-gray-200 px-4 py-2 rounded">Delete</button>
            </form>
        <?php endif; ?>

        <?php if (!($isOwner ?? false) && isset($organizer)): ?>
            <div class="text-sm text-gray-600">Organizer: <?= htmlspecialchars($organizer['full_name'] ?? $organizer['name'] ?? $organizer['email'] ?? '') ?></div>
        <?php endif; ?>

        <a href="/bookings/my-bookings" class="text-sm text-gray-600">Back</a>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>