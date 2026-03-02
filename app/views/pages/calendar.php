<?php
$title = 'Event Calendar - Cardinal Stage';
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Event Calendar</h1>
        <p class="text-gray-600 mb-6">Browse all upcoming events from our performing organizations</p>
        
        <!-- View Toggle -->
        <div class="flex gap-4 mb-6">
            <button id="calendarViewBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                Calendar View
            </button>
            <button id="listViewBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                List View
            </button>
        </div>
    </div>

    <!-- Calendar View -->
    <div id="calendarView" class="bg-white rounded-lg shadow-lg p-6">
        <div id="calendar"></div>
    </div>

    <!-- List View -->
    <div id="listView" class="hidden">
        <div class="space-y-4">
            <input 
                type="text" 
                id="searchInput" 
                placeholder="Search by organization, venue, or event name..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
            >
            <div id="eventsList" class="space-y-4 mt-4">
                <!-- Events will load here -->
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
let calendar;
let allEvents = [];

document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    loadEvents();
    setupViewToggle();
    setupSearch();
});

function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        height: 'auto',
        events: function(info, successCallback, failureCallback) {
            // Events will be populated by loadEvents()
            successCallback(allEvents);
        },
        eventClick: function(info) {
            showEventDetails(info.event.extendedProps);
        },
        eventDisplay: 'block'
    });
    calendar.render();
}

function loadEvents() {
    fetch('/api/calendar/events')
        .then(response => response.json())
        .then(data => {
            allEvents = data.data || [];
            
            // Refresh calendar with new events
            if (calendar) {
                calendar.refetchEvents();
            }
            
            // Populate list view
            renderEventsList(allEvents);
        })
        .catch(error => {
            console.error('Error loading events:', error);
        });
}

function renderEventsList(events) {
    const listContainer = document.getElementById('eventsList');
    
    if (events.length === 0) {
        listContainer.innerHTML = '<div class="text-center py-8 text-gray-500">No events found</div>';
        return;
    }

    listContainer.innerHTML = events
        .sort((a, b) => new Date(a.start) - new Date(b.start))
        .map(event => {
            const startDate = new Date(event.start);
            const formattedDate = startDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            const formattedTime = startDate.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });

            return `
                <div class="bg-white border-l-4 border-red-600 p-4 rounded shadow hover:shadow-lg transition cursor-pointer" onclick="showEventDetails(${JSON.stringify(event.extendedProps).replace(/"/g, '&quot;')})">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-lg font-bold">${event.title}</h3>
                        <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded">${event.extendedProps.organization_name || 'Organization'}</span>
                    </div>
                    <p class="text-gray-600 text-sm mb-2">
                        📅 ${formattedDate}
                    </p>
                    ${event.extendedProps.venue ? `<p class="text-gray-600 text-sm mb-2">📍 ${event.extendedProps.venue}</p>` : ''}
                    ${event.extendedProps.technical_needs ? `<p class="text-gray-600 text-sm">🎛️ ${event.extendedProps.technical_needs}</p>` : ''}
                </div>
            `;
        })
        .join('');
}

function showEventDetails(event) {
    // Create and show modal with full event details
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    
    const content = document.createElement('div');
    content.className = 'bg-white rounded-lg p-8 max-w-md w-full mx-4';
    content.innerHTML = `
        <div class="mb-4">
            <h2 class="text-2xl font-bold mb-2">${event.event_name || 'Event'}</h2>
            <p class="text-red-600 font-semibold">${event.organization_name || 'Organization'}</p>
        </div>
        
        <div class="space-y-3 mb-6">
            <p><strong>📅 Date:</strong> ${new Date(event.event_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
            ${event.venue ? `<p><strong>📍 Venue:</strong> ${event.venue}</p>` : ''}
            ${event.technical_needs ? `<p><strong>🎛️ Technical Needs:</strong> ${event.technical_needs}</p>` : ''}
        </div>
        
        <div class="flex gap-2">
            <button onclick="this.closest('.fixed').remove()" class="flex-1 px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Close</button>
            <a href="/bookings/create?org_id=${event.organization_id || ''}" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-center">Request Booking</a>
        </div>
    `;
    
    modal.appendChild(content);
    modal.onclick = function(e) {
        if (e.target === modal) modal.remove();
    };
    document.body.appendChild(modal);
}

function setupViewToggle() {
    document.getElementById('calendarViewBtn').addEventListener('click', function() {
        document.getElementById('calendarView').classList.remove('hidden');
        document.getElementById('listView').classList.add('hidden');
        document.getElementById('calendarViewBtn').classList.add('bg-red-600', 'text-white');
        document.getElementById('calendarViewBtn').classList.remove('bg-gray-200', 'text-gray-800');
        document.getElementById('listViewBtn').classList.remove('bg-red-600', 'text-white');
        document.getElementById('listViewBtn').classList.add('bg-gray-200', 'text-gray-800');
    });

    document.getElementById('listViewBtn').addEventListener('click', function() {
        document.getElementById('calendarView').classList.add('hidden');
        document.getElementById('listView').classList.remove('hidden');
        document.getElementById('listViewBtn').classList.add('bg-red-600', 'text-white');
        document.getElementById('listViewBtn').classList.remove('bg-gray-200', 'text-gray-800');
        document.getElementById('calendarViewBtn').classList.remove('bg-red-600', 'text-white');
        document.getElementById('calendarViewBtn').classList.add('bg-gray-200', 'text-gray-800');
    });
}

function setupSearch() {
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const filtered = allEvents.filter(event => 
            (event.title && event.title.toLowerCase().includes(query)) ||
            (event.extendedProps.organization_name && event.extendedProps.organization_name.toLowerCase().includes(query)) ||
            (event.extendedProps.venue && event.extendedProps.venue.toLowerCase().includes(query))
        );
        renderEventsList(filtered);
    });
}
</script>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>
