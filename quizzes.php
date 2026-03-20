<?php
// quizzes.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn);

// Fetch all quizzes, including associated lesson and language info
$quizzes = [];
$stmt = $conn->prepare("
    SELECT 
        q.*, 
        l.title as lesson_title, 
        pl.name as language_name,
        qa.passed as last_attempt_passed -- Check if user passed this quiz before
    FROM quizzes q
    LEFT JOIN lessons l ON q.lesson_id = l.id
    LEFT JOIN programming_languages pl ON l.language_id = pl.id
    LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id AND qa.user_id = ?
    ORDER BY q.title ASC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $quizzes[] = $row;
    }
    $result->free();
}
$stmt->close();

include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-purple-300">Available Quizzes</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($quizzes)): ?>
            <?php foreach ($quizzes as $quiz): ?>
                <div class="form-card p-6 rounded-xl shadow-lg flex flex-col justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold mb-2 text-purple-200"><?php echo htmlspecialchars($quiz['title']); ?></h2>
                        <?php if ($quiz['lesson_title']): ?>
                            <p class="text-sm text-gray-400 mb-1">Associated Lesson: <span class="font-semibold"><?php echo htmlspecialchars($quiz['lesson_title']); ?></span></p>
                        <?php endif; ?>
                        <?php if ($quiz['language_name']): ?>
                            <p class="text-sm text-gray-400 mb-3">Language: <span class="font-semibold"><?php echo htmlspecialchars($quiz['language_name']); ?></span></p>
                        <?php endif; ?>
                        <p class="text-sm text-gray-300 mb-4"><?php echo htmlspecialchars(substr($quiz['description'], 0, 100)); ?>...</p>
                        <p class="text-md text-yellow-300 font-bold">Points per correct answer: <?php echo $quiz['points_per_correct_answer']; ?></p>
                        <p class="text-md text-blue-300 font-bold">Pass Percentage: <?php echo $quiz['pass_percentage']; ?>%</p>
                        <?php if ($quiz['last_attempt_passed']): ?>
                            <span class="ml-2 text-green-500 text-sm font-bold bg-green-900 bg-opacity-50 px-2 py-1 rounded-full mt-2 inline-block">LAST ATTEMPT PASSED</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4">
                        <a href="take_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 w-full text-center">
                            Take Quiz
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-purple-100 col-span-full text-center">No quizzes available yet. Check back later!</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
