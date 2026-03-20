<?php
// challenges.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn);

// Fetch all coding challenges, including associated language and user's submission status
$challenges = [];
$stmt = $conn->prepare("
    SELECT 
        cc.*, 
        pl.name as language_name,
        cs.status as user_submission_status,
        cs.submission_date as user_submission_date,
        cs.points_awarded as user_points_awarded
    FROM coding_challenges cc
    LEFT JOIN programming_languages pl ON cc.language_id = pl.id
    LEFT JOIN challenge_submissions cs ON cs.challenge_id = cc.id AND cs.user_id = ?
    ORDER BY cc.created_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $challenges[] = $row;
    }
    $result->free();
}
$stmt->close();

include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-purple-300">Coding Challenges</h1>
    <p class="text-lg text-center text-purple-100 mb-8">
        Sharpen your coding skills by tackling these challenges! Submit your solutions and earn points upon review.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($challenges)): ?>
            <?php foreach ($challenges as $challenge): ?>
                <div class="form-card p-6 rounded-xl shadow-lg flex flex-col justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold mb-2 text-purple-200"><?php echo htmlspecialchars($challenge['title']); ?></h2>
                        <?php if ($challenge['language_name']): ?>
                            <p class="text-sm text-gray-400 mb-1">Language: <span class="font-semibold"><?php echo htmlspecialchars($challenge['language_name']); ?></span></p>
                        <?php endif; ?>
                        <p class="text-md text-yellow-300 font-bold mb-3">Reward: <?php echo $challenge['points_reward']; ?> Points</p>
                        <p class="text-sm text-gray-300 mb-4"><?php echo htmlspecialchars(substr($challenge['description'], 0, 150)); ?>...</p>
                        
                        <?php if ($challenge['user_submission_status']): ?>
                            <p class="text-sm font-bold mt-2">Your Status: 
                                <span class="px-2 py-1 rounded-full text-xs 
                                    <?php 
                                        if ($challenge['user_submission_status'] === 'pending') echo 'bg-yellow-900 text-yellow-300';
                                        elseif ($challenge['user_submission_status'] === 'approved') echo 'bg-green-900 text-green-300';
                                        else echo 'bg-red-900 text-red-300';
                                    ?>">
                                    <?php echo htmlspecialchars(ucfirst($challenge['user_submission_status'])); ?>
                                </span>
                                <?php if ($challenge['user_submission_status'] === 'approved'): ?>
                                    <span class="ml-1 text-green-300">(+<?php echo $challenge['user_points_awarded']; ?> pts)</span>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-500">Submitted: <?php echo date('Y-m-d H:i', strtotime($challenge['user_submission_date'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4">
                        <a href="submit_challenge.php?challenge_id=<?php echo $challenge['id']; ?>" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 w-full text-center">
                            <?php echo $challenge['user_submission_status'] ? 'View/Resubmit' : 'Attempt Challenge'; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-purple-100 col-span-full text-center">No coding challenges available yet. Check back later!</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
