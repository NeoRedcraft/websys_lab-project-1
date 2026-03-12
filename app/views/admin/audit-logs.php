<?php
$title = 'Audit Logs';
ob_start();
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Audit Logs</h1>
        <a href="/admin/dashboard" class="text-red-600 hover:underline text-sm">Back to Dashboard</a>
    </div>

    <?php
    $usersList = is_array($users ?? null) ? $users : [];
    $activeUserId = (string) ($selectedUserId ?? '');
    $activeLimit = (int) ($selectedLimit ?? 100);
    ?>

    <form method="GET" action="/admin/audit-logs" class="bg-white border rounded p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <div class="md:col-span-2">
            <label for="user_id" class="block text-sm font-medium mb-1">Filter by User</label>
            <select id="user_id" name="user_id" class="w-full border rounded px-3 py-2">
                <option value="">All users</option>
                <?php foreach ($usersList as $u): ?>
                    <?php $uid = (string) ($u['id'] ?? ''); ?>
                    <?php if ($uid === '') continue; ?>
                    <?php $label = trim((string) ($u['full_name'] ?? '')) !== '' ? (string) $u['full_name'] : ((string) ($u['email'] ?? $uid)); ?>
                    <option value="<?= htmlspecialchars($uid) ?>" <?= $activeUserId === $uid ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?> (<?= htmlspecialchars((string) ($u['email'] ?? 'no-email')) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="limit" class="block text-sm font-medium mb-1">Rows</label>
            <select id="limit" name="limit" class="w-full border rounded px-3 py-2">
                <?php foreach ([50, 100, 250, 500, 1000] as $limitOption): ?>
                    <option value="<?= $limitOption ?>" <?= $activeLimit === $limitOption ? 'selected' : '' ?>><?= $limitOption ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Apply</button>
            <a href="/admin/audit-logs" class="px-4 py-2 border rounded text-gray-700 hover:bg-gray-50">Clear</a>
        </div>
    </form>

    <?php if (empty($logs)): ?>
        <p class="text-gray-600">No audit logs to display.</p>
    <?php else: ?>
        <table class="w-full bg-white border rounded">
            <thead>
                <tr class="text-left">
                    <th class="p-2">Time</th>
                    <th class="p-2">User</th>
                    <th class="p-2">Action</th>
                    <th class="p-2">Entity</th>
                    <th class="p-2">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                    <tr class="border-t">
                        <td class="p-2"><?= htmlspecialchars($l['created_at'] ?? '') ?></td>
                        <td class="p-2"><?= htmlspecialchars($l['user_email'] ?? $l['user_id'] ?? 'system') ?></td>
                        <td class="p-2"><?= htmlspecialchars($l['action'] ?? '') ?></td>
                        <td class="p-2"><?= htmlspecialchars((string) ($l['entity_type'] ?? $l['entity'] ?? '')) ?> #<?= htmlspecialchars((string) ($l['entity_id'] ?? 'n/a')) ?></td>
                        <td class="p-2 text-sm text-gray-700"><?= htmlspecialchars(json_encode($l['new_values'] ?? $l['meta'] ?? [])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>