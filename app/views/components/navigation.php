<nav class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="/" class="flex items-center">
                    <span class="text-xl font-bold text-red-600">Cardinal Stage</span>
                </a>
            </div>

            <div class="hidden md:flex items-center space-x-8">
                <a href="/" class="text-gray-700 hover:text-red-600">Home</a>
                <a href="/directory" class="text-gray-700 hover:text-red-600">Talent Directory</a>
                <a href="/calendar" class="text-gray-700 hover:text-red-600">Calendar</a>
                
                <?php if (auth_check()): ?>
                    <div class="relative group">
                        <button class="text-gray-700 hover:text-red-600 flex items-center">
                            <?php echo htmlspecialchars(get_display_name(get_user())); ?>
                            <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-opacity">
                            <?php if (user_has_role('admin')): ?>
                                <a href="/admin" class="block px-4 py-2 text-gray-700 hover:bg-red-50">Admin Dashboard</a>
                            <?php endif; ?>
                            <?php if (user_has_role('org_admin')): ?>
                                <a href="/org-admin" class="block px-4 py-2 text-gray-700 hover:bg-red-50">Org Admin Dashboard</a>
                            <?php endif; ?>
                            <?php if (user_has_role('organizer')): ?>
                                <a href="/dashboard" class="block px-4 py-2 text-gray-700 hover:bg-red-50">Dashboard</a>
                            <?php endif; ?>
                            <a href="/account" class="block px-4 py-2 text-gray-700 hover:bg-red-50">Account Settings</a>
                            <a href="/signout" class="block px-4 py-2 text-gray-700 hover:bg-red-50 border-t">Sign Out</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/signin" class="text-gray-700 hover:text-red-600">Sign In</a>
                    <a href="/signup" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
