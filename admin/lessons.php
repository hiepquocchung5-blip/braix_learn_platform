<?php
// admin/lessons.php
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
        $language_id = (int)sanitize_input($_POST['language_id']);
        $title = sanitize_input($_POST['title']);
        $content_type = sanitize_input($_POST['content_type']);
        $content = sanitize_input($_POST['content']);
        $is_premium = isset($_POST['is_premium']) ? 1 : 0;
        $points_cost = (int)sanitize_input($_POST['points_cost']);

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO lessons (language_id, title, content_type, content, is_premium, points_cost) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssii", $language_id, $title, $content_type, $content, $is_premium, $points_cost);
            if ($stmt->execute()) {
                $message = 'Lesson added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding lesson: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $id = (int)sanitize_input($_POST['id']);
            $stmt = $conn->prepare("UPDATE lessons SET language_id = ?, title = ?, content_type = ?, content = ?, is_premium = ?, points_cost = ? WHERE id = ?");
            $stmt->bind_param("isssiii", $language_id, $title, $content_type, $content, $is_premium, $points_cost, $id);
            if ($stmt->execute()) {
                $message = 'Lesson updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating lesson: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM lessons WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Lesson deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting lesson: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Fetch all languages for dropdown
$languages = [];
$result = $conn->query("SELECT id, name FROM programming_languages ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $languages[] = $row;
    }
    $result->free();
}

// Fetch all lessons with language names
$lessons = [];
$stmt = $conn->prepare("SELECT l.*, pl.name as language_name FROM lessons l JOIN programming_languages pl ON l.language_id = pl.id ORDER BY pl.name, l.title ASC");
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lessons[] = $row;
    }
    $result->free();
}
$stmt->close();


include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Manage Lessons</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Add New Lesson</h2>
        <form action="lessons.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label for="language_id" class="block text-purple-200 text-lg font-medium mb-2">Programming Language</label>
                <select id="language_id" name="language_id" required
                        class="w-full px-4 py-2 rounded-lg">
                    <option value="">Select a language</option>
                    <?php foreach ($languages as $lang): ?>
                        <option value="<?php echo $lang['id']; ?>"><?php echo htmlspecialchars($lang['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="title" class="block text-purple-200 text-lg font-medium mb-2">Lesson Title</label>
                <input type="text" id="title" name="title" placeholder="e.g., Variables in Python" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <div>
                <label for="content_type" class="block text-purple-200 text-lg font-medium mb-2">Content Type</label>
                <select id="content_type" name="content_type" required
                        class="w-full px-4 py-2 rounded-lg" onchange="toggleContentField()">
                    <option value="text">Text Content</option>
                    <option value="drive_link">Google Drive Link</option>
                </select>
            </div>
            <div>
                <label for="content" class="block text-purple-200 text-lg font-medium mb-2">Content / Drive Link</label>
                <textarea id="content" name="content" rows="5" placeholder="Enter lesson text or Google Drive URL..."
                          class="w-full px-4 py-2 rounded-lg"></textarea>
            </div>
            <div class="flex items-center space-x-2">
                <input type="checkbox" id="is_premium" name="is_premium" value="1"
                       class="form-checkbox h-5 w-5 text-purple-600 rounded">
                <label for="is_premium" class="text-purple-200 text-lg font-medium">Premium Lesson</label>
            </div>
            <div>
                <label for="points_cost" class="block text-purple-200 text-lg font-medium mb-2">Points Cost (if not premium)</label>
                <input type="number" id="points_cost" name="points_cost" value="0" min="0"
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <button type="submit" class="admin-button-primary w-full">Add Lesson</button>
        </form>
    </div>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Existing Lessons</h2>
        <?php if (!empty($lessons)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Language</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Premium</th>
                            <th>Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lessons as $lesson): ?>
                            <tr>
                                <td><?php echo $lesson['id']; ?></td>
                                <td><?php echo htmlspecialchars($lesson['language_name']); ?></td>
                                <td><?php echo htmlspecialchars($lesson['title']); ?></td>
                                <td><?php echo htmlspecialchars($lesson['content_type']); ?></td>
                                <td><?php echo $lesson['is_premium'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $lesson['points_cost']; ?></td>
                                <td class="flex space-x-2">
                                    <button onclick="editLesson(<?php echo htmlspecialchars(json_encode($lesson)); ?>)"
                                            class="admin-button-secondary">Edit</button>
                                    <form action="lessons.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this lesson?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $lesson['id']; ?>">
                                        <button type="submit" class="admin-button-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No lessons added yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Lesson Modal (Hidden by default) -->
<div id="editLessonModal" class="message-overlay hidden">
    <div class="message-box">
        <div class="message-box-header">Edit Lesson</div>
        <div class="message-box-content">
            <form id="editLessonForm" action="lessons.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div>
                    <label for="edit_language_id" class="block text-purple-200 text-lg font-medium mb-2">Programming Language</label>
                    <select id="edit_language_id" name="language_id" required
                            class="w-full px-4 py-2 rounded-lg">
                        <?php foreach ($languages as $lang): ?>
                            <option value="<?php echo $lang['id']; ?>"><?php echo htmlspecialchars($lang['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="edit_title" class="block text-purple-200 text-lg font-medium mb-2">Lesson Title</label>
                    <input type="text" id="edit_title" name="title" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="edit_content_type" class="block text-purple-200 text-lg font-medium mb-2">Content Type</label>
                    <select id="edit_content_type" name="content_type" required
                            class="w-full px-4 py-2 rounded-lg" onchange="toggleEditContentField()">
                        <option value="text">Text Content</option>
                        <option value="drive_link">Google Drive Link</option>
                    </select>
                </div>
                <div>
                    <label for="edit_content" class="block text-purple-200 text-lg font-medium mb-2">Content / Drive Link</label>
                    <textarea id="edit_content" name="content" rows="5"
                              class="w-full px-4 py-2 rounded-lg"></textarea>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="edit_is_premium" name="is_premium" value="1"
                           class="form-checkbox h-5 w-5 text-purple-600 rounded">
                    <label for="edit_is_premium" class="text-purple-200 text-lg font-medium">Premium Lesson</label>
                </div>
                <div>
                    <label for="edit_points_cost" class="block text-purple-200 text-lg font-medium mb-2">Points Cost (if not premium)</label>
                    <input type="number" id="edit_points_cost" name="points_cost" value="0" min="0"
                           class="w-full px-4 py-2 rounded-lg">
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
    function toggleContentField() {
        const contentType = document.getElementById('content_type').value;
        const contentField = document.getElementById('content');
        if (contentType === 'text') {
            contentField.placeholder = 'Enter lesson text...';
        } else {
            contentField.placeholder = 'Enter Google Drive URL...';
        }
    }

    function toggleEditContentField() {
        const contentType = document.getElementById('edit_content_type').value;
        const contentField = document.getElementById('edit_content');
        if (contentType === 'text') {
            contentField.placeholder = 'Enter lesson text...';
        } else {
            contentField.placeholder = 'Enter Google Drive URL...';
        }
    }

    function editLesson(lesson) {
        document.getElementById('edit_id').value = lesson.id;
        document.getElementById('edit_language_id').value = lesson.language_id;
        document.getElementById('edit_title').value = lesson.title;
        document.getElementById('edit_content_type').value = lesson.content_type;
        document.getElementById('edit_content').value = lesson.content;
        document.getElementById('edit_is_premium').checked = lesson.is_premium == 1;
        document.getElementById('edit_points_cost').value = lesson.points_cost;
        toggleEditContentField(); // Adjust placeholder based on content type
        document.getElementById('editLessonModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editLessonModal').classList.add('hidden');
    }

    // Initial call to set placeholder correctly on page load for add form
    document.addEventListener('DOMContentLoaded', toggleContentField);
</script>

<?php include '../includes/footer.php'; ?>
