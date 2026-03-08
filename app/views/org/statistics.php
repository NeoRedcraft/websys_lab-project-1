<?php
$title = 'Organization Statistics - Cardinal Stage';
ob_start();
?>
<div class="max-w-7xl mx-auto py-8 mb-8 px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Organization Statistics</h1>
            <p class="mt-2 text-sm text-gray-700">A snapshot of your organization's performance and bookings.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="/org-admin"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">
                    Total Bookings
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">
                    <?php echo htmlspecialchars($stats['total'] ?? 0); ?>
                </dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">
                    Pending Requests
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">
                    <?php echo htmlspecialchars($stats['pending'] ?? 0); ?>
                </dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">
                    Accepted Bookings
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-green-600">
                    <?php echo htmlspecialchars($stats['accepted'] ?? 0); ?>
                </dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg sm:col-span-3">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">
                    Declined/Cancelled
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-red-600">
                    <?php
                    $total = $stats['total'] ?? 0;
                    $pending = $stats['pending'] ?? 0;
                    $accepted = $stats['accepted'] ?? 0;
                    echo htmlspecialchars($total - ($pending + $accepted));
                    ?>
                </dd>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include app_path('views/layout/app.php');
