<?php
// submit_challenge.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn);
$challenge_id = isset($_GET['challenge_id']) ? (int)$_GET['challenge_id'] : 0;
$message = '';
$message_type = '';

if ($challenge_id === 0) {
    redirect('challenges.php');
}

// Fetch challenge details
$challenge = null;
$stmt = $conn->prepare("
    SELECT 
        cc.*, 
        pl.name as language_name,
        cs.id as submission_id,
        cs.code_submission,
        cs.submission_date,
        cs.admin_notes,
        cs.status as submission_status,
        cs.points_awarded
    FROM coding_challenges cc
    LEFT JOIN programming_languages pl ON cc.language_id = pl.id
    LEFT JOIN challenge_submissions cs ON cs.challenge_id = cc.id AND cs.user_id = ?
    WHERE cc.id = ?
    ORDER BY cs.submission_date DESC LIMIT 1 -- Get the latest submission if multiple
");
$stmt->bind_param("ii", $user['id'], $challenge_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $challenge = $result->fetch_assoc();
}
$stmt->close();

if (!$challenge) {
    redirect('challenges.php'); // Challenge not found
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    $code_submission = sanitize_input($_POST['code_submission']);

    if (empty($code_submission)) {
        $message = 'Please provide your code solution.';
        $message_type = 'error';
    } else {
        // If there's an existing submission, update it. Otherwise, insert new.
        if ($challenge['submission_id']) {
            $stmt_update = $conn->prepare("UPDATE challenge_submissions SET code_submission = ?, submission_date = CURRENT_TIMESTAMP, status = 'pending', admin_notes = NULL, points_awarded = 0 WHERE id = ?");
            $stmt_update->bind_param("si", $code_submission, $challenge['submission_id']);
            if ($stmt_update->execute()) {
                $message = 'Your submission has been updated and is awaiting review!';
                $message_type = 'success';
                // Refresh challenge data to show updated status
                $challenge['code_submission'] = $code_submission;
                $challenge['submission_status'] = 'pending';
                $challenge['admin_notes'] = NULL;
                $challenge['points_awarded'] = 0;
                $challenge['submission_date'] = date('Y-m-d H:i:s'); // Update date for display
            } else {
                $message = 'Error updating submission: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt_update->close();
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO challenge_submissions (challenge_id, user_id, code_submission, status) VALUES (?, ?, ?, 'pending')");
            $stmt_insert->bind_param("iis", $challenge['id'], $user['id'], $code_submission);
            if ($stmt_insert->execute()) {
                $message = 'Your solution has been submitted and is awaiting review!';
                $message_type = 'success';
                // Set submission_id for future updates
                $challenge['submission_id'] = $stmt_insert->insert_id;
                $challenge['code_submission'] = $code_submission;
                $challenge['submission_status'] = 'pending';
                $challenge['submission_date'] = date('Y-m-d H:i:s');
            } else {
                $message = 'Error submitting solution: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt_insert->close();
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-4 text-center text-purple-300">Challenge: <?php echo htmlspecialchars($challenge['title']); ?></h1>
    <p class="text-lg text-center text-purple-100 mb-8">Language: <span class="font-semibold"><?php echo htmlspecialchars($challenge['language_name']); ?></span> | Reward: <span class="font-bold text-yellow-300"><?php echo $challenge['points_reward']; ?> Points</span></p>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-3xl font-semibold mb-4 text-purple-200">Challenge Description</h2>
        <div class="prose prose-invert max-w-none text-gray-200 leading-relaxed mb-6">
            <p><?php echo nl2br(htmlspecialchars($challenge['description'])); ?></p>
            <?php if ($challenge['expected_output']): ?>
                <h3 class="text-xl font-semibold mt-4 text-purple-300">Expected Output:</h3>
                <pre class="bg-gray-800 text-gray-100 p-4 rounded-lg overflow-x-auto whitespace-pre-wrap break-words"><?php echo htmlspecialchars($challenge['expected_output']); ?></pre>
            <?php endif; ?>
        </div>

        <?php if ($challenge['submission_status']): ?>
            <h2 class="text-3xl font-semibold mb-4 text-purple-200">Your Last Submission</h2>
            <div class="bg-gray-800 bg-opacity-50 p-4 rounded-lg mb-6">
                <p class="text-lg text-purple-100 mb-2">Status: 
                    <span class="px-3 py-1 rounded-full font-bold 
                        <?php 
                            if ($challenge['submission_status'] === 'pending') echo 'bg-yellow-700 text-yellow-100';
                            elseif ($challenge['submission_status'] === 'approved') echo 'bg-green-700 text-green-100';
                            else echo 'bg-red-700 text-red-100';
                        ?>">
                        <?php echo htmlspecialchars(ucfirst($challenge['submission_status'])); ?>
                    </span>
                    <?php if ($challenge['submission_status'] === 'approved'): ?>
                        <span class="ml-2 text-green-300">(+<?php echo $challenge['points_awarded']; ?> pts)</span>
                    <?php endif; ?>
                </p>
                <p class="text-sm text-gray-400 mb-2">Submitted On: <?php echo date('Y-m-d H:i', strtotime($challenge['submission_date'])); ?></p>
                <h3 class="text-lg font-semibold mt-4 text-purple-200">Your Code:</h3>
                <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto whitespace-pre-wrap break-words text-sm"><?php echo htmlspecialchars($challenge['code_submission']); ?></pre>
                <?php if ($challenge['admin_notes']): ?>
                    <h3 class="text-lg font-semibold mt-4 text-purple-200">Admin Notes:</h3>
                    <p class="text-gray-300"><?php echo nl2br(htmlspecialchars($challenge['admin_notes'])); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <h2 class="text-3xl font-semibold mb-4 text-purple-200">Submit Your Solution</h2>
        <form action="submit_challenge.php?challenge_id=<?php echo $challenge['id']; ?>" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="submit">
            <div>
                <label for="code_submission" class="block text-purple-200 text-lg font-medium mb-2">Your Code or Link (e.g., GitHub Gist, Pastebin)</label>
                <textarea id="code_submission" name="code_submission" rows="10" placeholder="Paste your code here, or a link to your solution (e.g., https://gist.github.com/your-code)" required
                          class="w-full px-4 py-2 rounded-lg"></textarea>
            </div>
            <button type="submit" class="transparent-button px-8 py-4 rounded-full text-xl font-bold text-white hover:text-purple-200 w-full">
                Submit Solution
            </button>
        </form>
    </div>

    <div class="mt-8 text-center">
        <a href="challenges.php" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block">
            Back to Challenges
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
