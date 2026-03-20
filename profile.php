<?php
// profile.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn);

// Fetch user's completed lessons count
$completed_lessons_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM user_lesson_progress WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $completed_lessons_count = $result->fetch_row()[0];
    $result->free();
}
$stmt->close();

// Fetch user's passed quizzes count
$passed_quizzes_count = 0;
$stmt = $conn->prepare("SELECT COUNT(DISTINCT quiz_id) FROM quiz_attempts WHERE user_id = ? AND passed = TRUE");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $passed_quizzes_count = $result->fetch_row()[0];
    $result->free();
}
$stmt->close();

// Fetch user's approved challenges count
$approved_challenges_count = 0;
$stmt = $conn->prepare("SELECT COUNT(DISTINCT challenge_id) FROM challenge_submissions WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $approved_challenges_count = $result->fetch_row()[0];
    $result->free();
}
$stmt->close();

// Fetch user's equipped profile items (e.g., avatar, banner)
$equipped_avatar_url = 'https://placehold.co/100x100/aaaaaa/ffffff?text=User'; // Default avatar
$equipped_banner_url = ''; // Default empty banner

$stmt = $conn->prepare("
    SELECT pi.type, pi.value 
    FROM user_profile_items upi
    JOIN profile_items pi ON upi.item_id = pi.id
    WHERE upi.user_id = ? AND upi.equipped = TRUE
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['type'] === 'avatar') {
            $equipped_avatar_url = htmlspecialchars($row['value']);
        } elseif ($row['type'] === 'banner') {
            $equipped_banner_url = htmlspecialchars($row['value']);
        }
    }
    $result->free();
}
$stmt->close();


include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-purple-300">My Profile</h1>

    <div class="form-card p-8 rounded-xl shadow-lg text-center relative overflow-hidden">
        <?php if ($equipped_banner_url): ?>
            <div class="absolute top-0 left-0 w-full h-40 bg-cover bg-center rounded-t-xl" style="background-image: url('<?php echo $equipped_banner_url; ?>');"></div>
            <div class="relative pt-24 pb-4"> <!-- Adjust padding based on banner height -->
        <?php else: ?>
            <div class="relative py-4">
        <?php endif; ?>
            <img src="<?php echo $equipped_avatar_url; ?>" alt="Profile Avatar" class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-purple-600 shadow-lg object-cover">
            <h2 class="text-3xl font-bold text-purple-200 mb-2"><?php echo htmlspecialchars($user['username']); ?></h2>
            <p class="text-lg text-yellow-300 mb-4">Points: <span class="font-bold"><?php echo $user['points']; ?></span></p>
            <p class="text-md text-purple-100 mb-2">Premium Status: <span class="font-bold <?php echo $user['is_premium'] ? 'text-green-400' : 'text-red-400'; ?>"><?php echo $user['is_premium'] ? 'Active' : 'Inactive'; ?></span></p>
            <?php if ($user['premium_expires_at']): ?>
                <p class="text-sm text-gray-400 mb-4">Expires: <?php echo date('Y-m-d H:i', strtotime($user['premium_expires_at'])); ?></p>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 text-left">
                <div class="bg-gray-800 bg-opacity-50 p-4 rounded-lg">
                    <h3 class="text-xl font-semibold text-purple-300 mb-2">Lessons Completed</h3>
                    <p class="text-3xl font-bold text-yellow-300"><?php echo $completed_lessons_count; ?></p>
                </div>
                <div class="bg-gray-800 bg-opacity-50 p-4 rounded-lg">
                    <h3 class="text-xl font-semibold text-purple-300 mb-2">Quizzes Passed</h3>
                    <p class="text-3xl font-bold text-yellow-300"><?php echo $passed_quizzes_count; ?></p>
                </div>
                <div class="bg-gray-800 bg-opacity-50 p-4 rounded-lg">
                    <h3 class="text-xl font-semibold text-purple-300 mb-2">Challenges Approved</h3>
                    <p class="text-3xl font-bold text-yellow-300"><?php echo $approved_challenges_count; ?></p>
                </div>
            </div>

            <div class="mt-8 space-y-4">
                <a href="user_badges.php" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block w-full md:w-auto">
                    View My Badges
                </a>
                <a href="redeem_gifts.php" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block w-full md:w-auto">
                    Redeem Profile Items / Gifts
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
