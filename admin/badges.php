<?php
// admin/badges.php
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
        $description = sanitize_input($_POST['description']);
        $image_url = sanitize_input($_POST['image_url']);
        $criteria = sanitize_input($_POST['criteria']);

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO badges (name, description, image_url, criteria) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $description, $image_url, $criteria);
            if ($stmt->execute()) {
                $message = 'Badge added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding badge: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $id = (int)sanitize_input($_POST['id']);
            $stmt = $conn->prepare("UPDATE badges SET name = ?, description = ?, image_url = ?, criteria = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $description, $image_url, $criteria, $id);
            if ($stmt->execute()) {
                $message = 'Badge updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating badge: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM badges WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Badge deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting badge: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Fetch all badges
$badges = [];
$result = $conn->query("SELECT * FROM badges ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $badges[] = $row;
    }
    $result->free();
}

include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Manage Badges</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Add New Badge</h2>
        <form action="badges.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label for="name" class="block text-purple-200 text-lg font-medium mb-2">Badge Name</label>
                <input type="text" id="name" name="name" placeholder="e.g., Python Novice" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <div>
                <label for="description" class="block text-purple-200 text-lg font-medium mb-2">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="A short description of the badge..."
                          class="w-full px-4 py-2 rounded-lg"></textarea>
            </div>
            <div>
                <label for="image_url" class="block text-purple-200 text-lg font-medium mb-2">Image URL</label>
                <input type="url" id="image_url" name="image_url" placeholder="https://placehold.co/50x50/..." required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <div>
                <label for="criteria" class="block text-purple-200 text-lg font-medium mb-2">Criteria (How to earn)</label>
                <textarea id="criteria" name="criteria" rows="3" placeholder="e.g., Complete all Python lessons"
                          class="w-full px-4 py-2 rounded-lg"></textarea>
            </div>
            <button type="submit" class="admin-button-primary w-full">Add Badge</button>
        </form>
    </div>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Existing Badges</h2>
        <?php if (!empty($badges)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Criteria</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($badges as $badge): ?>
                            <tr>
                                <td><?php echo $badge['id']; ?></td>
                                <td><img src="<?php echo htmlspecialchars($badge['image_url'] ?: 'https://placehold.co/50x50/6a0dad/ffffff?text=Badge'); ?>" alt="Badge" class="w-10 h-10 object-cover rounded-full"></td>
                                <td><?php echo htmlspecialchars($badge['name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($badge['description'], 0, 100)); ?>...</td>
                                <td><?php echo htmlspecialchars(substr($badge['criteria'], 0, 100)); ?>...</td>
                                <td class="flex space-x-2">
                                    <button onclick="editBadge(<?php echo htmlspecialchars(json_encode($badge)); ?>)"
                                            class="admin-button-secondary">Edit</button>
                                    <form action="badges.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this badge? This will also remove it from all users who earned it!');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $badge['id']; ?>">
                                        <button type="submit" class="admin-button-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No badges added yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Badge Modal (Hidden by default) -->
<div id="editBadgeModal" class="message-overlay hidden">
    <div class="message-box">
        <div class="message-box-header">Edit Badge</div>
        <div class="message-box-content">
            <form id="editBadgeForm" action="badges.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div>
                    <label for="edit_name" class="block text-purple-200 text-lg font-medium mb-2">Badge Name</label>
                    <input type="text" id="edit_name" name="name" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="edit_description" class="block text-purple-200 text-lg font-medium mb-2">Description</label>
                    <textarea id="edit_description" name="description" rows="3"
                              class="w-full px-4 py-2 rounded-lg"></textarea>
                </div>
                <div>
                    <label for="edit_image_url" class="block text-purple-200 text-lg font-medium mb-2">Image URL</label>
                    <input type="url" id="edit_image_url" name="image_url" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="edit_criteria" class="block text-purple-200 text-lg font-medium mb-2">Criteria (How to earn)</label>
                    <textarea id="edit_criteria" name="criteria" rows="3"
                              class="w-full px-4 py-2 rounded-lg"></textarea>
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
    function editBadge(badge) {
        document.getElementById('edit_id').value = badge.id;
        document.getElementById('edit_name').value = badge.name;
        document.getElementById('edit_description').value = badge.description;
        document.getElementById('edit_image_url').value = badge.image_url;
        document.getElementById('edit_criteria').value = badge.criteria;
        document.getElementById('editBadgeModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editBadgeModal').classList.add('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
