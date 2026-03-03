<?php
$title = 'Organization Statistics - Cardinal Stage';
ob_start();

$pending = (int) ($stats['pending'] ?? 0);
$accepted = (int) ($stats['accepted'] ?? 0);
$declined = (int) ($stats['declined'] ?? 0);
$total = $pending + $accepted + $declined;
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Organization Statistics</h1>
        <a href="/org-admin/dashboard" class="text-gray-700 hover:text-gray-900">Back to Dashboard</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border rounded-lg p-5 shadow-sm">
            <p class="text-sm text-gray-500">Total Requests</p>
            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $total; ?></p>
        </div>
        <div class="bg-white border rounded-lg p-5 shadow-sm">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="text-3xl font-bold text-yellow-600 mt-1"><?php echo $pending; ?></p>
        </div>
        <div class="bg-white border rounded-lg p-5 shadow-sm">
            <p class="text-sm text-gray-500">Accepted</p>
            <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $accepted; ?></p>
        </div>
        <div class="bg-white border rounded-lg p-5 shadow-sm">
            <p class="text-sm text-gray-500">Declined</p>
            <p class="text-3xl font-bold text-red-600 mt-1"><?php echo $declined; ?></p>
        </div>
    </div>

    <div class="bg-white border rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-2">Summary</h2>
        <p class="text-gray-700">
            <?php echo $accepted; ?> accepted, <?php echo $pending; ?> pending, and <?php echo $declined; ?> declined booking requests.
        </p>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
