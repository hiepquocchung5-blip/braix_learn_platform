<?php
// leaderboard.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn);

// Fetch top users by points
$leaderboard = [];
$stmt = $conn->prepare("
    SELECT 
        id, 
        username, 
        points, 
        is_premium 
    FROM users 
    ORDER BY points DESC, updated_at ASC 
    LIMIT 100
"); // Limit to top 100 users
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = $row;
    }
    $result->free();
}
$stmt->close();

include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-purple-300">Leaderboard</h1>
    <p class="text-lg text-center text-purple-100 mb-8">
        See who's at the top! Compete with other learners and climb the ranks by earning more points.
    </p>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <?php if (!empty($leaderboard)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th class="w-1/12">Rank</th>
                            <th class="w-6/12">Username</th>
                            <th class="w-3/12 text-right">Points</th>
                            <th class="w-2/12 text-center">Premium</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($leaderboard as $leader_user): ?>
                            <tr class="<?php echo ($leader_user['id'] === $user['id']) ? 'bg-purple-800 bg-opacity-30 font-bold' : ''; ?>">
                                <td><?php echo $rank++; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($leader_user['username']); ?>
                                    <?php if ($leader_user['id'] === $user['id']): ?>
                                        <span class="ml-2 px-2 py-1 rounded-full text-xs bg-yellow-600 text-white">YOU</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right text-yellow-300"><?php echo $leader_user['points']; ?></td>
                                <td class="text-center">
                                    <?php if ($leader_user['is_premium']): ?>
                                        <span class="px-2 py-1 rounded-full text-xs bg-green-700 text-green-100">YES</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded-full text-xs bg-gray-700 text-gray-300">NO</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No users on the leaderboard yet. Start earning points!</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
