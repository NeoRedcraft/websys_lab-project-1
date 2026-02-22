<?php
$title = 'Manage Users';
ob_start();
?>

<div class="max-w-6xl mx-auto p-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">User Management</h1>
        <button onclick="document.getElementById('preregister-modal').classList.remove('hidden')"
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
            + Pre-register President
        </button>
    </div>

    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Users Table -->
    <?php if (empty($users)): ?>
        <p class="text-gray-600">No users found.</p>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600 uppercase text-xs">
                        <th class="p-4">Name</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">Role</th>
                        <th class="p-4">Organization</th>
                        <th class="p-4">Active</th>
                        <th class="p-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4 font-medium"><?= htmlspecialchars($u['full_name'] ?? '—') ?></td>
                            <td class="p-4 text-gray-600"><?= htmlspecialchars($u['email'] ?? '') ?></td>
                            <td class="p-4">
                                <?php
                                $roleName = $u['roles']['name'] ?? 'unknown';
                                $roleColors = [
                                    'system_admin' => 'bg-purple-100 text-purple-800',
                                    'org_admin'    => 'bg-blue-100 text-blue-800',
                                    'organizer'    => 'bg-green-100 text-green-800',
                                ];
                                $color = $roleColors[$roleName] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?= $color ?>">
                                    <?= htmlspecialchars($roleName) ?>
                                </span>
                            </td>
                            <td class="p-4 text-gray-600"><?= htmlspecialchars($u['organizations']['name'] ?? '—') ?></td>
                            <td class="p-4">
                                <?php if ($u['is_active']): ?>
                                    <span class="text-green-600 font-medium">Active</span>
                                <?php else: ?>
                                    <span class="text-red-500 font-medium">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <button onclick="openRoleModal('<?= htmlspecialchars($u['id']) ?>',
                                                               '<?= htmlspecialchars($u['full_name'] ?? '') ?>',
                                                               '<?= (int)($u['role_id'] ?? 0) ?>',
                                                               '<?= (int)($u['org_id'] ?? 0) ?>')"
                                        class="text-red-600 hover:underline text-sm">
                                    Change Role
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ===================== Pre-register President Modal ===================== -->
<div id="preregister-modal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold">Pre-register Organization President</h2>
            <button onclick="document.getElementById('preregister-modal').classList.add('hidden')"
                    class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
        </div>

        <div id="prereg-result" class="hidden mb-4 p-3 rounded text-sm"></div>

        <form id="prereg-form" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

            <div>
                <label class="block text-sm font-medium mb-1">Full Name</label>
                <input type="text" name="name" required
                       class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"
                       placeholder="Juan dela Cruz">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">myMail Address</label>
                <input type="email" name="email" required
                       class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"
                       placeholder="jdelacruz@mymail.mapua.edu.ph">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Assign to Organization</label>
                <select name="org_id" required
                        class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">— Select Organization —</option>
                    <?php foreach ($organizations ?? [] as $org): ?>
                        <option value="<?= (int)$org['id'] ?>"><?= htmlspecialchars($org['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button"
                        onclick="document.getElementById('preregister-modal').classList.add('hidden')"
                        class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-50">Cancel</button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Pre-register
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== Change Role Modal ===================== -->
<div id="role-modal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold">Change Role</h2>
            <button onclick="document.getElementById('role-modal').classList.add('hidden')"
                    class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
        </div>
        <p class="text-sm text-gray-600 mb-4">Changing role for: <strong id="role-modal-name"></strong></p>

        <form method="POST" action="/admin/users/assign-role" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
            <input type="hidden" name="user_id" id="role-modal-user-id">

            <div>
                <label class="block text-sm font-medium mb-1">Role</label>
                <select name="role_id" id="role-modal-role-id" required
                        class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <?php foreach ($roles ?? [] as $role): ?>
                        <option value="<?= (int)$role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Organization (for org_admin only)</label>
                <select name="org_id" id="role-modal-org-id"
                        class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">— None —</option>
                    <?php foreach ($organizations ?? [] as $org): ?>
                        <option value="<?= (int)$org['id'] ?>"><?= htmlspecialchars($org['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button"
                        onclick="document.getElementById('role-modal').classList.add('hidden')"
                        class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-50">Cancel</button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Pre-register form — AJAX submit so we can show the temp password
document.getElementById('prereg-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const resultBox = document.getElementById('prereg-result');
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.textContent = 'Registering...';

    const data = new FormData(form);
    const response = await fetch('/admin/users/preregister-president', {
        method: 'POST',
        body: data,
    });
    const json = await response.json();

    resultBox.classList.remove('hidden', 'bg-green-100', 'bg-red-100', 'text-green-800', 'text-red-800');
    if (json.success) {
        resultBox.classList.add('bg-green-100', 'text-green-800');
        resultBox.innerHTML = `<strong>Success!</strong> Account created.<br>
            Temporary password: <code class="font-mono font-bold">${json.temp_password}</code><br>
            <em class="text-xs">Share this securely with the president. They must change it on first login.</em>`;
        form.reset();
        // Reload the user list after a moment
        setTimeout(() => location.reload(), 4000);
    } else {
        resultBox.classList.add('bg-red-100', 'text-red-800');
        resultBox.textContent = json.error ?? 'An error occurred.';
    }

    btn.disabled = false;
    btn.textContent = 'Pre-register';
});

// Populate role modal
function openRoleModal(userId, userName, roleId, orgId) {
    document.getElementById('role-modal-user-id').value = userId;
    document.getElementById('role-modal-name').textContent = userName;
    document.getElementById('role-modal-role-id').value = roleId;
    document.getElementById('role-modal-org-id').value = orgId || '';
    document.getElementById('role-modal').classList.remove('hidden');
}
</script>

<?php $content = ob_get_clean(); include app_path('views/layout/app.php'); ?>