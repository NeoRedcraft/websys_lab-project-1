<?php
$title = 'Booking Inbox - Cardinal Stage';
ob_start();
?>
<div class="max-w-7xl mx-auto py-8 mb-8 px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Booking Inbox</h1>
            <p class="mt-2 text-sm text-gray-700">A list of all incoming booking requests for your organization.</p>
        </div>
    </div>

    <!-- Feedback Messages -->
    <div id="alert-container" class="mt-4 hidden">
        <div id="alert-message" class="rounded-md p-4"></div>
    </div>

    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Event Details</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Dates</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Location</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php if (empty($bookingRequests)): ?>
                                <tr>
                                    <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">No booking requests found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookingRequests as $booking): ?>
                                    <tr id="booking-row-<?php echo htmlspecialchars($booking['id']); ?>">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['event_name']); ?></div>
                                            <div class="text-gray-500 text-xs">Created: <?php echo htmlspecialchars(date('M d, Y', strtotime($booking['created_at']))); ?></div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <div>Start: <?php echo htmlspecialchars(date('M d, Y', strtotime($booking['event_start']))); ?></div>
                                            <div>End: <?php echo htmlspecialchars(date('M d, Y', strtotime($booking['event_end']))); ?></div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($booking['location']); ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                                            <span id="status-badge-<?php echo htmlspecialchars($booking['id']); ?>" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?php echo $booking['status'] === 'pending' ? 'yellow-100 text-yellow-800' : ($booking['status'] === 'accepted' ? 'green-100 text-green-800' : 'red-100 text-red-800'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <a href="/org-admin/bookings/<?php echo htmlspecialchars($booking['id']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">View</a>
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <button type="button" onclick="openActionModal(<?php echo htmlspecialchars($booking['id']); ?>, 'accept')" class="text-green-600 hover:text-green-900 mr-4">Approve</button>
                                                <button type="button" onclick="openActionModal(<?php echo htmlspecialchars($booking['id']); ?>, 'decline')" class="text-red-600 hover:text-red-900">Decline</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Approve/Decline Actions -->
<div id="action-modal" class="fixed inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                    <span id="modal-action-text">Action</span> Booking
                </h3>
                <div class="mt-2">
                    <form id="action-form" onsubmit="submitAction(event)">
                        <input type="hidden" name="booking_id" id="modal-booking-id">
                        <input type="hidden" id="modal-action-type">
                        <div>
                            <label for="modal-input" class="block text-sm font-medium text-gray-700" id="modal-input-label">Notes</label>
                            <div class="mt-1">
                                <textarea id="modal-input" name="modal_input" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" required></textarea>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="submitAction(event)" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm" id="modal-submit-btn">
                    Submit
                </button>
                <button type="button" onclick="closeActionModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const csrfToken = '<?php echo $csrfToken; ?>';

    function openActionModal(bookingId, action) {
        document.getElementById('modal-booking-id').value = bookingId;
        document.getElementById('modal-action-type').value = action;
        
        const titleSpan = document.getElementById('modal-action-text');
        const inputLabel = document.getElementById('modal-input-label');
        const submitBtn = document.getElementById('modal-submit-btn');
        const inputField = document.getElementById('modal-input');
        
        if (action === 'accept') {
            titleSpan.textContent = 'Approve';
            inputLabel.textContent = 'Approval Notes (Optional)';
            inputField.removeAttribute('required');
            submitBtn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm';
            submitBtn.textContent = 'Approve Booking';
        } else {
            titleSpan.textContent = 'Decline';
            inputLabel.textContent = 'Reason for Declining (Required)';
            inputField.setAttribute('required', 'required');
            submitBtn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm';
            submitBtn.textContent = 'Decline Booking';
        }

        document.getElementById('action-modal').classList.remove('hidden');
    }

    function closeActionModal() {
        document.getElementById('action-modal').classList.add('hidden');
        document.getElementById('modal-input').value = '';
    }

    function showAlert(message, isSuccess) {
        const container = document.getElementById('alert-container');
        const msgDiv = document.getElementById('alert-message');
        
        container.classList.remove('hidden');
        msgDiv.textContent = message;
        
        if (isSuccess) {
            msgDiv.className = 'rounded-md p-4 bg-green-50 text-green-800 border border-green-200';
        } else {
            msgDiv.className = 'rounded-md p-4 bg-red-50 text-red-800 border border-red-200';
        }

        setTimeout(() => {
            container.classList.add('hidden');
        }, 5000);
    }

    async function submitAction(e) {
        e.preventDefault();
        
        const bookingId = document.getElementById('modal-booking-id').value;
        const actionType = document.getElementById('modal-action-type').value;
        const inputValue = document.getElementById('modal-input').value;
        
        const endpoint = actionType === 'accept' ? '/org-admin/bookings/accept' : '/org-admin/bookings/decline';
        const payloadKey = actionType === 'accept' ? 'notes' : 'reason';

        try {
            const params = new URLSearchParams();
            params.append('booking_id', bookingId);
            params.append(payloadKey, inputValue);
            params.append('csrf_token', csrfToken);

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString()
            });

            const result = await response.json();

            if (result.success) {
                showAlert(result.success, true);
                
                // Update UI dynamically
                const badge = document.getElementById(`status-badge-${bookingId}`);
                if (badge) {
                     if (actionType === 'accept') {
                         badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                         badge.textContent = 'Accepted';
                     } else {
                         badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800';
                         badge.textContent = 'Declined';
                     }
                }

                // Remove buttons
                const row = document.getElementById(`booking-row-${bookingId}`);
                if (row) {
                     const actionsCell = row.lastElementChild;
                     const viewBtn = actionsCell.firstElementChild;
                     actionsCell.innerHTML = '';
                     actionsCell.appendChild(viewBtn);
                }

                closeActionModal();
            } else {
                showAlert(result.error || 'An error occurred', false);
                closeActionModal();
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('A network error occurred', false);
            closeActionModal();
        }
    }
</script>
<?php
$content = ob_get_clean();
include app_path('views/layout/app.php');
