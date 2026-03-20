<?php
// user_badges.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn);

// Fetch all badges earned by the user
$user_badges = [];
$stmt = $conn->prepare("
    SELECT 
        b.name, 
        b.description, 
        b.image_url, 
        b.criteria,
        ub.awarded_at
    FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = ?
    ORDER BY ub.awarded_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $user_badges[] = $row;
    }
    $result->free();
}
$stmt->close();

include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-purple-300">My Badges</h1>
    <p class="text-lg text-center text-purple-100 mb-8">
        A collection of your achievements on LearnX! Keep learning to earn more.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($user_badges)): ?>
            <?php foreach ($user_badges as $badge): ?>
                <div class="form-card p-6 rounded-xl shadow-lg flex flex-col items-center text-center">
                    <img src="<?php echo htmlspecialchars($badge['image_url'] ?: 'https://placehold.co/80x80/6a0dad/ffffff?text=Badge'); ?>" alt="<?php echo htmlspecialchars($badge['name']); ?>" class="w-20 h-20 object-cover rounded-full mb-4 border-2 border-yellow-400 shadow-md">
                    <h2 class="text-2xl font-semibold mb-2 text-purple-200"><?php echo htmlspecialchars($badge['name']); ?></h2>
                    <p class="text-sm text-gray-300 mb-2"><?php echo htmlspecialchars($badge['description']); ?></p>
                    <p class="text-xs text-gray-400 mb-4">Awarded: <?php echo date('Y-m-d', strtotime($badge['awarded_at'])); ?></p>
                    <div class="mt-auto pt-4 border-t border-gray-700 w-full">
                        <p class="text-xs text-purple-300 font-semibold">Criteria: <?php echo htmlspecialchars($badge['criteria']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-purple-100 col-span-full text-center">You haven't earned any badges yet. Keep learning and completing tasks!</p>
        <?php endif; ?>
    </div>

    <div class="mt-8 text-center">
        <a href="profile.php" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block">
            Back to Profile
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
