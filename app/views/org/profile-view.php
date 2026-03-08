<?php
$title = 'Organization Profile - Cardinal Stage';
ob_start();
?>

<div class="max-w-4xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Organization Profile</h1>
        <div class="flex items-center gap-3">
            <a href="/org-admin/dashboard" class="inline-flex items-center rounded border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">Back to Org Dashboard</a>
            <a href="/org-admin/profile/edit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Edit Profile</a>
        </div>
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

    <?php if (!empty($reviewState['hasPending'])): ?>
        <?php $pending = $reviewState['latestAdminChange'] ?? []; ?>
        <?php $pendingValues = $pending['new_values_decoded'] ?? []; ?>
        <div class="mb-6 rounded border border-amber-200 bg-amber-50 p-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-amber-800 mb-2">Admin profile update pending your review</h2>
            <p class="text-sm text-amber-900 mb-3">An admin updated your organization profile. You can accept, decline (revert), or edit the details yourself.</p>
            <ul class="text-sm text-amber-900 space-y-1 mb-4">
                <li><strong>Name:</strong> <?php echo htmlspecialchars((string) ($pendingValues['name'] ?? ($organization['name'] ?? ''))); ?></li>
                <li><strong>Genre:</strong> <?php echo htmlspecialchars((string) ($pendingValues['genre'] ?? ($organization['genre'] ?? 'Not set'))); ?></li>
                <li><strong>Bio:</strong> <?php echo htmlspecialchars((string) ($pendingValues['bio'] ?? ($organization['bio'] ?? 'No bio yet.'))); ?></li>
            </ul>
            <div class="flex flex-wrap gap-3">
                <form method="post" action="/org-admin/profile/accept-admin-changes">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <button type="submit" class="inline-flex items-center rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">Accept Admin Changes</button>
                </form>
                <form method="post" action="/org-admin/profile/decline-admin-changes" onsubmit="return confirm('Decline and revert to the previous profile details?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <button type="submit" class="inline-flex items-center rounded bg-red-600 px-4 py-2 text-white hover:bg-red-700">Decline and Revert</button>
                </form>
                <a href="/org-admin/profile/edit" class="inline-flex items-center rounded border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">Edit Instead</a>
            </div>
        </div>
    <?php endif; ?>

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
