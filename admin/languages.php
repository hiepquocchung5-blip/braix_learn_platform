<?php
// admin/languages.php
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

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO programming_languages (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            if ($stmt->execute()) {
                $message = 'Language added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding language: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $id = (int)sanitize_input($_POST['id']);
            $stmt = $conn->prepare("UPDATE programming_languages SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $id);
            if ($stmt->execute()) {
                $message = 'Language updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating language: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM programming_languages WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Language deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting language: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Fetch all languages
$languages = [];
$result = $conn->query("SELECT * FROM programming_languages ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $languages[] = $row;
    }
    $result->free();
}

include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Manage Programming Languages</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Add New Language</h2>
        <form action="languages.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label for="name" class="block text-purple-200 text-lg font-medium mb-2">Language Name</label>
                <input type="text" id="name" name="name" placeholder="e.g., Python" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <div>
                <label for="description" class="block text-purple-200 text-lg font-medium mb-2">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="A brief description of the language..."
                          class="w-full px-4 py-2 rounded-lg"></textarea>
            </div>
            <button type="submit" class="admin-button-primary w-full">Add Language</button>
        </form>
    </div>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Existing Languages</h2>
        <?php if (!empty($languages)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($languages as $lang): ?>
                            <tr>
                                <td><?php echo $lang['id']; ?></td>
                                <td><?php echo htmlspecialchars($lang['name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($lang['description'], 0, 100)); ?>...</td>
                                <td class="flex space-x-2">
                                    <button onclick="editLanguage(<?php echo htmlspecialchars(json_encode($lang)); ?>)"
                                            class="admin-button-secondary">Edit</button>
                                    <form action="languages.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this language? This will also delete all associated lessons!');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $lang['id']; ?>">
                                        <button type="submit" class="admin-button-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No languages added yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Language Modal (Hidden by default) -->
<div id="editLanguageModal" class="message-overlay hidden">
    <div class="message-box">
        <div class="message-box-header">Edit Language</div>
        <div class="message-box-content">
            <form id="editLanguageForm" action="languages.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div>
                    <label for="edit_name" class="block text-purple-200 text-lg font-medium mb-2">Language Name</label>
                    <input type="text" id="edit_name" name="name" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="edit_description" class="block text-purple-200 text-lg font-medium mb-2">Description</label>
                    <textarea id="edit_description" name="description" rows="3"
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
    function editLanguage(language) {
        document.getElementById('edit_id').value = language.id;
        document.getElementById('edit_name').value = language.name;
        document.getElementById('edit_description').value = language.description;
        document.getElementById('editLanguageModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editLanguageModal').classList.add('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
