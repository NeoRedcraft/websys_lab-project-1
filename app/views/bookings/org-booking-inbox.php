<?php
$title = 'Booking Inbox';
ob_start();
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Booking Inbox</h1>
    </div>

    <?php if (empty($bookingRequests)): ?>
        <div class="bg-white border rounded-lg p-12 text-center shadow-sm">
            <p class="text-gray-500">No incoming booking requests yet.</p>
        </div>
    <?php else: ?>
        <div class="bg-white border rounded-lg overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Venue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($bookingRequests as $booking): ?>
                        <?php $status = strtolower($booking['status'] ?? 'pending'); ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($booking['event_name'] ?? ''); ?></div>
                                <div class="text-xs text-gray-500">Organizer: <?php echo htmlspecialchars($booking['organizer_name'] ?? ($booking['organizer_id'] ?? '')); ?></div>
                                <?php if (!empty($booking['organizer_email'])): ?>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['organizer_email']); ?></div>
                                <?php endif; ?>
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
                            <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                <a href="/org-admin/bookings/<?php echo (int) ($booking['id'] ?? 0); ?>" class="text-red-600 hover:text-red-900">View</a>

                                <?php if ($status === 'pending'): ?>
                                    <form method="post" action="/org-admin/bookings/accept" class="inline">
                                        <input type="hidden" name="booking_id" value="<?php echo (int) ($booking['id'] ?? 0); ?>">
                                        <button type="submit" class="text-green-700 hover:text-green-900">Accept</button>
                                    </form>
                                    <form method="post" action="/org-admin/bookings/decline" class="inline">
                                        <input type="hidden" name="booking_id" value="<?php echo (int) ($booking['id'] ?? 0); ?>">
                                        <button type="submit" class="text-gray-700 hover:text-gray-900">Decline</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
