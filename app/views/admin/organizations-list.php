<?php
$title = 'Organizations';
ob_start();
?>

<div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Organizations</h1>
        <div class="flex items-center gap-3">
            <a href="/admin/dashboard" class="text-sm text-gray-600 hover:text-gray-800">Back to Admin Dashboard</a>
            <a href="/admin/organizations/create" class="bg-red-600 text-white px-4 py-2 rounded">New Organization</a>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($organizations)): ?>
        <p class="text-gray-600">No organizations found.</p>
    <?php else: ?>
        <table class="w-full bg-white border rounded">
            <thead>
                <tr class="text-left">
                    <th class="p-3">Name</th>
                    <th class="p-3">Genre</th>
                    <th class="p-3">Active</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizations as $o): ?>
                    <tr class="border-t">
                        <td class="p-3"><?= htmlspecialchars($o['name'] ?? '') ?></td>
                        <td class="p-3"><?= htmlspecialchars($o['genre'] ?? '') ?></td>
                        <td class="p-3"><?= $o['is_active'] ? 'Yes' : 'No' ?></td>
                        <td class="p-3">
                            <a href="/admin/organizations/edit/<?= $o['id'] ?>" class="text-red-600">Edit</a>
                            <form method="post" action="/admin/organizations/delete" style="display:inline;margin-left:8px;">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                <button class="text-gray-600">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>