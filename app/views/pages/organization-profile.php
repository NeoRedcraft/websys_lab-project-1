<?php
$title = ($organization['name'] ?? 'Organization') . ' - Cardinal Stage';
ob_start();
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Header -->
    <div class="bg-gradient-to-r from-red-600 to-red-700 rounded-lg p-8 text-white mb-8">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-4xl font-bold mb-2"><?php echo htmlspecialchars($organization['name']); ?></h1>
                <?php if ($organization['genre']): ?>
                    <p class="text-red-100 text-lg mb-4"><?php echo htmlspecialchars($organization['genre']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Bio Section -->
            <?php if ($organization['bio']): ?>
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">About</h2>
                    <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($organization['bio']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Technical Requirements -->
            <?php if ($organization['technical_requirements']): ?>
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">Technical Requirements</h2>
                    <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($organization['technical_requirements']); ?></p>
                </div>
            <?php endif; ?>

            <!-- YouTube Links -->
            <?php if ($organization['youtube_links']): ?>
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">Media</h2>
                    <div class="space-y-4">
                        <?php 
                        $links = array_filter(array_map('trim', explode("\n", $organization['youtube_links'])));
                        foreach ($links as $link):
                            if (!empty($link)):
                        ?>
                            <div>
                                <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" rel="noopener noreferrer" class="text-red-600 hover:text-red-700 break-all">
                                    <?php echo htmlspecialchars($link); ?>
                                </a>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Upcoming Events -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-2xl font-bold mb-4">Upcoming Events</h2>
                
                <?php 
                $upcomingBookings = array_filter($organization['accepted_bookings'] ?? [], function($b) {
                    return strtotime($b['event_date']) >= time();
                });
                usort($upcomingBookings, function($a, $b) {
                    return strtotime($a['event_date']) - strtotime($b['event_date']);
                });
                ?>

                <?php if (empty($upcomingBookings)): ?>
                    <p class="text-gray-500">No upcoming events scheduled</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($upcomingBookings as $booking): ?>
                            <div class="border-l-4 border-red-600 pl-4 py-2">
                                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($booking['event_name']); ?></h3>
                                <p class="text-gray-600">
                                    📅 <?php echo date('F j, Y', strtotime($booking['event_date'])); ?>
                                </p>
                                <?php if ($booking['venue']): ?>
                                    <p class="text-gray-600">
                                        📍 <?php echo htmlspecialchars($booking['venue']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Contact Card -->
            <?php if ($organization['admin']): ?>
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="font-bold mb-4">Organization President</h3>
                    <p class="text-gray-700 font-semibold"><?php echo htmlspecialchars($organization['admin']['full_name']); ?></p>
                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($organization['admin']['email']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="font-bold mb-4">Quick Stats</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-semibold text-green-600">Active</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Upcoming Events:</span>
                        <span class="font-semibold"><?php echo count($upcomingBookings); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Events:</span>
                        <span class="font-semibold"><?php echo count($organization['accepted_bookings'] ?? []); ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <?php if ($isAuthenticated && $user['role'] === 'organizer'): ?>
                <a href="/bookings/create?org_id=<?php echo $organization['id']; ?>" class="w-full block bg-red-600 text-white py-3 rounded-lg hover:bg-red-700 text-center font-semibold transition">
                    Request Booking
                </a>
            <?php elseif (!$isAuthenticated): ?>
                <a href="/signin" class="w-full block bg-red-600 text-white py-3 rounded-lg hover:bg-red-700 text-center font-semibold transition">
                    Sign In to Book
                </a>
            <?php endif; ?>

            <!-- Back Link -->
            <a href="/directory" class="w-full block bg-gray-200 text-gray-800 py-2 rounded-lg hover:bg-gray-300 text-center mt-4 transition">
                Back to Directory
            </a>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
