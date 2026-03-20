<?php
// admin/users.php
require_once '../includes/config.php';

// Redirect if not logged in or not admin
if (!is_logged_in() || !is_admin($conn)) {
    redirect('../index.php');
}

$message = '';
$message_type = '';

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action']);

    if ($action === 'edit_user') {
        $id = (int)sanitize_input($_POST['id']);
        $username = sanitize_input($_POST['username']);
        $points = (int)sanitize_input($_POST['points']);
        $is_premium = isset($_POST['is_premium']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE users SET username = ?, points = ?, is_premium = ? WHERE id = ?");
        $stmt->bind_param("siii", $username, $points, $is_premium, $id);
        if ($stmt->execute()) {
            $message = 'User updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating user: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    } elseif ($action === 'delete_user') {
        $id = (int)sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'User deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting user: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Fetch all users
$users = [];
$result = $conn->query("SELECT * FROM users ORDER BY username ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}

include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Manage Users</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">All Users</h2>
        <?php if (!empty($users)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Telegram ID</th>
                            <th>Username</th>
                            <th>Points</th>
                            <th>Premium</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['telegram_id']); ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo $u['points']; ?></td>
                                <td><?php echo $u['is_premium'] ? 'Yes' : 'No'; ?></td>
                                <td class="flex space-x-2">
                                    <button onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)"
                                            class="admin-button-secondary">Edit</button>
                                    <form action="users.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="admin-button-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No users registered yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit User Modal (Hidden by default) -->
<div id="editUserModal" class="message-overlay hidden">
    <div class="message-box">
        <div class="message-box-header">Edit User</div>
        <div class="message-box-content">
            <form id="editUserForm" action="users.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" id="edit_user_id" name="id">
                <div>
                    <label for="edit_username" class="block text-purple-200 text-lg font-medium mb-2">Username</label>
                    <input type="text" id="edit_username" name="username" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="edit_points" class="block text-purple-200 text-lg font-medium mb-2">Points</label>
                    <input type="number" id="edit_points" name="points" min="0" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="edit_is_premium" name="is_premium" value="1"
                           class="form-checkbox h-5 w-5 text-purple-600 rounded">
                    <label for="edit_is_premium" class="text-purple-200 text-lg font-medium">Premium User</label>
                </div>
                <div class="message-box-footer flex justify-end space-x-4">
                    <button type="button" onclick="closeEditModal()" class="admin-button-secondary">Cancel</button>
                    <button type="submit" class="admin-button-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editUser(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_points').value = user.points;
        document.getElementById('edit_is_premium').checked = user.is_premium == 1;
        document.getElementById('editUserModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editUserModal').classList.add('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
