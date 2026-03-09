<?php
$title = 'Booking Details - Cardinal Stage';
ob_start();
?>
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Booking Reference: #<?php echo htmlspecialchars($booking['id']); ?>
            </h2>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="/org-admin/bookings"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Back to Inbox
            </a>
        </div>
    </div>

    <!-- Details View -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Booking Information
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Organized by <?php echo htmlspecialchars($organizer['name']); ?>
            </p>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
            <dl class="sm:divide-y sm:divide-gray-200">
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Event Name</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($booking['event_name']); ?></dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Date Range</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars(date('M d, Y', strtotime($booking['event_start']))); ?> to
                        <?php echo htmlspecialchars(date('M d, Y', strtotime($booking['event_end']))); ?>
                    </dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Location</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($booking['location']); ?></dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Event Details</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 whitespace-pre-line">
                        <?php echo htmlspecialchars($booking['event_details']); ?></dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Current Status</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?php echo $booking['status'] === 'pending' ? 'yellow-100 text-yellow-800' : ($booking['status'] === 'accepted' ? 'green-100 text-green-800' : 'red-100 text-red-800'); ?>">
                            <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                        </span>
                    </dd>
                </div>
                <?php if ($booking['notes']): ?>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Admin Notes</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 whitespace-pre-line">
                            <?php echo htmlspecialchars($booking['notes']); ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <!-- Actions (If pending) -->
    <?php if ($booking['status'] === 'pending'): ?>
        <div class="bg-gray-50 shadow sm:rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Action Required
                </h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500">
                    <p>
                        Please review this booking request. You can either approve or decline it.
                    </p>
                </div>
                <div class="mt-5 flex items-center space-x-4">
                    <form action="/org-admin/bookings/accept" method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id']); ?>">
                        <input type="hidden" name="notes" value="Approved automatically via UI detail view">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:text-sm">
                            Accept Booking
                        </button>
                    </form>

                    <form action="/org-admin/bookings/decline" method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id']); ?>">
                        <div class="mt-2 flex items-center space-x-2">
                            <input type="text" name="reason" placeholder="Reason for declining" required
                                class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            <button type="submit"
                                class="whitespace-nowrap inline-flex items-center justify-center px-4 py-2 border border-transparent font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm">
                                Decline Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include app_path('views/layout/app.php');
