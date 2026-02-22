<?php
$title = isset($organization) ? 'Edit Organization' : 'Create Organization';
ob_start();
?>

<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($title) ?></h1>

    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 p-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= isset($organization) ? '/admin/organizations/edit/' . ($organization['id'] ?? '') : '/admin/organizations/create' ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input name="name" value="<?= htmlspecialchars($organization['name'] ?? '') ?>" class="mt-1 block w-full border rounded px-3 py-2" />
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Genre</label>
            <input name="genre" value="<?= htmlspecialchars($organization['genre'] ?? '') ?>" class="mt-1 block w-full border rounded px-3 py-2" />
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Bio</label>
            <textarea name="bio" class="mt-1 block w-full border rounded px-3 py-2"><?= htmlspecialchars($organization['bio'] ?? '') ?></textarea>
        </div>

        <div class="flex items-center space-x-3">
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Save</button>
            <a href="/admin/organizations" class="text-sm text-gray-600">Cancel</a>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>