<?php
$title = 'Edit Organization Profile - Cardinal Stage';
ob_start();
?>
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Edit Organization Profile
            </h2>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="/org-admin/profile"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancel
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="rounded-md bg-red-50 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="rounded-md bg-green-50 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">
                        <?php echo htmlspecialchars($success); ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <form action="/org-admin/profile/edit" method="POST" class="space-y-8 divide-y divide-gray-200 p-6 sm:p-8">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <div class="space-y-8 divide-y divide-gray-200">
                <div>
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Profile Content
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Update the public-facing information for
                            <?php echo htmlspecialchars($organization['name'] ?? 'your organization'); ?>.
                        </p>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label for="genre" class="block text-sm font-medium text-gray-700">
                                Genre / Category
                            </label>
                            <div class="mt-1">
                                <input type="text" name="genre" id="genre"
                                    value="<?php echo htmlspecialchars($organization['genre'] ?? ''); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>

                        <div class="sm:col-span-6">
                            <label for="bio" class="block text-sm font-medium text-gray-700">
                                Bio
                            </label>
                            <div class="mt-1">
                                <textarea id="bio" name="bio" rows="4"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border border-gray-300 rounded-md"><?php echo htmlspecialchars($organization['bio'] ?? ''); ?></textarea>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Write a few sentences about the organization.</p>
                        </div>

                        <div class="sm:col-span-6">
                            <label for="technical_requirements" class="block text-sm font-medium text-gray-700">
                                Technical Requirements
                            </label>
                            <div class="mt-1">
                                <textarea id="technical_requirements" name="technical_requirements" rows="4"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border border-gray-300 rounded-md"><?php echo htmlspecialchars($organization['technical_requirements'] ?? ''); ?></textarea>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">List equipment and space requirements.</p>
                        </div>

                        <div class="sm:col-span-6">
                            <label for="youtube_links" class="block text-sm font-medium text-gray-700">
                                YouTube Links
                            </label>
                            <div class="mt-1">
                                <textarea id="youtube_links" name="youtube_links" rows="3"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border border-gray-300 rounded-md"
                                    placeholder="Enter valid YouTube URLs separated by commas"><?php echo htmlspecialchars($organization['youtube_links'] ?? ''); ?></textarea>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Add YouTube links as a comma-separated list.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-5">
                <div class="flex justify-end">
                    <button type="submit"
                        class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="mt-10 bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-red-700">
                Danger Zone
            </h3>
            <div class="mt-2 max-w-xl text-sm text-gray-500">
                <p>
                    Permanently delete this organization profile. This action cannot be undone, and you will lose all
                    data and bookings associated with it. You will be signed out immediately.
                </p>
            </div>
            <div class="mt-5 border-t border-gray-200 pt-5 text-right">
                <form action="/org-admin/profile/delete" method="POST"
                    onsubmit="return confirm('Are you absolutely sure you want to delete this organization? This action CANNOT be undone.');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm">
                        Delete Organization
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include app_path('views/layout/app.php');
