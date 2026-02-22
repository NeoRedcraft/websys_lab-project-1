<?php
$title = 'Home - Cardinal Stage';
ob_start();
?>

<div class="bg-gradient-to-r from-red-600 to-red-700 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Welcome to Cardinal Stage</h1>
        <p class="text-xl mb-8 text-red-100">Discover and manage talent for your events</p>
        
        <?php if (!$isAuthenticated): ?>
            <div class="flex gap-4">
                <a href="/signup" class="bg-white text-red-600 px-6 py-3 rounded-lg font-bold hover:bg-red-50">
                    Get Started
                </a>
                <a href="/signin" class="border-2 border-white text-white px-6 py-3 rounded-lg font-bold hover:bg-white hover:bg-opacity-10">
                    Sign In
                </a>
            </div>
        <?php else: ?>
            <div class="flex gap-4">
                <a href="/dashboard" class="bg-white text-red-600 px-6 py-3 rounded-lg font-bold hover:bg-red-50">
                    Go to Dashboard
                </a>
                <a href="/directory" class="border-2 border-white text-white px-6 py-3 rounded-lg font-bold hover:bg-white hover:bg-opacity-10">
                    View Talent Directory
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white p-8 rounded-lg shadow-md hover:shadow-lg transition">
            <div class="text-3xl mb-4">🎭</div>
            <h3 class="text-xl font-bold mb-2">Find Talent</h3>
            <p class="text-gray-600">Browse our extensive talent directory and discover amazing performers.</p>
        </div>
        <div class="bg-white p-8 rounded-lg shadow-md hover:shadow-lg transition">
            <div class="text-3xl mb-4">📅</div>
            <h3 class="text-xl font-bold mb-2">Manage Events</h3>
            <p class="text-gray-600">Organize and manage your events with ease using our platform.</p>
        </div>
        <div class="bg-white p-8 rounded-lg shadow-md hover:shadow-lg transition">
            <div class="text-3xl mb-4">🤝</div>
            <h3 class="text-xl font-bold mb-2">Connect & Collaborate</h3>
            <p class="text-gray-600">Connect with organizers and build lasting professional relationships.</p>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
