<?php
$title = '404 - Page Not Found';
ob_start();
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-red-600 mb-4">404</h1>
        <p class="text-2xl font-bold text-gray-900 mb-4">Page Not Found</p>
        <p class="text-gray-600 mb-8">Sorry, the page you're looking for doesn't exist.</p>
        <a href="/" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700">
            Go Home
        </a>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
