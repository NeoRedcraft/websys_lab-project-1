<?php
$title = 'Admin Bookings - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Bookings (All Organizations)</h1>
        <a href="/bookings/create" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">+ Create Booking</a>
    </div>

    <?php if (empty($bookingsByOrg)): ?>
        <div class="bg-white border rounded-lg p-12 text-center shadow-sm">
            <p class="text-gray-500 mb-4">No booking requests available yet.</p>
            <a href="/bookings/create" class="text-red-600 font-semibold hover:underline">Create the first booking now &rarr;</a>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($bookingsByOrg as $group): ?>
                <?php
                $organization = $group['organization'] ?? [];
                $bookings = $group['bookings'] ?? [];
                ?>
                <section class="bg-white border rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($organization['name'] ?? 'Unknown Organization'); ?></h2>
                        <span class="text-sm text-gray-600"><?php echo count($bookings); ?> booking(s)</span>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Venue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($bookings as $booking): ?>
                                <?php $status = strtolower($booking['status'] ?? 'pending'); ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($booking['event_name'] ?? ''); ?></div>
                                        <div class="text-xs text-gray-500">Organizer ID: <?php echo htmlspecialchars($booking['organizer_id'] ?? ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars(date('M d, Y', strtotime($booking['event_date'] ?? 'now'))); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['venue'] ?? ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status === 'accepted' ? 'bg-green-100 text-green-800' : ($status === 'declined' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <a href="/bookings/view/<?php echo (int) ($booking['id'] ?? 0); ?>" class="text-red-600 hover:text-red-900">View</a>
                                        <form method="post" action="/bookings/delete" class="inline" onsubmit="return confirm('Delete this booking request? This action cannot be undone.');">
                                            <input type="hidden" name="booking_id" value="<?php echo (int) ($booking['id'] ?? 0); ?>">
                                            <button type="submit" class="ml-3 text-gray-600 hover:text-gray-900">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
