<?php
$title = 'Talent Directory - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-12">
        <h1 class="text-4xl font-bold mb-2">Talent Directory</h1>
        <p class="text-gray-600 mb-8">Discover amazing performing organizations for your events</p>

        <form id="directorySearchForm" action="/search-results" method="GET" class="mb-8 bg-white rounded-lg shadow p-6">
            <label for="q" class="block text-sm font-semibold text-gray-700 mb-2">Search Organizations</label>
            <div class="flex gap-3">
                <input
                    type="text"
                    id="q"
                    name="q"
                    placeholder="Search by organization name, genre, or bio"
                    class="flex-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                    autocomplete="off"
                >
                <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                    Search
                </button>
            </div>
        </form>
    </div>

    <div id="organizationsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="col-span-full bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600">Loading organizations...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('directorySearchForm');
    const input = document.getElementById('q');
    const container = document.getElementById('organizationsContainer');
    let organizations = [];

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderOrganizations(items) {
        if (!Array.isArray(items) || items.length === 0) {
            container.innerHTML = '<div class="col-span-full bg-white rounded-lg shadow p-8 text-center"><p class="text-gray-600">No organizations found.</p></div>';
            return;
        }

        container.innerHTML = items.map(function (organization) {
            const name = organization.name || 'Organization';
            const genre = organization.genre || 'Organization';
            const bio = (organization.bio || '').trim();
            const summary = bio ? (bio.length > 140 ? bio.substring(0, 140) + '...' : bio) : 'Professional performing organization';
            const upcomingCount = Number(organization.upcoming_bookings_count || 0);
            const imageUrl = organization.image_url || organization.image || organization.logo_url || '';

            const banner = imageUrl
                ? '<div class="h-40 overflow-hidden">'
                  + '<img src="' + escapeHtml(imageUrl) + '" class="w-full h-full object-cover" alt="' + escapeHtml(name) + '" />'
                  + '</div>'
                : '<div class="bg-gradient-to-r from-red-600 to-red-700 h-40 flex items-center justify-center">'
                  + '<div class="text-center text-white">'
                  + '<div class="text-3xl font-bold">' + escapeHtml(name.substring(0, 1)) + '</div>'
                  + '<div class="text-sm mt-2">' + escapeHtml(genre) + '</div>'
                  + '</div></div>';

            return '<div class="bg-white rounded-lg shadow-md overflow-hidden">'
                + banner
                + '<div class="p-5">'
                + '<h2 class="text-lg font-bold mb-2">' + escapeHtml(name) + '</h2>'
                + '<p class="text-sm text-gray-600 mb-3">' + escapeHtml(summary) + '</p>'
                + '<div class="text-xs text-gray-500 mb-4">' + upcomingCount + ' upcoming booking' + (upcomingCount === 1 ? '' : 's') + '</div>'
                + '<a href="/organizations/' + Number(organization.id || 0) + '" class="w-full block bg-red-600 text-white py-2 rounded-lg text-center hover:bg-red-700 transition">View</a>'
                + '</div></div>';
        }).join('');
    }

    function filterOrganizations(term) {
        const query = term.trim().toLowerCase();
        if (!query) {
            renderOrganizations(organizations);
            return;
        }

        const filtered = organizations.filter(function (organization) {
            const name = (organization.name || '').toLowerCase();
            const genre = (organization.genre || '').toLowerCase();
            const bio = (organization.bio || '').toLowerCase();
            return name.includes(query) || genre.includes(query) || bio.includes(query);
        });

        renderOrganizations(filtered);
    }

    fetch('/api/organizations/directory')
        .then(function (response) { return response.json(); })
        .then(function (data) {
            organizations = Array.isArray(data.data) ? data.data : [];
            renderOrganizations(organizations);
        })
        .catch(function () {
            organizations = [];
            container.innerHTML = '<div class="col-span-full bg-white rounded-lg shadow p-8 text-center"><p class="text-red-600">Error loading organizations.</p></div>';
        });

    input.addEventListener('input', function (event) {
        filterOrganizations(event.target.value);
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        filterOrganizations(input.value);
    });
});
</script>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>