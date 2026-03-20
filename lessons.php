<?php
// lessons.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn); 
$language_id = isset($_GET['language_id']) ? (int)$_GET['language_id'] : 0;

if ($language_id === 0) {
    // If no language ID is provided, redirect to dashboard or a language selection page
    redirect('dashboard.php');
}

// Get language details
$language = null;
$stmt = $conn->prepare("SELECT * FROM programming_languages WHERE id = ?");
$stmt->bind_param("i", $language_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $language = $result->fetch_assoc();
}
$stmt->close();

if (!$language) {
    // Language not found
    redirect('dashboard.php'); // Or show an error
}

// Get lessons for the selected language, and check user's completion status
$lessons = [];
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
    WHERE l.language_id = ? 
    ORDER BY l.title ASC
");
$stmt->bind_param("ii", $user['id'], $language_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lessons[] = $row;
    }
    $result->free();
}
$stmt->close();

include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-4 text-center text-purple-300"><?php echo htmlspecialchars($language['name']); ?> Lessons</h1>
    <p class="text-lg text-center text-purple-100 mb-8"><?php echo htmlspecialchars($language['description']); ?></p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($lessons)): ?>
            <?php foreach ($lessons as $lesson): ?>
                <div class="form-card p-6 rounded-xl shadow-lg flex flex-col justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold mb-2 text-purple-200"><?php echo htmlspecialchars($lesson['title']); ?></h2>
                        <?php if ($lesson['is_premium']): ?>
                            <span class="text-yellow-400 text-sm font-bold bg-yellow-900 bg-opacity-50 px-2 py-1 rounded-full">PREMIUM</span>
                        <?php elseif ($lesson['points_cost'] > 0): ?>
                            <span class="text-blue-400 text-sm font-bold bg-blue-900 bg-opacity-50 px-2 py-1 rounded-full">COST: <?php echo $lesson['points_cost']; ?> PTS</span>
                        <?php else: ?>
                            <span class="text-green-400 text-sm font-bold bg-green-900 bg-opacity-50 px-2 py-1 rounded-full">FREE</span>
                        <?php endif; ?>

                        <?php if ($lesson['is_completed']): ?>
                            <span class="ml-2 text-green-500 text-sm font-bold bg-green-900 bg-opacity-50 px-2 py-1 rounded-full">COMPLETED</span>
                        <?php endif; ?>

                        <p class="text-sm text-gray-300 mt-3 mb-4">
                            <?php
                                if ($lesson['content_type'] == 'text') {
                                    echo htmlspecialchars(substr($lesson['content'], 0, 150)) . '...';
                                } else {
                                    echo 'This lesson is available via Google Drive link.';
                                }
                            ?>
                        </p>
                    </div>
                    <div class="mt-4 space-y-2">
                        <?php
                        $can_access = true;
                        $button_text = 'Start Lesson';
                        $button_link = 'lesson_detail.php?lesson_id=' . $lesson['id'];
                        $button_class = 'transparent-button px-4 py-2 rounded-full text-sm font-medium text-white hover:text-purple-200 w-full text-center';

                        if ($lesson['is_premium'] && !$user['is_premium']) {
                            $can_access = false;
                            $button_text = 'Requires Premium';
                            $button_link = 'buy_points.php'; // Link to buy premium
                            $button_class .= ' opacity-70 cursor-not-allowed';
                        } elseif ($lesson['points_cost'] > 0 && $user['points'] < $lesson['points_cost'] && !$user['is_premium']) {
                            $can_access = false;
                            $button_text = 'Not Enough Points';
                            $button_link = 'buy_points.php'; // Link to buy points
                            $button_class .= ' opacity-70 cursor-not-allowed';
                        }

                        if ($can_access) {
                            echo '<a href="' . $button_link . '" class="' . $button_class . '">' . $button_text . '</a>';
                        } else {
                            echo '<a href="' . $button_link . '" class="' . $button_class . '">' . $button_text . '</a>';
                        }

                        // Link to quiz if available and lesson is completed (or can be accessed)
                        if ($lesson['quiz_id'] && ($can_access || $lesson['is_completed'])) {
                            echo '<a href="take_quiz.php?quiz_id=' . $lesson['quiz_id'] . '" class="transparent-button bg-purple-700 hover:bg-purple-800 px-4 py-2 rounded-full text-sm font-medium text-white w-full text-center mt-2">Take Quiz: ' . htmlspecialchars($lesson['quiz_title']) . '</a>';
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-purple-100 col-span-full text-center">No lessons found for <?php echo htmlspecialchars($language['name']); ?> yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
