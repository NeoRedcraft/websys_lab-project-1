<?php
$title = 'Talent Directory - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-4xl font-bold mb-4">Talent Directory</h1>
    <p class="text-gray-600 mb-8">Discover amazing talent for your events</p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
            <div class="bg-gray-300 h-48"></div>
            <div class="p-6">
                <h3 class="text-lg font-bold mb-2">Talented Performer</h3>
                <p class="text-gray-600 mb-4">Professional dancer and choreographer</p>
                <button class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">
                    View Profile
                </button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
            <div class="bg-gray-300 h-48"></div>
            <div class="p-6">
                <h3 class="text-lg font-bold mb-2">Skilled Artist</h3>
                <p class="text-gray-600 mb-4">Award-winning musician</p>
                <button class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">
                    View Profile
                </button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
            <div class="bg-gray-300 h-48"></div>
            <div class="p-6">
                <h3 class="text-lg font-bold mb-2">Creative Talent</h3>
                <p class="text-gray-600 mb-4">Professional event coordinator</p>
                <button class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">
                    View Profile
                </button>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
