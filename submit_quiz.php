<?php
// submit_quiz.php
require_once 'includes/config.php';

// Redirect if not logged in or not a POST request
if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$user = get_app_current_user($conn);
$quiz_id = (int)sanitize_input($_POST['quiz_id']);

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

// Fetch quiz questions and their correct answers
$questions = [];
$stmt = $conn->prepare("SELECT id, correct_answer FROM quiz_questions WHERE quiz_id = ?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $questions[$row['id']] = $row['correct_answer'];
    }
    $result->free();
}
$stmt->close();

$correct_answers_count = 0;
$total_questions = count($questions);

foreach ($questions as $question_id => $correct_answer) {
    $user_answer_key = 'answer_' . $question_id;
    if (isset($_POST[$user_answer_key])) {
        $user_submitted_answer = sanitize_input($_POST[$user_answer_key]);
        // Case-insensitive comparison for fill_in_the_blank, case-sensitive for multiple_choice options
        if (strtolower($user_submitted_answer) === strtolower($correct_answer)) {
            $correct_answers_count++;
        }
    }
}

$points_earned = $correct_answers_count * $quiz['points_per_correct_answer'];
$percentage_correct = ($total_questions > 0) ? ($correct_answers_count / $total_questions) * 100 : 0;
$passed = ($percentage_correct >= $quiz['pass_percentage']);

// Award points if passed
if ($passed) {
    update_user_points($conn, $user['id'], $points_earned, 'quiz_reward');
}

// Record quiz attempt
$stmt = $conn->prepare("INSERT INTO quiz_attempts (user_id, quiz_id, score, total_questions, points_earned, passed) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiiiii", $user['id'], $quiz_id, $correct_answers_count, $total_questions, $points_earned, $passed);
$stmt->execute();
$stmt->close();

// Prepare results message
$result_message = "You answered " . $correct_answers_count . " out of " . $total_questions . " questions correctly. (" . round($percentage_correct, 2) . "%)";
if ($passed) {
    $result_message .= "<br>Congratulations! You passed the quiz and earned " . $points_earned . " points!";
    $result_type = 'success';
} else {
    $result_message .= "<br>You did not pass this time. Keep learning and try again!";
    $result_type = 'error';
}

include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-purple-300">Quiz Results: <?php echo htmlspecialchars($quiz['title']); ?></h1>

    <div class="form-card p-8 rounded-xl shadow-lg text-center
        <?php echo $result_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
        <p class="text-2xl font-semibold mb-4"><?php echo $result_message; ?></p>
        <p class="text-lg text-yellow-300">Your current points: <?php echo get_app_current_user($conn)['points']; ?></p>
    </div>

    <div class="mt-8 text-center space-y-4 md:space-y-0 md:flex md:justify-center md:space-x-4">
        <a href="quizzes.php" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block">
            Back to Quizzes
        </a>
        <?php if (!$passed): ?>
            <a href="take_quiz.php?quiz_id=<?php echo $quiz_id; ?>" class="transparent-button bg-purple-700 hover:bg-purple-800 px-6 py-3 rounded-full text-lg font-medium text-white inline-block">
                Try Again
            </a>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
