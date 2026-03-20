<?php
// admin/profile_items.php
require_once '../includes/config.php';

// Redirect if not logged in or not admin
if (!is_logged_in() || !is_admin($conn)) {
    redirect('../index.php');
}

$message = '';
$message_type = '';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action']);

    if ($action === 'add' || $action === 'edit') {
        $name = sanitize_input($_POST['name']);
        $type = sanitize_input($_POST['type']);
        $value = sanitize_input($_POST['value']);
        $points_cost = (int)sanitize_input($_POST['points_cost']);
        $is_premium_exclusive = isset($_POST['is_premium_exclusive']) ? 1 : 0;

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO profile_items (name, type, value, points_cost, is_premium_exclusive) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisi", $name, $type, $value, $points_cost, $is_premium_exclusive);
            if ($stmt->execute()) {
                $message = 'Profile item added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding profile item: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $id = (int)sanitize_input($_POST['id']);
            $stmt = $conn->prepare("UPDATE profile_items SET name = ?, type = ?, value = ?, points_cost = ?, is_premium_exclusive = ? WHERE id = ?");
            $stmt->bind_param("ssisii", $name, $type, $value, $points_cost, $is_premium_exclusive, $id);
            if ($stmt->execute()) {
                $message = 'Profile item updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating profile item: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM profile_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Profile item deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting profile item: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Fetch all profile items
$profile_items = [];
$result = $conn->query("SELECT * FROM profile_items ORDER BY type, name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $profile_items[] = $row;
    }
    $result->free();
}

include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Manage Profile Items</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Add New Profile Item</h2>
        <form action="profile_items.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label for="name" class="block text-purple-200 text-lg font-medium mb-2">Item Name</label>
                <input type="text" id="name" name="name" placeholder="e.g., Space Explorer Avatar" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <div>
                <label for="type" class="block text-purple-200 text-lg font-medium mb-2">Item Type</label>
                <select id="type" name="type" required
                        class="w-full px-4 py-2 rounded-lg">
                    <option value="avatar">Avatar</option>
                    <option value="banner">Banner</option>
                    <option value="theme">Theme</option>
                </select>
            </div>
            <div>
                <label for="value" class="block text-purple-200 text-lg font-medium mb-2">Value (URL for image, CSS class/JSON for theme)</label>
                <textarea id="value" name="value" rows="3" placeholder="e.g., https://placehold.co/100x100/..." required
                          class="w-full px-4 py-2 rounded-lg"></textarea>
            </div>
            <div>
                <label for="points_cost" class="block text-purple-200 text-lg font-medium mb-2">Points Cost</label>
                <input type="number" id="points_cost" name="points_cost" value="0" min="0" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <div class="flex items-center space-x-2">
                <input type="checkbox" id="is_premium_exclusive" name="is_premium_exclusive" value="1"
                       class="form-checkbox h-5 w-5 text-purple-600 rounded">
                <label for="is_premium_exclusive" class="text-purple-200 text-lg font-medium">Premium Exclusive</label>
            </div>
            <button type="submit" class="admin-button-primary w-full">Add Item</button>
        </form>
    </div>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Existing Profile Items</h2>
        <?php if (!empty($profile_items)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Cost</th>
                            <th>Premium</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profile_items as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($item['type'])); ?></td>
                                <td><?php echo htmlspecialchars(substr($item['value'], 0, 50)); ?>...</td>
                                <td><?php echo $item['points_cost']; ?></td>
                                <td><?php echo $item['is_premium_exclusive'] ? 'Yes' : 'No'; ?></td>
                                <td class="flex space-x-2">
                                    <button onclick="editProfileItem(<?php echo htmlspecialchars(json_encode($item)); ?>)"
                                            class="admin-button-secondary">Edit</button>
                                    <form action="profile_items.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this profile item?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="admin-button-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No profile items added yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Profile Item Modal (Hidden by default) -->
<div id="editProfileItemModal" class="message-overlay hidden">
    <div class="message-box">
        <div class="message-box-header">Edit Profile Item</div>
        <div class="message-box-content">
            <form id="editProfileItemForm" action="profile_items.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div>
                    <label for="edit_name" class="block text-purple-200 text-lg font-medium mb-2">Item Name</label>
                    <input type="text" id="edit_name" name="name" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="edit_type" class="block text-purple-200 text-lg font-medium mb-2">Item Type</label>
                    <select id="edit_type" name="type" required
                            class="w-full px-4 py-2 rounded-lg">
                        <option value="avatar">Avatar</option>
                        <option value="banner">Banner</option>
                        <option value="theme">Theme</option>
                    </select>
                </div>
                <div>
                    <label for="edit_value" class="block text-purple-200 text-lg font-medium mb-2">Value</label>
                    <textarea id="edit_value" name="value" rows="3" required
                              class="w-full px-4 py-2 rounded-lg"></textarea>
                </div>
                <div>
                    <label for="edit_points_cost" class="block text-purple-200 text-lg font-medium mb-2">Points Cost</label>
                    <input type="number" id="edit_points_cost" name="points_cost" min="0" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="edit_is_premium_exclusive" name="is_premium_exclusive" value="1"
                           class="form-checkbox h-5 w-5 text-purple-600 rounded">
                    <label for="edit_is_premium_exclusive" class="text-purple-200 text-lg font-medium">Premium Exclusive</label>
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
    function editProfileItem(item) {
        document.getElementById('edit_id').value = item.id;
        document.getElementById('edit_name').value = item.name;
        document.getElementById('edit_type').value = item.type;
        document.getElementById('edit_value').value = item.value;
        document.getElementById('edit_points_cost').value = item.points_cost;
        document.getElementById('edit_is_premium_exclusive').checked = item.is_premium_exclusive == 1;
        document.getElementById('editProfileItemModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editProfileItemModal').classList.add('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
