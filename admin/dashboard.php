<?php
// admin/dashboard.php
require_once '../includes/config.php';

// Redirect if not logged in or not admin
if (!is_logged_in() || !is_admin($conn)) {
    redirect('../index.php');
}

// Calling the renamed function
$user = get_app_current_user($conn); 

// Fetch some dashboard stats
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_languages = $conn->query("SELECT COUNT(*) FROM programming_languages")->fetch_row()[0];
$total_lessons = $conn->query("SELECT COUNT(*) FROM lessons")->fetch_row()[0];
$pending_transactions = $conn->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'")->fetch_row()[0];
$pending_challenge_submissions = $conn->query("SELECT COUNT(*) FROM challenge_submissions WHERE status = 'pending'")->fetch_row()[0]; // NEW STAT

include '../includes/header.php'; // Use the main header
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Admin Dashboard</h1>

    <!-- Introductory Text for Admin -->
    <div class="form-card p-6 rounded-xl shadow-lg text-center mb-8">
        <p class="text-lg text-purple-100 mb-4">
            Welcome to your Braix Admin Panel! From here, you can manage all aspects of your "Earn to Learn" platform.
            Monitor user activity, update content, and oversee transactions to keep your community thriving.
        </p>
        <p class="text-md text-gray-300">
            Use the sections below to navigate through different management tools.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div class="form-card p-6 rounded-xl shadow-lg text-center">
            <h2 class="text-2xl font-semibold text-purple-200">Total Users</h2>
            <p class="text-5xl font-bold text-yellow-300 mt-4"><?php echo $total_users; ?></p>
        </div>
        <div class="form-card p-6 rounded-xl shadow-lg text-center">
            <h2 class="text-2xl font-semibold text-purple-200">Programming Languages</h2>
            <p class="text-5xl font-bold text-yellow-300 mt-4"><?php echo $total_languages; ?></p>
        </div>
        <div class="form-card p-6 rounded-xl shadow-lg text-center">
            <h2 class="text-2xl font-semibold text-purple-200">Total Lessons</h2>
            <p class="text-5xl font-bold text-yellow-300 mt-4"><?php echo $total_lessons; ?></p>
        </div>
        <div class="form-card p-6 rounded-xl shadow-lg text-center">
            <h2 class="text-2xl font-semibold text-purple-200">Pending Payments</h2>
            <p class="text-5xl font-bold text-red-400 mt-4"><?php echo $pending_transactions; ?></p>
        </div>
        <div class="form-card p-6 rounded-xl shadow-lg text-center">
            <h2 class="text-2xl font-semibold text-purple-200">Pending Challenge Submissions</h2>
            <p class="text-5xl font-bold text-red-400 mt-4"><?php echo $pending_challenge_submissions; ?></p>
        </div>
    </div>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Admin Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="languages.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Manage Languages
            </a>
            <a href="lessons.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Manage Lessons
            </a>
            <a href="users.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Manage Users
            </a>
            <a href="transactions.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Manage Transactions
            </a>
            <a href="gifts.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Manage Gifts
            </a>
            <a href="quizzes.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Manage Quizzes
            </a>
            <a href="coding_challenges.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Manage Challenges
            </a>
            <a href="challenge_submissions.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Review Submissions
            </a>
            <a href="badges.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Manage Badges
            </a>
            <a href="profile_items.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Manage Profile Items
            </a>
            <a href="ai_prompts.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Manage AI Prompts
            </a>
            <a href="community.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                Manage Community
            </a>
            <a href="settings.php" class="transparent-button px-6 py-4 rounded-xl text-lg font-medium text-white hover:text-purple-200 text-center">
                App Settings
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
