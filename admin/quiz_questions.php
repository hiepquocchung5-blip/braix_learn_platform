<?php
// admin/quiz_questions.php
require_once '../includes/config.php';

// Redirect if not logged in or not admin
if (!is_logged_in() || !is_admin($conn)) {
    redirect('../index.php');
}

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
if ($quiz_id === 0) {
    redirect('quizzes.php'); // Redirect if no quiz ID is provided
}

// Fetch quiz details
$quiz = null;
$stmt = $conn->prepare("SELECT id, title FROM quizzes WHERE id = ?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $quiz = $result->fetch_assoc();
}
$stmt->close();

if (!$quiz) {
    redirect('quizzes.php'); // Redirect if quiz not found
}

$message = '';
$message_type = '';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action']);
    $question_text = sanitize_input($_POST['question_text']);
    $question_type = sanitize_input($_POST['question_type']);
    $correct_answer = sanitize_input($_POST['correct_answer']);
    $option_a = sanitize_input($_POST['option_a'] ?? '');
    $option_b = sanitize_input($_POST['option_b'] ?? '');
    $option_c = sanitize_input($_POST['option_c'] ?? '');
    $option_d = sanitize_input($_POST['option_d'] ?? '');

    if ($action === 'add' || $action === 'edit') {
        if ($question_type === 'multiple_choice' && (empty($option_a) || empty($option_b) || empty($correct_answer))) {
            $message = 'For multiple-choice questions, options A, B, and a correct answer are required.';
            $message_type = 'error';
        } elseif ($question_type === 'fill_in_the_blank' && empty($correct_answer)) {
            $message = 'For fill-in-the-blank questions, a correct answer is required.';
            $message_type = 'error';
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type, correct_answer, option_a, option_b, option_c, option_d) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssss", $quiz_id, $question_text, $question_type, $correct_answer, $option_a, $option_b, $option_c, $option_d);
                if ($stmt->execute()) {
                    $message = 'Question added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding question: ' . $conn->error;
                    $message_type = 'error';
                }
                $stmt->close();
            } elseif ($action === 'edit') {
                $id = (int)sanitize_input($_POST['id']);
                $stmt = $conn->prepare("UPDATE quiz_questions SET question_text = ?, question_type = ?, correct_answer = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ? WHERE id = ? AND quiz_id = ?");
                $stmt->bind_param("sssssssii", $question_text, $question_type, $correct_answer, $option_a, $option_b, $option_c, $option_d, $id, $quiz_id);
                if ($stmt->execute()) {
                    $message = 'Question updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating question: ' . $conn->error;
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE id = ? AND quiz_id = ?");
        $stmt->bind_param("ii", $id, $quiz_id);
        if ($stmt->execute()) {
            $message = 'Question deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting question: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Fetch all questions for the current quiz
$questions = [];
$stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    $result->free();
}
$stmt->close();

include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-4 text-center text-red-300">Manage Questions for: <?php echo htmlspecialchars($quiz['title']); ?></h1>
    <p class="text-lg text-center text-purple-100 mb-8"><a href="quizzes.php" class="text-blue-300 hover:underline">&larr; Back to Quizzes</a></p>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Add New Question</h2>
        <form action="quiz_questions.php?quiz_id=<?php echo $quiz['id']; ?>" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label for="question_text" class="block text-purple-200 text-lg font-medium mb-2">Question Text</label>
                <textarea id="question_text" name="question_text" rows="3" placeholder="Enter the question text..." required
                          class="w-full px-4 py-2 rounded-lg"></textarea>
            </div>
            <div>
                <label for="question_type" class="block text-purple-200 text-lg font-medium mb-2">Question Type</label>
                <select id="question_type" name="question_type" required
                        class="w-full px-4 py-2 rounded-lg" onchange="toggleOptionsField()">
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="fill_in_the_blank">Fill-in-the-Blank</option>
                </select>
            </div>
            <div id="mc_options_fields" class="space-y-4">
                <div>
                    <label for="option_a" class="block text-purple-200 text-lg font-medium mb-2">Option A</label>
                    <input type="text" id="option_a" name="option_a" placeholder="Option A text"
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="option_b" class="block text-purple-200 text-lg font-medium mb-2">Option B</label>
                    <input type="text" id="option_b" name="option_b" placeholder="Option B text"
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="option_c" class="block text-purple-200 text-lg font-medium mb-2">Option C (Optional)</label>
                    <input type="text" id="option_c" name="option_c" placeholder="Option C text"
                           class="w-full px-4 py-2 rounded-lg">
                </div>
                <div>
                    <label for="option_d" class="block text-purple-200 text-lg font-medium mb-2">Option D (Optional)</label>
                    <input type="text" id="option_d" name="option_d" placeholder="Option D text"
                           class="w-full px-4 py-2 rounded-lg">
                </div>
            </div>
            <div>
                <label for="correct_answer" class="block text-purple-200 text-lg font-medium mb-2">Correct Answer (For MC: A, B, C, or D; For Fill-in: exact text)</label>
                <input type="text" id="correct_answer" name="correct_answer" placeholder="e.g., B or 'Python'" required
                       class="w-full px-4 py-2 rounded-lg">
            </div>
            <button type="submit" class="admin-button-primary w-full">Add Question</button>
        </form>
    </div>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Existing Questions</h2>
        <?php if (!empty($questions)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Question Text</th>
                            <th>Type</th>
                            <th>Options</th>
                            <th>Correct Answer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $question): ?>
                            <tr>
                                <td><?php echo $question['id']; ?></td>
                                <td><?php echo htmlspecialchars(substr($question['question_text'], 0, 100)); ?>...</td>
                                <td><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($question['question_type']))); ?></td>
                                <td>
                                    <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                        A: <?php echo htmlspecialchars($question['option_a']); ?><br>
                                        B: <?php echo htmlspecialchars($question['option_b']); ?><br>
                                        <?php if ($question['option_c']): ?>C: <?php echo htmlspecialchars($question['option_c']); ?><br><?php endif; ?>
                                        <?php if ($question['option_d']): ?>D: <?php echo htmlspecialchars($question['option_d']); ?><br><?php endif; ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($question['correct_answer']); ?></td>
                                <td class="flex space-x-2">
                                    <button onclick="editQuestion(<?php echo htmlspecialchars(json_encode($question)); ?>)"
                                            class="admin-button-secondary">Edit</button>
                                    <form action="quiz_questions.php?quiz_id=<?php echo $quiz['id']; ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $question['id']; ?>">
                                        <button type="submit" class="admin-button-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No questions added for this quiz yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Question Modal (Hidden by default) -->
<div id="editQuestionModal" class="message-overlay hidden">
    <div class="message-box">
        <div class="message-box-header">Edit Question</div>
        <div class="message-box-content">
            <form id="editQuestionForm" action="quiz_questions.php?quiz_id=<?php echo $quiz['id']; ?>" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div>
                    <label for="edit_question_text" class="block text-purple-200 text-lg font-medium mb-2">Question Text</label>
                    <textarea id="edit_question_text" name="question_text" rows="3" required
                              class="w-full px-4 py-2 rounded-lg"></textarea>
                </div>
                <div>
                    <label for="edit_question_type" class="block text-purple-200 text-lg font-medium mb-2">Question Type</label>
                    <select id="edit_question_type" name="question_type" required
                            class="w-full px-4 py-2 rounded-lg" onchange="toggleEditOptionsField()">
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="fill_in_the_blank">Fill-in-the-Blank</option>
                    </select>
                </div>
                <div id="edit_mc_options_fields" class="space-y-4">
                    <div>
                        <label for="edit_option_a" class="block text-purple-200 text-lg font-medium mb-2">Option A</label>
                        <input type="text" id="edit_option_a" name="option_a"
                               class="w-full px-4 py-2 rounded-lg">
                    </div>
                    <div>
                        <label for="edit_option_b" class="block text-purple-200 text-lg font-medium mb-2">Option B</label>
                        <input type="text" id="edit_option_b" name="option_b"
                               class="w-full px-4 py-2 rounded-lg">
                    </div>
                    <div>
                        <label for="edit_option_c" class="block text-purple-200 text-lg font-medium mb-2">Option C (Optional)</label>
                        <input type="text" id="edit_option_c" name="option_c"
                               class="w-full px-4 py-2 rounded-lg">
                    </div>
                    <div>
                        <label for="edit_option_d" class="block text-purple-200 text-lg font-medium mb-2">Option D (Optional)</label>
                        <input type="text" id="edit_option_d" name="option_d"
                               class="w-full px-4 py-2 rounded-lg">
                    </div>
                </div>
                <div>
                    <label for="edit_correct_answer" class="block text-purple-200 text-lg font-medium mb-2">Correct Answer (For MC: A, B, C, or D; For Fill-in: exact text)</label>
                    <input type="text" id="edit_correct_answer" name="correct_answer" required
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
    function toggleOptionsField() {
        const questionType = document.getElementById('question_type').value;
        const mcOptionsFields = document.getElementById('mc_options_fields');
        if (questionType === 'multiple_choice') {
            mcOptionsFields.classList.remove('hidden');
        } else {
            mcOptionsFields.classList.add('hidden');
        }
    }

    function toggleEditOptionsField() {
        const questionType = document.getElementById('edit_question_type').value;
        const mcOptionsFields = document.getElementById('edit_mc_options_fields');
        if (questionType === 'multiple_choice') {
            mcOptionsFields.classList.remove('hidden');
        } else {
            mcOptionsFields.classList.add('hidden');
        }
    }

    function editQuestion(question) {
        document.getElementById('edit_id').value = question.id;
        document.getElementById('edit_question_text').value = question.question_text;
        document.getElementById('edit_question_type').value = question.question_type;
        document.getElementById('edit_correct_answer').value = question.correct_answer;
        document.getElementById('edit_option_a').value = question.option_a;
        document.getElementById('edit_option_b').value = question.option_b;
        document.getElementById('edit_option_c').value = question.option_c;
        document.getElementById('edit_option_d').value = question.option_d;
        
        toggleEditOptionsField(); // Adjust visibility based on type

        document.getElementById('editQuestionModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editQuestionModal').classList.add('hidden');
    }

    // Initial call to set visibility on page load
    document.addEventListener('DOMContentLoaded', () => {
        toggleOptionsField();
        // If editing, also ensure the edit modal's fields are correct
        if (document.getElementById('editQuestionModal') && !document.getElementById('editQuestionModal').classList.contains('hidden')) {
            toggleEditOptionsField();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
