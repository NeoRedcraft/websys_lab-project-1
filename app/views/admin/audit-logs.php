<?php
$title = 'Audit Logs';
ob_start();
?>

<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Audit Logs</h1>

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
                        <td class="p-2"><?= htmlspecialchars($l['entity_type'] ?? $l['entity'] ?? '') ?></td>
                        <td class="p-2 text-sm text-gray-700"><?= htmlspecialchars(json_encode($l['meta'] ?? [])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>