<?php
$title = 'Organization Profile - Cardinal Stage';
ob_start();
?>

<div class="max-w-4xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Organization Profile</h1>
        <a href="/org-admin/profile/edit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Edit Profile</a>
    </div>

    <div class="bg-white border rounded-lg shadow-sm p-6 space-y-6">
        <div>
            <h2 class="text-sm uppercase tracking-wide text-gray-500 mb-1">Organization Name</h2>
            <p class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($organization['name'] ?? ''); ?></p>
        </div>

        <div>
            <h2 class="text-sm uppercase tracking-wide text-gray-500 mb-1">Genre</h2>
            <p class="text-gray-800"><?php echo htmlspecialchars($organization['genre'] ?? 'Not set'); ?></p>
        </div>

        <div>
            <h2 class="text-sm uppercase tracking-wide text-gray-500 mb-1">Bio</h2>
            <p class="text-gray-800 whitespace-pre-wrap"><?php echo htmlspecialchars($organization['bio'] ?? 'No bio yet.'); ?></p>
        </div>

        <div>
            <h2 class="text-sm uppercase tracking-wide text-gray-500 mb-1">Technical Requirements</h2>
            <p class="text-gray-800 whitespace-pre-wrap"><?php echo htmlspecialchars($organization['technical_requirements'] ?? 'No requirements added yet.'); ?></p>
        </div>

        <div>
            <h2 class="text-sm uppercase tracking-wide text-gray-500 mb-1">YouTube Links</h2>
            <?php if (!empty($organization['youtube_links'])): ?>
                <?php $links = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $organization['youtube_links']))); ?>
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($links as $link): ?>
                        <li>
                            <a class="text-red-600 hover:text-red-700 break-all" href="<?php echo htmlspecialchars($link); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($link); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-gray-800">No links added yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
