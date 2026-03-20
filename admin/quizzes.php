<?php
// admin/quizzes.php
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
        $lesson_id = !empty($_POST['lesson_id']) ? (int)sanitize_input($_POST['lesson_id']) : NULL;
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $points_per_correct_answer = (int)sanitize_input($_POST['points_per_correct_answer']);
        $pass_percentage = (int)sanitize_input($_POST['pass_percentage']);

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO quizzes (lesson_id, title, description, points_per_correct_answer, pass_percentage) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiii", $lesson_id, $title, $description, $points_per_correct_answer, $pass_percentage);
            if ($stmt->execute()) {
                $message = 'Quiz added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding quiz: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $id = (int)sanitize_input($_POST['id']);
            $stmt = $conn->prepare("UPDATE quizzes SET lesson_id = ?, title = ?, description = ?, points_per_correct_answer = ?, pass_percentage = ? WHERE id = ?");
            $stmt->bind_param("isiiii", $lesson_id, $title, $description, $points_per_correct_answer, $pass_percentage, $id);
            if ($stmt->execute()) {
                $message = 'Quiz updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating quiz: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Quiz deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting quiz: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Fetch all lessons for dropdown
$lessons = [];
$result = $conn->query("SELECT id, title, language_id FROM lessons ORDER BY title ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lessons[] = $row;
    }
    $result->free();
}

// Fetch all quizzes with associated lesson info
$quizzes = [];
$stmt = $conn->prepare("
    SELECT 
        q.*, 
        l.title as lesson_title,
        pl.name as language_name
    FROM quizzes q
    LEFT JOIN lessons l ON q.lesson_id = l.id
    LEFT JOIN programming_languages pl ON l.language_id = pl.id
    ORDER BY q.title ASC
");
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $quizzes[] = $row;
    }
    $result->free();
}
$stmt->close();

include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Manage Quizzes</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Add New Quiz</h2>
        <form action="quizzes.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label for="lesson_id" class="block text-purple-200 text-lg font-medium mb-2">Associated Lesson (Optional)</label>
                <select id="lesson_id" name="lesson_id" class="w-full px-4 py-2 rounded-lg">
                    <option value="">No specific lesson</option>
                    <?php foreach ($lessons as $lesson): ?>
                        <option value="<?php echo $lesson['id']; ?>"><?php echo htmlspecialchars($lesson['title']); ?> (Lang ID: <?php echo $lesson['language_id']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="title" class="block text-purple-200 text-lg font-medium mb-2">Quiz Title</label>
                <input type="text" id="title" name="title" placeholder="e.g., Python Basics Quiz" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <div>
                <label for="description" class="block text-purple-200 text-lg font-medium mb-2">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="A brief description of the quiz..."
                          class="w-full px-4 py-2 rounded-lg"></textarea>
            </div>
            <div>
                <label for="points_per_correct_answer" class="block text-purple-200 text-lg font-medium mb-2">Points Per Correct Answer</label>
                <input type="number" id="points_per_correct_answer" name="points_per_correct_answer" value="10" min="0" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <div>
                <label for="pass_percentage" class="block text-purple-200 text-lg font-medium mb-2">Pass Percentage (%)</label>
                <input type="number" id="pass_percentage" name="pass_percentage" value="70" min="0" max="100" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <button type="submit" class="admin-button-primary w-full">Add Quiz</button>
        </form>
    </div>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Existing Quizzes</h2>
        <?php if (!empty($quizzes)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Lesson</th>
                            <th>Lang</th>
                            <th>Points/Q</th>
                            <th>Pass %</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $quiz): ?>
                            <tr>
                                <td><?php echo $quiz['id']; ?></td>
                                <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                <td><?php echo htmlspecialchars($quiz['lesson_title'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($quiz['language_name'] ?: 'N/A'); ?></td>
                                <td><?php echo $quiz['points_per_correct_answer']; ?></td>
                                <td><?php echo $quiz['pass_percentage']; ?>%</td>
                                <td class="flex space-x-2">
                                    <button onclick="editQuiz(<?php echo htmlspecialchars(json_encode($quiz)); ?>)"
                                            class="admin-button-secondary">Edit</button>
                                    <a href="quiz_questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="admin-button-secondary">Questions</a>
                                    <form action="quizzes.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this quiz? This will also delete all associated questions and attempts!');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $quiz['id']; ?>">
                                        <button type="submit" class="admin-button-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No quizzes added yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Quiz Modal (Hidden by default) -->
<div id="editQuizModal" class="message-overlay hidden">
    <div class="message-box">
        <div class="message-box-header">Edit Quiz</div>
        <div class="message-box-content">
            <form id="editQuizForm" action="quizzes.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div>
                    <label for="edit_lesson_id" class="block text-purple-200 text-lg font-medium mb-2">Associated Lesson (Optional)</label>
                    <select id="edit_lesson_id" name="lesson_id" class="w-full px-4 py-2 rounded-lg">
                        <option value="">No specific lesson</option>
                        <?php foreach ($lessons as $lesson): ?>
                            <option value="<?php echo $lesson['id']; ?>"><?php echo htmlspecialchars($lesson['title']); ?> (Lang ID: <?php echo $lesson['language_id']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="edit_title" class="block text-purple-200 text-lg font-medium mb-2">Quiz Title</label>
                    <input type="text" id="edit_title" name="title" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="edit_description" class="block text-purple-200 text-lg font-medium mb-2">Description</label>
                    <textarea id="edit_description" name="description" rows="3"
                              class="w-full px-4 py-2 rounded-lg"></textarea>
                </div>
                <div>
                    <label for="edit_points_per_correct_answer" class="block text-purple-200 text-lg font-medium mb-2">Points Per Correct Answer</label>
                    <input type="number" id="edit_points_per_correct_answer" name="points_per_correct_answer" min="0" required
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="edit_pass_percentage" class="block text-purple-200 text-lg font-medium mb-2">Pass Percentage (%)</label>
                    <input type="number" id="edit_pass_percentage" name="pass_percentage" min="0" max="100" required
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
    function editQuiz(quiz) {
        document.getElementById('edit_id').value = quiz.id;
        document.getElementById('edit_lesson_id').value = quiz.lesson_id || ''; // Set to empty string if null
        document.getElementById('edit_title').value = quiz.title;
        document.getElementById('edit_description').value = quiz.description;
        document.getElementById('edit_points_per_correct_answer').value = quiz.points_per_correct_answer;
        document.getElementById('edit_pass_percentage').value = quiz.pass_percentage;
        document.getElementById('editQuizModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editQuizModal').classList.add('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
