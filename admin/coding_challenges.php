<?php
// admin/coding_challenges.php
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
        $description = sanitize_input($_POST['description']);
        $expected_output = sanitize_input($_POST['expected_output']);
        $points_reward = (int)sanitize_input($_POST['points_reward']);

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO coding_challenges (language_id, title, description, expected_output, points_reward) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issii", $language_id, $title, $description, $expected_output, $points_reward);
            if ($stmt->execute()) {
                $message = 'Coding challenge added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding coding challenge: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $id = (int)sanitize_input($_POST['id']);
            $stmt = $conn->prepare("UPDATE coding_challenges SET language_id = ?, title = ?, description = ?, expected_output = ?, points_reward = ? WHERE id = ?");
            $stmt->bind_param("issiii", $language_id, $title, $description, $expected_output, $points_reward, $id);
            if ($stmt->execute()) {
                $message = 'Coding challenge updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating coding challenge: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM coding_challenges WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Coding challenge deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting coding challenge: ' . $conn->error;
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

// Fetch all coding challenges with language names
$challenges = [];
$stmt = $conn->prepare("SELECT cc.*, pl.name as language_name FROM coding_challenges cc JOIN programming_languages pl ON cc.language_id = pl.id ORDER BY cc.title ASC");
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $challenges[] = $row;
    }
    $result->free();
}
$stmt->close();

include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Manage Coding Challenges</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Add New Coding Challenge</h2>
        <form action="coding_challenges.php" method="POST" class="space-y-4">
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
                <label for="title" class="block text-purple-200 text-lg font-medium mb-2">Challenge Title</label>
                <input type="text" id="title" name="title" placeholder="e.g., FizzBuzz Challenge" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <div>
                <label for="description" class="block text-purple-200 text-lg font-medium mb-2">Description</label>
                <textarea id="description" name="description" rows="5" placeholder="Detailed description of the challenge..." required
                          class="w-full px-4 py-2 rounded-lg"></textarea>
            </div>
            <div>
                <label for="expected_output" class="block text-purple-200 text-lg font-medium mb-2">Expected Output (Optional, for reference)</label>
                <textarea id="expected_output" name="expected_output" rows="3" placeholder="Expected output for the challenge, if applicable."
                          class="w-full px-4 py-2 rounded-lg"></textarea>
            </div>
            <div>
                <label for="points_reward" class="block text-purple-200 text-lg font-medium mb-2">Points Reward</label>
                <input type="number" id="points_reward" name="points_reward" value="50" min="0" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <button type="submit" class="admin-button-primary w-full">Add Challenge</button>
        </form>
    </div>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Existing Challenges</h2>
        <?php if (!empty($challenges)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Language</th>
                            <th>Reward</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($challenges as $challenge): ?>
                            <tr>
                                <td><?php echo $challenge['id']; ?></td>
                                <td><?php echo htmlspecialchars($challenge['title']); ?></td>
                                <td><?php echo htmlspecialchars($challenge['language_name']); ?></td>
                                <td><?php echo $challenge['points_reward']; ?></td>
                                <td class="flex space-x-2">
                                    <button onclick="editChallenge(<?php echo htmlspecialchars(json_encode($challenge)); ?>)"
                                            class="admin-button-secondary">Edit</button>
                                    <form action="coding_challenges.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this challenge? This will also delete all associated submissions!');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $challenge['id']; ?>">
                                        <button type="submit" class="admin-button-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No coding challenges added yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Challenge Modal (Hidden by default) -->
<div id="editChallengeModal" class="message-overlay hidden">
    <div class="message-box">
        <div class="message-box-header">Edit Coding Challenge</div>
        <div class="message-box-content">
            <form id="editChallengeForm" action="coding_challenges.php" method="POST" class="space-y-4">
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
                    <label for="edit_title" class="block text-purple-200 text-lg font-medium mb-2">Challenge Title</label>
                    <input type="text" id="edit_title" name="title" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="edit_description" class="block text-purple-200 text-lg font-medium mb-2">Description</label>
                    <textarea id="edit_description" name="description" rows="5" required
                              class="w-full px-4 py-2 rounded-lg"></textarea>
                </div>
                <div>
                    <label for="edit_expected_output" class="block text-purple-200 text-lg font-medium mb-2">Expected Output (Optional)</label>
                    <textarea id="edit_expected_output" name="expected_output" rows="3"
                              class="w-full px-4 py-2 rounded-lg"></textarea>
                </div>
                <div>
                    <label for="edit_points_reward" class="block text-purple-200 text-lg font-medium mb-2">Points Reward</label>
                    <input type="number" id="edit_points_reward" name="points_reward" min="0" required
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
    function editChallenge(challenge) {
        document.getElementById('edit_id').value = challenge.id;
        document.getElementById('edit_language_id').value = challenge.language_id;
        document.getElementById('edit_title').value = challenge.title;
        document.getElementById('edit_description').value = challenge.description;
        document.getElementById('edit_expected_output').value = challenge.expected_output;
        document.getElementById('edit_points_reward').value = challenge.points_reward;
        document.getElementById('editChallengeModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editChallengeModal').classList.add('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
