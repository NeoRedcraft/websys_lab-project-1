<?php
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestPath = rtrim($requestPath, '/');
if ($requestPath === '') {
    $requestPath = '/';
}

$navLinkClasses = static function (bool $isActive): string {
    $base = 'border-b-2 pb-1 transition '; 
    return $base . ($isActive
        ? 'border-red-600 text-red-600'
        : 'border-transparent text-gray-700 hover:text-red-600');
};

$mobileNavLinkClasses = static function (bool $isActive): string {
    $base = 'block px-2 py-2 rounded-md transition ';
    return $base . ($isActive
        ? 'bg-red-50 text-red-700'
        : 'text-gray-700 hover:bg-gray-50 hover:text-red-600');
};

$matchesRoute = static function (array $exact = [], array $prefixes = []) use ($requestPath): bool {
    foreach ($exact as $path) {
        if ($requestPath === $path) {
            return true;
        }
    }

    foreach ($prefixes as $prefix) {
        if (strpos($requestPath, $prefix) === 0) {
            return true;
        }
    }

    return false;
};

$isAuthed = auth_check();
$role = $isAuthed ? session_get('role') : null;
$bookingsPath = null;
$accountAreaActive = $matchesRoute(
    ['/admin', '/admin/dashboard', '/org-admin', '/org-admin/dashboard', '/dashboard', '/organizer-dashboard', '/account'],
    ['/admin/', '/org-admin/profile', '/org-admin/statistics', '/account/']
);

if ($role === 'system_admin') {
    $bookingsPath = '/bookings';
} elseif ($role === 'org_admin') {
    $bookingsPath = '/org-admin/bookings';
} elseif ($role === 'organizer') {
    $bookingsPath = '/bookings';
}
?>

<nav class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="/" class="flex items-center">
                    <span class="text-xl font-bold text-red-600">Cardinal Stage</span>
                </a>
            </div>

            <button
                type="button"
                class="md:hidden inline-flex items-center justify-center rounded-md p-2 text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-500"
                id="mobile-nav-toggle"
                aria-controls="mobile-nav-menu"
                aria-expanded="false"
                onclick="var m=document.getElementById('mobile-nav-menu');var ex=this.getAttribute('aria-expanded')==='true';this.setAttribute('aria-expanded',ex?'false':'true');if(m){m.classList.toggle('hidden');}"
            >
                <span class="sr-only">Open main menu</span>
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <div class="hidden md:flex items-center space-x-8">
                <a href="/" class="<?php echo $navLinkClasses($matchesRoute(['/'])); ?>">Home</a>
                <a href="/directory" class="<?php echo $navLinkClasses($matchesRoute(['/directory', '/search-results'], ['/organizations/'])); ?>">Talent Directory</a>
                <a href="/calendar" class="<?php echo $navLinkClasses($matchesRoute(['/calendar'])); ?>">Calendar</a>
                
                <?php if ($isAuthed): ?>
                    <?php if ($bookingsPath): ?>
                        <a href="<?php echo $bookingsPath; ?>" class="<?php echo $navLinkClasses($matchesRoute([], ['/bookings', '/org-admin/bookings'])); ?>">Bookings</a>
                    <?php endif; ?>

                    <div class="relative group">
                        <button class="<?php echo $navLinkClasses($accountAreaActive); ?> flex items-center">
                            <?php echo htmlspecialchars(get_display_name(get_user())); ?>
                            <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div class="absolute right-0 top-full mt-1 w-48 bg-white rounded-md shadow-lg opacity-0 invisible pointer-events-none group-hover:opacity-100 group-hover:visible group-hover:pointer-events-auto group-focus-within:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto transition-opacity">
                            <?php if (user_has_role('system_admin')): ?>
                                <a href="/admin" class="block px-4 py-2 text-gray-700 hover:bg-red-50">Admin Dashboard</a>
                                <a href="/admin/users" class="block px-4 py-2 text-gray-700 hover:bg-red-50">User Management</a>
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
                    <a href="/signin" class="<?php echo $navLinkClasses($matchesRoute(['/signin'])); ?>">Sign In</a>
                    <a href="/signup" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>

        <div id="mobile-nav-menu" class="hidden md:hidden border-t border-gray-200 py-3">
            <div class="space-y-1">
                <a href="/" class="<?php echo $mobileNavLinkClasses($matchesRoute(['/'])); ?>">Home</a>
                <a href="/directory" class="<?php echo $mobileNavLinkClasses($matchesRoute(['/directory', '/search-results'], ['/organizations/'])); ?>">Talent Directory</a>
                <a href="/calendar" class="<?php echo $mobileNavLinkClasses($matchesRoute(['/calendar'])); ?>">Calendar</a>

                <?php if ($isAuthed): ?>
                    <?php if ($bookingsPath): ?>
                        <a href="<?php echo $bookingsPath; ?>" class="<?php echo $mobileNavLinkClasses($matchesRoute([], ['/bookings', '/org-admin/bookings'])); ?>">Bookings</a>
                    <?php endif; ?>

                    <?php if (user_has_role('system_admin')): ?>
                        <a href="/admin" class="<?php echo $mobileNavLinkClasses($matchesRoute(['/admin', '/admin/dashboard'], ['/admin/'])); ?>">Admin Dashboard</a>
                        <a href="/admin/users" class="<?php echo $mobileNavLinkClasses($matchesRoute(['/admin/users'], ['/admin/users'])); ?>">User Management</a>
                    <?php endif; ?>
                    <?php if (user_has_role('org_admin')): ?>
                        <a href="/org-admin" class="<?php echo $mobileNavLinkClasses($matchesRoute(['/org-admin', '/org-admin/dashboard'], ['/org-admin/'])); ?>">Org Admin Dashboard</a>
                    <?php endif; ?>
                    <?php if (user_has_role('organizer')): ?>
                        <a href="/dashboard" class="<?php echo $mobileNavLinkClasses($matchesRoute(['/dashboard', '/organizer-dashboard'])); ?>">Dashboard</a>
                    <?php endif; ?>

                    <a href="/account" class="<?php echo $mobileNavLinkClasses($matchesRoute(['/account'], ['/account/'])); ?>">Account Settings</a>
                    <a href="/signout" class="<?php echo $mobileNavLinkClasses(false); ?> border-t border-gray-100 mt-2 pt-3">Sign Out</a>
                <?php else: ?>
                    <a href="/signin" class="<?php echo $mobileNavLinkClasses($matchesRoute(['/signin'])); ?>">Sign In</a>
                    <a href="/signup" class="block mt-2 bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-center">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
