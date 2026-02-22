<?php
$title = 'Admin Dashboard - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-4xl font-bold mb-8">Admin Dashboard</h1>

    <?php
    $orgs = isset($organizations) ? $organizations : [];
    $logs = isset($auditLogs) ? $auditLogs : [];
    $totalOrgs = is_array($orgs) ? count($orgs) : 0;
    $totalLogs = is_array($logs) ? count($logs) : 0;
    ?>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-medium mb-2">Organizations</h3>
            <p class="text-3xl font-bold text-red-600"><?php echo $totalOrgs; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-medium mb-2">Audit Entries</h3>
            <p class="text-3xl font-bold text-red-600"><?php echo $totalLogs; ?></p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-4">Recent Audit Logs</h2>
            <?php if (empty($logs)): ?>
                <p class="text-gray-600">No audit logs available.</p>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach (array_slice($logs, 0, 10) as $log): ?>
                        <li class="border rounded p-3">
                            <div class="text-sm text-gray-500"><?php echo $log['created_at'] ?? ''; ?> — <?php echo $log['action'] ?? ''; ?></div>
                            <div class="text-base font-medium"><?php echo $log['description'] ?? ($log['entity'] ?? ''); ?></div>
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
                                <div class="text-sm text-gray-500"><?php echo $o['genre'] ?? ''; ?></div>
                            </div>
                            <a href="/admin/organizations/edit/<?php echo $o['id'] ?? ''; ?>" class="text-red-600 hover:underline">Edit</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
