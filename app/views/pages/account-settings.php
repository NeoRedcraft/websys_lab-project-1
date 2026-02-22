<?php
$title = 'Account Settings - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-4xl font-bold mb-8">Account Settings</h1>

    <div class="bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold mb-6">Profile Information</h2>

        <form class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        value="<?php echo htmlspecialchars($user['email']); ?>"
                        disabled
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500"
                    />
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                    <input
                        type="text"
                        id="role"
                        value="<?php echo htmlspecialchars($user['role']); ?>"
                        disabled
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500"
                    />
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="text-lg font-bold mb-4">Security</h3>
                <button type="button" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                    Change Password
                </button>
            </div>

            <div class="border-t pt-6">
                <h3 class="text-lg font-bold mb-4 text-red-600">Danger Zone</h3>
                <button type="button" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                    Delete Account
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
