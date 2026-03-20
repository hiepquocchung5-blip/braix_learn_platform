<?php
// admin/ai_prompts.php
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
        $ai_name = sanitize_input($_POST['ai_name']);
        $language_id = !empty($_POST['language_id']) ? (int)sanitize_input($_POST['language_id']) : NULL;
        $prompt_text = sanitize_input($_POST['prompt_text']);

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO ai_prompts (ai_name, language_id, prompt_text) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $ai_name, $language_id, $prompt_text);
            if ($stmt->execute()) {
                $message = 'AI Prompt added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding AI Prompt: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $id = (int)sanitize_input($_POST['id']);
            $stmt = $conn->prepare("UPDATE ai_prompts SET ai_name = ?, language_id = ?, prompt_text = ? WHERE id = ?");
            $stmt->bind_param("sisi", $ai_name, $language_id, $prompt_text, $id);
            if ($stmt->execute()) {
                $message = 'AI Prompt updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating AI Prompt: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM ai_prompts WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'AI Prompt deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting AI Prompt: ' . $conn->error;
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

// Fetch all AI prompts with language names
$ai_prompts = [];
$stmt = $conn->prepare("SELECT ap.*, pl.name as language_name FROM ai_prompts ap LEFT JOIN programming_languages pl ON ap.language_id = pl.id ORDER BY ap.ai_name, pl.name ASC");
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ai_prompts[] = $row;
    }
    $result->free();
}
$stmt->close();

include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Manage AI Prompts</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Add New AI Prompt</h2>
        <form action="ai_prompts.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label for="ai_name" class="block text-purple-200 text-lg font-medium mb-2">AI Name</label>
                <input type="text" id="ai_name" name="ai_name" placeholder="e.g., Gemini Code Helper" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <div>
                <label for="language_id" class="block text-purple-200 text-lg font-medium mb-2">Associated Language (Optional)</label>
                <select id="language_id" name="language_id"
                        class="w-full px-4 py-2 rounded-lg">
                    <option value="">None</option>
                    <?php foreach ($languages as $lang): ?>
                        <option value="<?php echo $lang['id']; ?>"><?php echo htmlspecialchars($lang['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="prompt_text" class="block text-purple-200 text-lg font-medium mb-2">Prompt Text</label>
                <textarea id="prompt_text" name="prompt_text" rows="5" placeholder="Enter the prompt for the AI..." required
                          class="w-full px-4 py-2 rounded-lg"></textarea>
            </div>
            <button type="submit" class="admin-button-primary w-full">Add Prompt</button>
        </form>
    </div>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Existing AI Prompts</h2>
        <?php if (!empty($ai_prompts)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>AI Name</th>
                            <th>Language</th>
                            <th>Prompt Text</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ai_prompts as $prompt): ?>
                            <tr>
                                <td><?php echo $prompt['id']; ?></td>
                                <td><?php echo htmlspecialchars($prompt['ai_name']); ?></td>
                                <td><?php echo htmlspecialchars($prompt['language_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(substr($prompt['prompt_text'], 0, 100)); ?>...</td>
                                <td class="flex space-x-2">
                                    <button onclick="editPrompt(<?php echo htmlspecialchars(json_encode($prompt)); ?>)"
                                            class="admin-button-secondary">Edit</button>
                                    <form action="ai_prompts.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this prompt?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $prompt['id']; ?>">
                                        <button type="submit" class="admin-button-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No AI prompts added yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit AI Prompt Modal (Hidden by default) -->
<div id="editPromptModal" class="message-overlay hidden">
    <div class="message-box">
        <div class="message-box-header">Edit AI Prompt</div>
        <div class="message-box-content">
            <form id="editPromptForm" action="ai_prompts.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div>
                    <label for="edit_ai_name" class="block text-purple-200 text-lg font-medium mb-2">AI Name</label>
                    <input type="text" id="edit_ai_name" name="ai_name" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="edit_language_id" class="block text-purple-200 text-lg font-medium mb-2">Associated Language (Optional)</label>
                    <select id="edit_language_id" name="language_id"
                            class="w-full px-4 py-2 rounded-lg">
                        <option value="">None</option>
                        <?php foreach ($languages as $lang): ?>
                            <option value="<?php echo $lang['id']; ?>"><?php echo htmlspecialchars($lang['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="edit_prompt_text" class="block text-purple-200 text-lg font-medium mb-2">Prompt Text</label>
                    <textarea id="edit_prompt_text" name="prompt_text" rows="5" required
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
    function editPrompt(prompt) {
        document.getElementById('edit_id').value = prompt.id;
        document.getElementById('edit_ai_name').value = prompt.ai_name;
        document.getElementById('edit_language_id').value = prompt.language_id || ''; // Set to empty string if null
        document.getElementById('edit_prompt_text').value = prompt.prompt_text;
        document.getElementById('editPromptModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editPromptModal').classList.add('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
