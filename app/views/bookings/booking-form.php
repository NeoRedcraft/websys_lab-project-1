<?php
$title = isset($booking) ? 'Edit Booking' : 'Create Booking';
ob_start();
?>

<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($title) ?></h1>

    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 p-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= isset($booking) ? '/bookings/edit/' . ($booking['id'] ?? '') : '/bookings/create' ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Organization</label>
            <select name="org_id" class="mt-1 block w-full border rounded px-3 py-2">
                <option value="">Select organization</option>
                <?php foreach (($organizations ?? []) as $org): ?>
                    <option value="<?= $org['id'] ?>" <?= isset($booking) && $booking['org_id'] == $org['id'] ? 'selected' : '' ?>><?= htmlspecialchars($org['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Event Name</label>
            <input name="event_name" value="<?= htmlspecialchars($booking['event_name'] ?? '') ?>" class="mt-1 block w-full border rounded px-3 py-2" />
        </div>

        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Event Date</label>
                <input type="date" name="event_date" value="<?= htmlspecialchars($booking['event_date'] ?? '') ?>" class="mt-1 block w-full border rounded px-3 py-2" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Venue</label>
                <input name="venue" value="<?= htmlspecialchars($booking['venue'] ?? '') ?>" class="mt-1 block w-full border rounded px-3 py-2" />
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Technical Needs</label>
            <textarea name="technical_needs" class="mt-1 block w-full border rounded px-3 py-2"><?= htmlspecialchars($booking['technical_needs'] ?? '') ?></textarea>
        </div>

        <div class="flex items-center space-x-3">
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Save</button>
            <a href="/bookings/my-bookings" class="text-sm text-gray-600">Cancel</a>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>