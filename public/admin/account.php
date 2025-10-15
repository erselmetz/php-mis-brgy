<?php
include_once __DIR__.'../../../includes/app.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    include_once 'add_account.php';
    include_once 'edit_account.php';
}
requireAdmin();
$result = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Accounts Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <?php
    echo adddatatablecss();
    echo addjqueryjs();
    echo adddatatablejs();
    echo addjqueruicss();
    echo addjqueryuijs();
    ?>

</head>

<body>
    <?php include '../navbar.php'; ?>
    <div class="flex bg-gray-100">
        <?php include '../sidebar.php'; ?>
        <main class="p-20 w-screen">
            <h2 class="text-2xl font-semibold mb-4">Manage All Account</h2>

            <!-- show success message -->
            <?php if (isset($success) && $success != "") echo DialogMessage($success) ?>

            <!-- show error message -->
            <?php if (isset($error) && $error != "") echo DialogMessage($error) ?>

            <!-- ✅ Add Button -->
            <div class="p-6">
                <button id="openModalBtn"
                    class="bg-blue-700 hover:bg-blue-800 text-white font-semibold px-4 py-2 rounded shadow">
                    ➕ Add New Account
                </button>
            </div>
            <div class="content">
                <table id="accountsTable" class="display">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id']; ?></td>
                                <td><?= htmlspecialchars($row['name']); ?></td>
                                <td><?= htmlspecialchars($row['username']); ?></td>
                                <td><?= ucfirst($row['role']); ?></td>
                                <td><?= ucfirst($row['status']); ?></td>
                                <td><?= (new DateTime($row['created_at']))->format('Y-m-d') ?></td>
                                <td>
                                    <!-- Example Edit Button -->
                                    <button
                                        class="edit-btn bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 transition"
                                        data-id="<?= $row['id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                        data-username="<?= htmlspecialchars($row['username']) ?>"
                                        data-role="<?= htmlspecialchars($row['role']) ?>"
                                        data-status="<?= htmlspecialchars($row['status']) ?>">
                                        ✏️ Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Edit Account Dialog -->
    <div id="editAccountDialog" title="Edit Account" class="hidden">
        <form id="editAccountForm" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="edit_account">
            <input type="hidden" name="id" id="editAccountId">

            <div>
                <label class="block text-gray-700 font-medium">Full Name</label>
                <input type="text" name="fullname" id="editFullname" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Username</label>
                <input type="text" name="username" id="editUsername" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Role</label>
                <select name="role" id="editRole" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Status</label>
                <select name="status" id="editStatus" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Password (leave blank to keep current)</label>
                <input type="password" name="password" id="editPassword"
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
        </form>
    </div>


    <!-- add account thru modal -->
    <div id="addAccountModal" title="Add New Account" class="hidden">
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add_account">
            <?php if (isset($error)) echo "<p class='text-red-600 font-medium'>$error</p>"; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="fullname" placeholder="Full Name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" placeholder="Username" required
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" placeholder="Password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Role</label>
                <select name="role" required
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full bg-blue-700 hover:bg-blue-800 text-white py-2 rounded font-semibold">
                    Add Account
                </button>
            </div>
        </form>
    </div>

    <script>
        $(function() {
            $('#accountsTable').DataTable();


            // Initialize modal (hidden by default)
            $("#addAccountModal").dialog({
                autoOpen: false,
                modal: true,
                width: 400,
                show: {
                    effect: "fadeIn",
                    duration: 200
                },
                hide: {
                    effect: "fadeOut",
                    duration: 200
                }
            });

            // Open modal when button clicked
            $("#openModalBtn").on("click", function() {
                $("#addAccountModal").dialog("open");
            });

            $('.edit-btn').on('click', function() {
                // Get data from button
                const id = $(this).data('id');
                const name = $(this).data('name');
                const username = $(this).data('username');
                const role = $(this).data('role');
                const status = $(this).data('status');

                // Fill form fields
                $('#editAccountId').val(id);
                $('#editFullname').val(name);
                $('#editUsername').val(username);
                $('#editRole').val(role);
                $('#editStatus').val(status);
                $('#editPassword').val('');

                // Open dialog
                $("#editAccountDialog").dialog({
                    modal: true,
                    width: 450,
                    buttons: {
                        "Save Changes": function() {
                            $('#editAccountForm').submit(); // submit form via POST
                            $(this).dialog("close");
                        },
                        "Cancel": function() {
                            $(this).dialog("close");
                        }
                    },
                    open: function() {
                        $(".ui-dialog-buttonpane button:contains('Save Changes')")
                            .addClass("bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700");
                        $(".ui-dialog-buttonpane button:contains('Cancel')")
                            .addClass("bg-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-400");
                    }
                });
            });
        });
    </script>
</body>

</html>