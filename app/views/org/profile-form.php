<?php
$title = 'Edit Organization Profile - Cardinal Stage';
ob_start();
?>

<div class="max-w-4xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Edit Organization Profile</h1>
        <a href="/org-admin/profile" class="text-gray-700 hover:text-gray-900">Back to Profile</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" action="/org-admin/profile/edit" class="bg-white border rounded-lg shadow-sm p-6 space-y-5">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">

        <div>
            <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Organization Logo/Image</label>
            <?php if (!empty($organization['image_url'])): ?>
                <img src="<?php echo htmlspecialchars($organization['image_url']); ?>" alt="Organization image" class="w-full h-40 object-cover rounded mb-2">
            <?php endif; ?>
            <input id="image" type="file" name="image" accept="image/*" class="w-full rounded border border-gray-300 px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">Accepted: JPG, PNG, GIF, WEBP. Max 2MB.</p>
        </div>

        <div>
            <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
            <textarea id="bio" name="bio" rows="6" class="w-full rounded border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"><?php echo htmlspecialchars($organization['bio'] ?? ''); ?></textarea>
        </div>

        <div>
            <label for="genre" class="block text-sm font-medium text-gray-700 mb-1">Genre</label>
            <input id="genre" name="genre" type="text" value="<?php echo htmlspecialchars($organization['genre'] ?? ''); ?>" class="w-full rounded border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>

        <div>
            <label for="technical_requirements" class="block text-sm font-medium text-gray-700 mb-1">Technical Requirements</label>
            <textarea id="technical_requirements" name="technical_requirements" rows="4" class="w-full rounded border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"><?php echo htmlspecialchars($organization['technical_requirements'] ?? ''); ?></textarea>
        </div>

        <div>
            <label for="youtube_links" class="block text-sm font-medium text-gray-700 mb-1">YouTube Links (one per line)</label>
            <textarea id="youtube_links" name="youtube_links" rows="4" class="w-full rounded border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"><?php echo htmlspecialchars($organization['youtube_links'] ?? ''); ?></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="/org-admin/profile" class="inline-flex items-center rounded border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="inline-flex items-center rounded bg-red-600 px-4 py-2 text-white hover:bg-red-700">Save Changes</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
