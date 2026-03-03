<?php
$title = 'Search Results - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Search Results</h1>
        <p class="text-gray-600">Browse organizations and open their profile pages.</p>
    </div>

    <form action="/search-results" method="GET" class="bg-white rounded-lg shadow p-6 mb-8">
        <label for="q" class="block text-sm font-semibold text-gray-700 mb-2">Search Organizations</label>
        <div class="flex gap-3">
            <input
                type="text"
                id="q"
                name="q"
                value="<?php echo htmlspecialchars($query ?? ''); ?>"
                placeholder="Search by organization name, genre, or bio"
                class="flex-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                autocomplete="off"
                required
            >
            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                Search
            </button>
        </div>
    </form>

    <?php if (empty($query)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600">Enter a search term to find organizations.</p>
        </div>
    <?php elseif (empty($organizations)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600">No organizations found for "<?php echo htmlspecialchars($query); ?>".</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($organizations as $organization): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <?php if (!empty($organization['image_url'])): ?>
                        <div class="h-40 overflow-hidden">
                            <img
                                src="<?php echo htmlspecialchars($organization['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($organization['name']); ?>"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            >
                        </div>
                    <?php else: ?>
                        <div class="bg-gradient-to-r from-red-600 to-red-700 h-40 flex items-center justify-center">
                            <div class="text-center text-white">
                                <div class="text-3xl font-bold"><?php echo htmlspecialchars(substr($organization['name'], 0, 1)); ?></div>
                                <div class="text-sm mt-2"><?php echo htmlspecialchars($organization['genre'] ?: 'Organization'); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="p-5">
                        <h2 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($organization['name']); ?></h2>
                        <p class="text-sm text-gray-600 mb-3">
                            <?php
                            $bio = trim((string) ($organization['bio'] ?? ''));
                            if ($bio === '') {
                                echo htmlspecialchars('Professional performing organization');
                            } else {
                                $summary = strlen($bio) > 140 ? substr($bio, 0, 140) . '...' : $bio;
                                echo htmlspecialchars($summary);
                            }
                            ?>
                        </p>

                        <div class="text-xs text-gray-500 mb-4">
                            <?php echo (int) ($organization['upcoming_bookings_count'] ?? 0); ?> upcoming booking<?php echo ((int) ($organization['upcoming_bookings_count'] ?? 0) === 1) ? '' : 's'; ?>
                        </div>

                        <a href="/organizations/<?php echo (int) $organization['id']; ?>" class="w-full block bg-red-600 text-white py-2 rounded-lg text-center hover:bg-red-700 transition">
                            View
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
