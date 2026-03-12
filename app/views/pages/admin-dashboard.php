<?php
$title = 'Admin Dashboard - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-4xl font-bold">Admin Dashboard</h1>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <a href="/admin/users" class="inline-flex items-center justify-center bg-white border border-red-600 text-red-600 px-4 py-2 rounded hover:bg-red-50">User Management</a>
            <a href="/admin/organizations" class="inline-flex items-center justify-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Manage Organizations</a>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 p-3 rounded"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 p-3 rounded"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php
    $orgs = isset($organizations) ? $organizations : [];
    $logs = isset($auditLogs) ? $auditLogs : [];
    $totalOrgs = is_array($orgs) ? count($orgs) : 0;
    $decodeAuditPayload = function ($value) {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    };
    ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-medium mb-2">Organizations</h3>
            <p class="text-3xl font-bold text-red-600"><?php echo $totalOrgs; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-medium mb-2">Pending Bookings</h3>
            <p class="text-3xl font-bold text-red-600"><?php echo isset($stats['pending']) ? $stats['pending'] : '-'; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-medium mb-2">Accepted</h3>
            <p class="text-3xl font-bold text-red-600"><?php echo isset($stats['accepted']) ? $stats['accepted'] : '-'; ?></p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Home Banner Image</h2>
        <?php if (!empty($homeBannerUrl)): ?>
            <img src="<?php echo htmlspecialchars($homeBannerUrl); ?>" alt="Home banner preview" class="w-full h-52 object-cover rounded mb-4">
        <?php else: ?>
            <p class="text-gray-600 mb-4">No custom home banner uploaded yet. The default red banner is currently in use.</p>
        <?php endif; ?>

        <form method="post" action="/admin/home-banner/upload" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-3 md:items-center">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
            <input type="file" name="home_banner" accept="image/*" class="block w-full md:w-auto border rounded px-3 py-2" required>
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Upload Banner</button>
        </form>
        <p class="text-xs text-gray-500 mt-2">Accepted: JPG, PNG, WEBP, GIF. Max 5MB.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold">Recent Audit Logs</h2>
                <a href="/admin/audit-logs" class="text-red-600 hover:underline text-sm">View Full Audit Logs</a>
            </div>
            <?php if (empty($logs)): ?>
                <p class="text-gray-600">No audit logs available.</p>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($logs as $log): ?>
                        <li class="border rounded p-3">
                            <?php
                            $entityType = (string) ($log['entity_type'] ?? 'entity');
                            $entityId = isset($log['entity_id']) ? (string) $log['entity_id'] : 'n/a';
                            $actor = (string) ($log['user_email'] ?? $log['user_id'] ?? 'system');
                            $newValues = $decodeAuditPayload($log['new_values'] ?? null);
                            $changedFields = array_keys($newValues);
                            ?>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars((string) ($log['created_at'] ?? '')); ?> - <?php echo htmlspecialchars((string) ($log['action'] ?? '')); ?></div>
                            <div class="text-base font-medium"><?php echo htmlspecialchars($entityType); ?> #<?php echo htmlspecialchars($entityId); ?> by <?php echo htmlspecialchars($actor); ?></div>
                            <?php if (!empty($changedFields)): ?>
                                <div class="text-sm text-gray-600">Changed: <?php echo htmlspecialchars(implode(', ', $changedFields)); ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-4">Organizations</h2>
            <?php if (empty($orgs)): ?>
                <p class="text-gray-600">No organizations found.</p>
            <?php else: ?>
                <ul class="space-y-2">
                    <?php foreach (array_slice($orgs, 0, 8) as $o): ?>
                        <li class="flex items-center justify-between">
                            <div>
                                <div class="font-medium"><?php echo $o['name'] ?? '—'; ?></div>
                                <div class="text-sm text-gray-500">
                                    <?php echo $o['genre'] ?? ''; ?>
                                    <span class="ml-2 <?php echo !empty($o['is_active']) ? 'text-green-700' : 'text-amber-700'; ?>">
                                        <?php echo !empty($o['is_active']) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="/admin/organizations/edit/<?php echo $o['id'] ?? ''; ?>" class="text-red-600 hover:underline">Edit</a>
                                <?php if (!empty($o['is_active'])): ?>
                                    <form method="post" action="/admin/organizations/deactivate">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($o['id'] ?? '')); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                                        <button class="text-amber-700 hover:underline" type="submit">Deactivate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="/admin/organizations/activate">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($o['id'] ?? '')); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                                        <button class="text-green-700 hover:underline" type="submit">Activate</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
