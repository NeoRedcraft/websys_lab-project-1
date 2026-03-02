<?php
$title = 'Talent Directory - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-12">
        <h1 class="text-4xl font-bold mb-2">Talent Directory</h1>
        <p class="text-gray-600 mb-8">Discover amazing performing organizations for your events</p>
        
        <!-- Search and Filter Section -->
        <div class="mb-8">
            <input 
                type="text" 
                id="searchInput" 
                placeholder="Search by organization name or genre..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
            >
        </div>
    </div>

    <!-- Organizations Grid -->
    <div id="organizationsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Organizations will be loaded here via JavaScript -->
        <div class="col-span-full text-center py-12">
            <p class="text-gray-500">Loading organizations...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('organizationsContainer');
    const searchInput = document.getElementById('searchInput');
    let allOrganizations = [];

    // Fetch organizations
    fetch('/api/organizations/directory')
        .then(response => response.json())
        .then(data => {
            allOrganizations = data.data || [];
            renderOrganizations(allOrganizations);
        })
        .catch(error => {
            console.error('Error loading organizations:', error);
            container.innerHTML = '<div class="col-span-full text-center text-red-600">Error loading organizations</div>';
        });

    // Search functionality
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const filtered = allOrganizations.filter(org => 
            org.name.toLowerCase().includes(query) || 
            (org.genre && org.genre.toLowerCase().includes(query))
        );
        renderOrganizations(filtered);
    });

    function renderOrganizations(organizations) {
        if (organizations.length === 0) {
            container.innerHTML = '<div class="col-span-full text-center py-12 text-gray-500">No organizations found</div>';
            return;
        }

        container.innerHTML = organizations.map(org => `
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="bg-gradient-to-r from-red-600 to-red-700 h-48 flex items-center justify-center">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-white">${org.name.charAt(0)}</div>
                        <div class="text-white text-sm mt-2">${org.genre || 'Organization'}</div>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-2">${org.name}</h3>
                    <p class="text-gray-600 text-sm mb-4">
                        ${org.bio ? org.bio.substring(0, 100) + (org.bio.length > 100 ? '...' : '') : 'Professional performing organization'}
                    </p>
                    
                    <!-- Genre Tag -->
                    ${org.genre ? `<div class="mb-3"><span class="inline-block bg-red-100 text-red-700 text-xs px-3 py-1 rounded-full">${org.genre}</span></div>` : ''}
                    
                    <!-- Upcoming Bookings Count -->
                    <div class="text-xs text-gray-500 mb-4">
                        📅 ${org.upcoming_bookings_count || 0} upcoming booking${org.upcoming_bookings_count !== 1 ? 's' : ''}
                    </div>
                    
                    <a href="/organizations/${org.id}" class="w-full block bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 text-center transition">
                        View Profile
                    </a>
                </div>
            </div>
        `).join('');
    }
});
</script>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
