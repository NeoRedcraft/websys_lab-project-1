<footer class="bg-gray-900 text-white mt-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-4">Cardinal Stage</h3>
                <p class="text-gray-400">Your platform for talent management and event organization.</p>
            </div>
            <div>
                <h4 class="font-bold mb-4">Quick Links</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="/" class="hover:text-white">Home</a></li>
                    <li><a href="/directory" class="hover:text-white">Talent Directory</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4">Account</h4>
                <ul class="space-y-2 text-gray-400">
                    <?php if (auth_check()): ?>
                        <li><a href="/account" class="hover:text-white">Settings</a></li>
                        <li><a href="/signout" class="hover:text-white">Sign Out</a></li>
                    <?php else: ?>
                        <li><a href="/signin" class="hover:text-white">Sign In</a></li>
                        <li><a href="/signup" class="hover:text-white">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4">Legal</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                    <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> Cardinal Stage. All rights reserved.</p>
        </div>
    </div>
</footer>
