<?php
// take_quiz.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn);
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

if ($quiz_id === 0) {
    redirect('quizzes.php');
}

// Fetch quiz details
$quiz = null;
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $quiz = $result->fetch_assoc();
}
$stmt->close();

if (!$quiz) {
    redirect('quizzes.php'); // Quiz not found
}

// Fetch quiz questions
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

if (empty($questions)) {
    // No questions for this quiz
    $message = 'This quiz has no questions yet. Please check back later.';
    $message_type = 'info';
    include 'includes/header.php';
    echo '<div class="container py-8"><div class="form-card p-4 rounded-lg mb-6 text-center bg-blue-900 bg-opacity-50 border border-blue-700 text-blue-200">' . htmlspecialchars($message) . '</div><div class="mt-8 text-center"><a href="quizzes.php" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block">Back to Quizzes</a></div></div>';
    include 'includes/footer.php';
    exit();
}

include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-4 text-center text-purple-300">Quiz: <?php echo htmlspecialchars($quiz['title']); ?></h1>
    <p class="text-lg text-center text-purple-100 mb-8"><?php echo htmlspecialchars($quiz['description']); ?></p>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <form action="submit_quiz.php" method="POST" class="space-y-8">
            <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
            
            <?php $q_num = 1; foreach ($questions as $question): ?>
                <div class="mb-6 border-b border-gray-700 pb-6 last:border-b-0 last:pb-0">
                    <p class="text-xl font-semibold mb-4 text-purple-200">Q<?php echo $q_num++; ?>. <?php echo htmlspecialchars($question['question_text']); ?></p>
                    
                    <?php if ($question['question_type'] === 'multiple_choice'): ?>
                        <div class="space-y-3">
                            <label class="flex items-center text-gray-200">
                                <input type="radio" name="answer_<?php echo $question['id']; ?>" value="A" class="form-radio h-5 w-5 text-purple-600">
                                <span class="ml-3"><?php echo htmlspecialchars($question['option_a']); ?></span>
                            </label>
                            <label class="flex items-center text-gray-200">
                                <input type="radio" name="answer_<?php echo $question['id']; ?>" value="B" class="form-radio h-5 w-5 text-purple-600">
                                <span class="ml-3"><?php echo htmlspecialchars($question['option_b']); ?></span>
                            </label>
                            <?php if ($question['option_c']): ?>
                            <label class="flex items-center text-gray-200">
                                <input type="radio" name="answer_<?php echo $question['id']; ?>" value="C" class="form-radio h-5 w-5 text-purple-600">
                                <span class="ml-3"><?php echo htmlspecialchars($question['option_c']); ?></span>
                            </label>
                            <?php endif; ?>
                            <?php if ($question['option_d']): ?>
                            <label class="flex items-center text-gray-200">
                                <input type="radio" name="answer_<?php echo $question['id']; ?>" value="D" class="form-radio h-5 w-5 text-purple-600">
                                <span class="ml-3"><?php echo htmlspecialchars($question['option_d']); ?></span>
                            </label>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($question['question_type'] === 'fill_in_the_blank'): ?>
                        <div>
                            <input type="text" name="answer_<?php echo $question['id']; ?>" placeholder="Your answer"
                                   class="w-full px-4 py-2 rounded-lg bg-gray-700 bg-opacity-50 border border-gray-600 text-white focus:ring-purple-500 focus:border-purple-500">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="mt-8 text-center">
                <button type="submit" class="transparent-button px-8 py-4 rounded-full text-xl font-bold text-white hover:text-purple-200 inline-block">
                    Submit Quiz
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
