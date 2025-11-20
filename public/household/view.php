<?php
require_once '../../includes/app.php';
requireStaff(); // Only Staff and Admin can access

$household_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($household_id <= 0) {
    header("Location: /household");
    exit;
}

// Data will be loaded via API

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View - Household - MIS Barangay</title>
    <?php loadAllAssets(); ?>

    <style>
        .chip {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            background: rgba(0, 0, 0, 0.05);
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include_once '../navbar.php'; ?>
    <div class="flex bg-gray-100">
        <?php include_once '../sidebar.php'; ?>

        <main class="p-6 w-screen">
            <h1 class="text-2xl font-semibold mb-6">View Household</h1>

            <!-- ✅ Start of Household Information Section -->
            <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Form Section -->
                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h2 class="text-lg font-medium mb-4">Edit Household</h2>
                    <form id="householdForm" autocomplete="off">
                        <div class="grid grid-cols-1 sm:grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Household Number</label>
                                <input name="household_no" id="household_no" type="text"
                                    class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Head of Household Name</label>
                                <input name="head_name" id="head_name" type="text"
                                    class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Address</label>
                                <textarea name="address" id="address" rows="3"
                                    class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" required></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Total Members</label>
                                <input name="total_members" id="total_members" type="number" readonly
                                    class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm bg-gray-100" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <button id="saveBtn" type="button"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow-sm">Save</button>
                            <a href="/household" class="px-4 py-2 border rounded-lg">Back to List</a>
                        </div>
                    </form>
                </section>

                <!-- Preview Section -->
                <aside class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="flex items-start justify-between">
                        <h2 class="text-lg font-medium">Household Details</h2>
                    </div>

                    <div id="previewCard" class="mt-4 border rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 id="previewHouseholdNo" class="text-xl font-semibold text-gray-800">—</h3>
                                <div id="previewHead" class="text-sm text-gray-600 mt-1">—</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Members</div>
                                <div id="previewMembers" class="font-medium">—</div>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-1 gap-2 text-sm text-gray-700">
                            <div><span class="font-medium">Address:</span>
                                <div id="previewAddress" class="mt-1 text-sm text-gray-600">—</div>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-4">
                        <button id="refreshBtn" class="px-3 py-1 border rounded-lg text-sm">Refresh</button>
                    </div>
                </aside>
            </div>

            <!-- Household Members Section -->
            <div class="max-w-6xl mx-auto mt-6">
                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h2 class="text-lg font-medium mb-4">Household Members (<span id="memberCount">0</span>)</h2>
                    <div id="membersContainer" class="text-center text-muted py-4">Loading members...</div>
                </section>
            </div>
            <!-- ✅ End of Household Information Section -->
        </main>
    </div>

    <script>
        // Helper function to show Bootstrap modal
        function showBootstrapModal(title, message, onClose) {
            const modalId = 'dynamicModal_' + Date.now();
            const safeTitle = $('<div>').text(title).html();
            const safeMessage = $('<div>').html(message).html();
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">${safeTitle}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-0">${safeMessage}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Ok</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(modalHtml);
            const modalElement = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            $(modalElement).on('hidden.bs.modal', function() {
                if (onClose) onClose();
                $(this).remove();
            });
        }
        
        $(function() {
            function updatePreview() {
                const data = {};
                $('#householdForm').serializeArray().forEach(f => data[f.name] = f.value);

                $('#previewHouseholdNo').text(data.household_no || '—');
                $('#previewHead').text(data.head_name || '—');
                $('#previewMembers').text(data.total_members || '0');
                $('#previewAddress').text(data.address || '—');
            }

            $('#householdForm').on('input change', 'input,textarea,select', updatePreview);
            updatePreview();

            $('#saveBtn').click(() => {
                const formData = {};
                $('#householdForm').serializeArray().forEach(f => formData[f.name] = f.value);
                
                const updateData = {
                    action: 'update',
                    id: householdId,
                    household_no: formData.household_no,
                    head_name: formData.head_name,
                    address: formData.address
                };

                $.ajax({
                    url: '/api/households',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(updateData),
                    dataType: 'json',
                    success: function(response) {
                        const message = response.message || (response.status === 'success' ? 'Household updated successfully' : 'Failed to update household');
                        const title = response.status === 'success' ? 'Saved' : 'Error';
                        
                        showBootstrapModal(title, message, function() {
                            if (response.status === 'success') {
                                loadHouseholdData();
                                loadMembers();
                            }
                        });
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.message || 'Failed to connect to server.';
                        showBootstrapModal('Error', errorMsg);
                    }
                });
            });
            
            function loadHouseholdData() {
                if (!householdId) return;
                
                $.ajax({
                    url: `/api/households?id=${householdId}`,
                    method: 'GET',
                    success: function(response) {
                        if (response.status === 'success' && response.data) {
                            const res = response.data;
                            // Fill all form fields
                            for (const key in res) {
                                if ($(`[name="${key}"]`).length) {
                                    $(`[name="${key}"]`).val(res[key]);
                                }
                            }
                            // Trigger preview update
                            updatePreview();
                        } else {
                            alert(response.message || 'Household not found');
                            window.location.href = '/household';
                        }
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.message || 'Failed to load household data.';
                        alert(errorMsg);
                        window.location.href = '/household';
                    }
                });
            }
            
            function loadMembers() {
                if (!householdId) return;
                
                $.ajax({
                    url: `/api/households?id=${householdId}&action=members`,
                    method: 'GET',
                    success: function(response) {
                        if (response.status === 'success' && response.data) {
                            const members = response.data;
                            $('#memberCount').text(members.length);
                            
                            if (members.length > 0) {
                                function calculateAge(birthdate) {
                                    if (!birthdate) return '';
                                    const parts = birthdate.split('-');
                                    if (parts.length !== 3) return '';
                                    const birth = new Date(parts[0], parts[1] - 1, parts[2]);
                                    const today = new Date();
                                    let age = today.getFullYear() - birth.getFullYear();
                                    const monthDiff = today.getMonth() - birth.getMonth();
                                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                                        age--;
                                    }
                                    return age;
                                }
                                
                                const html = '<div class="overflow-x-auto"><table class="w-full text-sm border border-gray-200 rounded-lg">' +
                                    '<thead class="bg-gray-50 text-gray-700"><tr>' +
                                    '<th class="p-2 text-left">Name</th>' +
                                    '<th class="p-2 text-left">Gender</th>' +
                                    '<th class="p-2 text-left">Age</th>' +
                                    '<th class="p-2 text-left">Civil Status</th>' +
                                    '<th class="p-2 text-left">Occupation</th>' +
                                    '<th class="p-2 text-left">Actions</th>' +
                                    '</tr></thead><tbody>' +
                                    members.map(member => {
                                        const fullName = [member.first_name, member.middle_name, member.last_name, member.suffix].filter(Boolean).join(' ');
                                        return '<tr>' +
                                            '<td class="p-2"><a href="/resident/view?id=' + member.id + '" class="text-blue-600 hover:underline">' + 
                                            escapeHtml(fullName) + '</a></td>' +
                                            '<td class="p-2">' + escapeHtml(member.gender || '') + '</td>' +
                                            '<td class="p-2">' + calculateAge(member.birthdate) + '</td>' +
                                            '<td class="p-2">' + escapeHtml(member.civil_status || '') + '</td>' +
                                            '<td class="p-2">' + escapeHtml(member.occupation || '') + '</td>' +
                                            '<td class="p-2"><a href="/resident/view?id=' + member.id + '" class="text-blue-600 hover:underline">View</a></td>' +
                                            '</tr>';
                                    }).join('') +
                                    '</tbody></table></div>';
                                $('#membersContainer').html(html);
                            } else {
                                $('#membersContainer').html('<p class="text-gray-500 text-center py-4">No members assigned to this household yet.</p>');
                            }
                        } else {
                            $('#membersContainer').html('<p class="text-gray-500 text-center py-4">Failed to load members.</p>');
                        }
                    },
                    error: function() {
                        $('#membersContainer').html('<p class="text-gray-500 text-center py-4">Error loading members.</p>');
                    }
                });
            }
            
            function escapeHtml(text) {
                if (!text) return '';
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
            }

            $('#refreshBtn').click(() => {
                updatePreview();
            });
        });
        // Load household data and members on page load
        const householdId = new URLSearchParams(window.location.search).get('id');
        if (householdId) {
            loadHouseholdData();
            loadMembers();
        }
    </script>
</body>

</html>

