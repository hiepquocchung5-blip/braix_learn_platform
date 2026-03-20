<?php
// lesson_detail.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn); 
$lesson_id = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;

if ($lesson_id === 0) {
    redirect('dashboard.php');
}

// Get lesson details and check completion status and quiz
$lesson = null;
$stmt = $conn->prepare("
    SELECT 
        l.*, 
        pl.name as language_name,
        ulp.completed_at IS NOT NULL as is_completed,
        q.id as quiz_id,
        q.title as quiz_title
    FROM lessons l 
    JOIN programming_languages pl ON l.language_id = pl.id 
    LEFT JOIN user_lesson_progress ulp ON ulp.lesson_id = l.id AND ulp.user_id = ?
    LEFT JOIN quizzes q ON q.lesson_id = l.id
    WHERE l.id = ?
");
$stmt->bind_param("ii", $user['id'], $lesson_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $lesson = $result->fetch_assoc();
}
$stmt->close();

if (!$lesson) {
    redirect('dashboard.php'); // Lesson not found
}

// Handle marking lesson as complete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_complete') {
    if (!$lesson['is_completed']) {
        $stmt_insert = $conn->prepare("INSERT INTO user_lesson_progress (user_id, lesson_id) VALUES (?, ?)");
        $stmt_insert->bind_param("ii", $user['id'], $lesson['id']);
        if ($stmt_insert->execute()) {
            // Award points for completion (optional, define points per lesson or per completion)
            // For now, let's say 10 points per lesson completion
            update_user_points($conn, $user['id'], 10, 'earn_points'); 
            $message = 'Lesson marked as complete! You earned 10 points.';
            $message_type = 'success';
            $lesson['is_completed'] = true; // Update status immediately
            $user = get_app_current_user($conn); // Refresh user data
        } else {
            $message = 'Error marking lesson complete: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt_insert->close();
    } else {
        $message = 'This lesson is already marked as complete.';
        $message_type = 'info';
    }
}


// Check access rights
$has_access = true;
$access_message = '';

if ($lesson['is_premium'] && !$user['is_premium']) {
    $has_access = false;
    $access_message = 'This is a premium lesson. Please upgrade to premium to access it.';
} elseif ($lesson['points_cost'] > 0 && !$user['is_premium']) {
    // Only deduct points if the lesson is NOT already completed AND user is not premium
    if (!$lesson['is_completed']) {
        if ($user['points'] >= $lesson['points_cost']) {
            // Deduct points
            update_user_points($conn, $user['id'], -$lesson['points_cost'], 'spend_points');
            // Refresh user data after point deduction
            $user = get_app_current_user($conn);
            $access_message = 'Points deducted successfully to access this lesson!';
            $message_type = 'success'; // Set message type for display
        } else {
            $has_access = false;
            $access_message = 'You do not have enough points to access this lesson. You need ' . $lesson['points_cost'] . ' points.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-8">
    <div class="form-card p-8 rounded-xl shadow-lg">
        <h1 class="text-4xl font-bold mb-4 text-purple-300"><?php echo htmlspecialchars($lesson['title']); ?></h1>
        <p class="text-lg text-purple-100 mb-6">Language: <span class="font-semibold"><?php echo htmlspecialchars($lesson['language_name']); ?></span></p>

        <?php if ($message): ?>
            <div class="form-card p-4 rounded-lg mb-6 text-center
                <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : ($message_type === 'info' ? 'bg-blue-900 bg-opacity-50 border border-blue-700 text-blue-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$has_access): ?>
            <div class="bg-red-900 bg-opacity-50 border border-red-700 text-red-200 p-4 rounded-lg mb-6">
                <p class="font-bold">Access Denied!</p>
                <p><?php echo htmlspecialchars($access_message); ?></p>
                <a href="buy_points.php" class="transparent-button px-4 py-2 rounded-full text-sm font-medium text-white hover:text-red-200 inline-block mt-4">
                    Get Points / Premium
                </a>
            </div>
        <?php else: ?>
            <?php if ($access_message && $message_type === 'success'): // Only show success message if access was granted now ?>
                <div class="bg-green-900 bg-opacity-50 border border-green-700 text-green-200 p-4 rounded-lg mb-6">
                    <p><?php echo htmlspecialchars($access_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($lesson['content_type'] == 'text'): ?>
                <div class="prose prose-invert max-w-none text-gray-200 leading-relaxed">
                    <p><?php echo nl2br(htmlspecialchars($lesson['content'])); ?></p>
                </div>
            <?php elseif ($lesson['content_type'] == 'drive_link'): ?>
                <div class="text-center">
                    <p class="text-lg mb-4 text-purple-100">This lesson is available via Google Drive.</p>
                    <a href="<?php echo htmlspecialchars($lesson['content']); ?>" target="_blank" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block">
                        Open Google Drive Link
                        <svg class="inline-block ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                    <p class="text-sm text-gray-400 mt-2">The link will open in a new tab.</p>
                </div>
            <?php endif; ?>

            <div class="mt-8 text-center space-y-4 md:space-y-0 md:flex md:justify-center md:space-x-4">
                <a href="lessons.php?language_id=<?php echo $lesson['language_id']; ?>" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block">
                    Back to Lessons
                </a>

                <?php if (!$lesson['is_completed']): ?>
                    <form action="lesson_detail.php?lesson_id=<?php echo $lesson['id']; ?>" method="POST" class="inline-block">
                        <input type="hidden" name="action" value="mark_complete">
                        <button type="submit" class="transparent-button bg-green-700 hover:bg-green-800 px-6 py-3 rounded-full text-lg font-medium text-white">
                            Mark as Complete
                        </button>
                    </form>
                <?php else: ?>
                    <button class="transparent-button bg-gray-700 px-6 py-3 rounded-full text-lg font-medium text-white cursor-not-allowed opacity-70">
                        Already Completed
                    </button>
                <?php endif; ?>

                <?php if ($lesson['quiz_id']): ?>
                    <a href="take_quiz.php?quiz_id=<?php echo $lesson['quiz_id']; ?>" class="transparent-button bg-purple-700 hover:bg-purple-800 px-6 py-3 rounded-full text-lg font-medium text-white inline-block">
                        Take Quiz: <?php echo htmlspecialchars($lesson['quiz_title']); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
