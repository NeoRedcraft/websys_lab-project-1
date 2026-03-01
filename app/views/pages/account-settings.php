<?php
$title = 'Account Settings - Cardinal Stage';
ob_start();
require_auth();
$user = get_user();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-4xl font-bold mb-8">Account Settings</h1>

    <?php if ($success = session_get('success')): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
        </div>
        <?php session_forget('success'); // Clear the message after showing it ?>
    <?php endif; ?>

    <?php if ($error = session_get('error')): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php session_forget('error'); // Clear the message after showing it ?>
    <?php endif; ?>
    <div class="bg-white rounded-lg shadow-md p-8">
        
        <form method="POST" action="/account/update-profile" class="space-y-6">
            <h2 class="text-2xl font-bold mb-6">Profile Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($user['user_metadata']['name'] ?? $user['name'] ?? ''); ?>"                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                    />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                        disabled
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500"
                    />
                    <p class="text-xs text-gray-500 mt-1">Email cannot be changed.</p>
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                    <input
                        type="text"
                        id="role"
                        value="<?php echo htmlspecialchars($user['role'] ?? ''); ?>"
                        disabled
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500"
                    />
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 font-medium shadow-sm">
                    Update Profile
                </button>
            </div>
        </form>

        <form method="POST" action="/account/change-password" class="space-y-6 mt-8 border-t pt-8">
            <h3 class="text-lg font-bold mb-4">Security</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                    />
                </div>
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                    />
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900 font-medium shadow-sm">
                    Change Password
                </button>
            </div>
        </form>

        <form method="POST" action="/account/delete" class="mt-8 border-t pt-8" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.');">
            <h3 class="text-lg font-bold mb-4 text-red-600">Danger Zone</h3>
            <button type="submit" class="border border-red-600 text-red-600 bg-white px-6 py-2 rounded-lg hover:bg-red-50 font-medium">
                Delete Account
            </button>
        </form>

    </div>
</div>

<?php 
$content = ob_get_clean(); 
include app_path('views/layout/app.php'); 
?>