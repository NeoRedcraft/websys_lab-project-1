<?php
$title = 'Account Settings - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-4xl font-bold mb-8">Account Settings</h1>

    <?php if (!empty($_GET['success'])): ?>
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold mb-6">Profile Information</h2>

        <form class="space-y-6" method="post" action="/account/password">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
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
                        value="<?php echo htmlspecialchars(session_get('role', 'user')); ?>"
                        disabled
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500"
                    />
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="text-lg font-bold mb-4">Security</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" id="new_password" name="new_password" minlength="8" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="8" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>
                <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
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
