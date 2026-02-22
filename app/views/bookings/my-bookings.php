<?php
$title = 'My Bookings';
ob_start();
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">My Booking Requests</h1>
        <a href="/bookings/create" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
            + New Booking
        </a>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="bg-white border rounded-lg p-12 text-center shadow-sm">
            <p class="text-gray-500 mb-4">You haven't submitted any booking requests yet.</p>
            <a href="/bookings/create" class="text-red-600 font-semibold hover:underline">
                Start your first booking now &rarr;
            </a>
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
                    <?php foreach ($bookings as $booking): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($booking['event_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($booking['org_name'] ?? 'Personal Booking') ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?= date('M d, Y', strtotime($booking['event_date'])) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($booking['venue'] ?? 'TBA') ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                    $status = strtolower($booking['status'] ?? 'pending');
                                    $statusClasses = [
                                        'approved' => 'bg-green-100 text-green-800',
                                        'pending'  => 'bg-yellow-100 text-yellow-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                    ];
                                    $badgeClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $badgeClass ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <a href="/bookings/view/<?= $booking['id'] ?>" class="text-red-600 hover:text-red-900 mr-3">View</a>
                                <?php if ($status === 'pending'): ?>
                                    <a href="/bookings/edit/<?= $booking['id'] ?>" class="text-gray-600 hover:text-gray-900">Edit</a>
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