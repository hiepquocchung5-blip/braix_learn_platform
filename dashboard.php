<?php
date_default_timezone_set('Asia/Yangon'); // Or your preferred timezone
// dashboard.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn); 

// --- Daily Login Bonus Logic ---
$daily_bonus_points = (int)get_admin_setting($conn, 'daily_bonus_points');
$last_login_timestamp = strtotime($user['last_login']);
$current_date = date('Y-m-d');
$last_login_date = date('Y-m-d', $last_login_timestamp);

$daily_bonus_message = '';

if ($current_date > $last_login_date) {
    // It's a new day, award daily bonus
    update_user_points($conn, $user['id'], $daily_bonus_points, 'daily_bonus');

    // Update last_login timestamp in users table
    $stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $stmt->close();

    // Refresh user data after update
    $user = get_app_current_user($conn);
    $daily_bonus_message = "Daily login bonus! You earned " . $daily_bonus_points . " points!";
}

// Get programming languages
$languages = [];
$result = $conn->query("SELECT * FROM programming_languages ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $languages[] = $row;
    }
    $result->free();
}

// Get ad URL for normal users
$ads_url = null;
if (!$user['is_premium']) {
    $ads_url = get_admin_setting($conn, 'ads_url');
}

include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-purple-300">Your Dashboard</h1>

    <?php if ($daily_bonus_message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center bg-green-900 bg-opacity-50 border border-green-700 text-green-200">
            <?php echo htmlspecialchars($daily_bonus_message); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        <!-- User Info Card -->
        <div class="form-card p-6 rounded-xl shadow-lg text-center">
            <h2 class="text-2xl font-semibold mb-4 text-purple-200">Welcome Back, <?php echo htmlspecialchars($user['username']); ?>!</h2>
            <p class="text-lg text-yellow-300 mb-2">Current Points: <span class="font-bold text-3xl"><?php echo $user['points']; ?></span></p>
            <p class="text-lg text-purple-100 mb-4">Premium Status: <span class="font-bold <?php echo $user['is_premium'] ? 'text-green-400' : 'text-red-400'; ?>"><?php echo $user['is_premium'] ? 'Active' : 'Inactive'; ?></span></p>
            <?php if ($user['premium_expires_at']): ?>
                <p class="text-sm text-gray-400 mb-2">Expires: <?php echo date('Y-m-d H:i', strtotime($user['premium_expires_at'])); ?></p>
            <?php endif; ?>
            <a href="buy_points.php" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block mt-4">
                <?php echo $user['is_premium'] ? 'Renew Premium / Buy More Points' : 'Get Premium / Buy Points'; ?>
            </a>
        </div>

        <!-- Programming Languages Card -->
        <div class="form-card p-6 rounded-xl shadow-lg col-span-1 md:col-span-2">
            <h2 class="text-2xl font-semibold mb-4 text-purple-200">Start Learning!</h2>
            <p class="text-lg text-purple-100 mb-6">Choose a programming language to begin your journey:</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php if (!empty($languages)): ?>
                    <?php foreach ($languages as $lang): ?>
                        <a href="lessons.php?language_id=<?php echo $lang['id']; ?>" class="transparent-button flex flex-col items-center p-4 rounded-xl text-center hover:scale-105 transition-transform duration-300">
                            <h3 class="text-xl font-bold text-purple-200 mb-2"><?php echo htmlspecialchars($lang['name']); ?></h3>
                            <p class="text-sm text-gray-300"><?php echo htmlspecialchars(substr($lang['description'], 0, 100)); ?>...</p>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-purple-100 col-span-full">No programming languages available yet. Check back later!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- New Feature Links -->
    <div class="form-card p-8 rounded-xl shadow-lg mt-12">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">More Fun & Engagement!</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="redeem_gifts.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center flex items-center justify-center space-x-2">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c1.657 0 3 .895 3 2s-1.343 2-3 2-3 .895-3 2 1.343 2 3 2m0-8V9m0 3v1m0 3v1m0-10a2 2 0 00-2 2v3.25M4.5 7.5V4a2 2 0 012-2h3.25M4.5 16.5V20a2 2 0 002 2h3.25M19.5 7.5V4a2 2 0 00-2-2h-3.25M19.5 16.5V20a2 2 0 01-2 2h-3.25m-11.25-3h3.25m-3.25 0V7.5m11.25 0h-3.25m3.25 0V16.5"></path></svg>
                <span>Redeem Gifts</span>
            </a>
            <a href="quizzes.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center flex items-center justify-center space-x-2">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <span>Quizzes</span>
            </a>
            <a href="challenges.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center flex items-center justify-center space-x-2">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                <span>Challenges</span>
            </a>
            <a href="leaderboard.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center flex items-center justify-center space-x-2">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                <span>Leaderboard</span>
            </a>
            <a href="community.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center flex items-center justify-center space-x-2">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h2a2 2 0 002-2V4a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h2m0 0l-4 4m4-4l4 4m-9-4h2m-2 0h2m-2 0l-4 4m4-4l4 4"></path></svg>
                <span>Community</span>
            </a>
            <a href="profile.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center flex items-center justify-center space-x-2">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span>My Profile</span>
            </a>
        </div>
    </div>

    <?php if (!$user['is_premium'] && $ads_url): ?>
        <div class="form-card p-6 rounded-xl shadow-lg text-center mt-12">
            <h2 class="text-2xl font-semibold mb-4 text-purple-200">Support Us!</h2>
            <p class="text-lg text-purple-100 mb-4">As a non-premium user, you can support us by viewing ads.</p>
            <a href="<?php echo htmlspecialchars($ads_url); ?>" target="_blank" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block">
                View Ads
                <script> /* filter */ </script>
            </a>
            <p class="text-sm text-gray-400 mt-2">Clicking this will open an external ad page.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
