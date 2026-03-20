<?php
// admin/challenge_submissions.php
require_once '../includes/config.php';

// Redirect if not logged in or not admin
if (!is_logged_in() || !is_admin($conn)) {
    redirect('../index.php');
}

$message = '';
$message_type = '';

// Handle submission review (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action']);
    $submission_id = (int)sanitize_input($_POST['submission_id']);
    $admin_notes = sanitize_input($_POST['admin_notes'] ?? '');

    // Fetch submission details
    $submission = null;
    $stmt = $conn->prepare("
        SELECT cs.*, cc.points_reward, u.username
        FROM challenge_submissions cs
        JOIN coding_challenges cc ON cs.challenge_id = cc.id
        JOIN users u ON cs.user_id = u.id
        WHERE cs.id = ?
    ");
    $stmt->bind_param("i", $submission_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $submission = $result->fetch_assoc();
    }
    $stmt->close();

    if ($submission) {
        if ($action === 'approve' && $submission['status'] === 'pending') {
            $points_to_award = $submission['points_reward'];
            // Update submission status
            $stmt_update = $conn->prepare("UPDATE challenge_submissions SET status = 'approved', points_awarded = ?, admin_notes = ? WHERE id = ?");
            $stmt_update->bind_param("isi", $points_to_award, $admin_notes, $submission_id);
            if ($stmt_update->execute()) {
                // Award points to user
                update_user_points($conn, $submission['user_id'], $points_to_award, 'challenge_reward');
                $message = 'Submission approved and ' . $points_to_award . ' points awarded to ' . htmlspecialchars($submission['username']) . '!';
                $message_type = 'success';
            } else {
                $message = 'Error approving submission: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt_update->close();
        } elseif ($action === 'reject' && $submission['status'] === 'pending') {
            // Update submission status
            $stmt_update = $conn->prepare("UPDATE challenge_submissions SET status = 'rejected', points_awarded = 0, admin_notes = ? WHERE id = ?");
            $stmt_update->bind_param("si", $admin_notes, $submission_id);
            if ($stmt_update->execute()) {
                $message = 'Submission rejected for ' . htmlspecialchars($submission['username']) . '.';
                $message_type = 'success';
            } else {
                $message = 'Error rejecting submission: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt_update->close();
        } elseif ($action === 'delete') {
            $stmt_delete = $conn->prepare("DELETE FROM challenge_submissions WHERE id = ?");
            $stmt_delete->bind_param("i", $submission_id);
            if ($stmt_delete->execute()) {
                $message = 'Submission deleted successfully.';
                $message_type = 'success';
            } else {
                $message = 'Error deleting submission: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt_delete->close();
        }
    } else {
        $message = 'Submission not found.';
        $message_type = 'error';
    }
}

// Fetch all challenge submissions
$submissions = [];
$stmt = $conn->prepare("
    SELECT 
        cs.*, 
        u.username, 
        cc.title as challenge_title,
        pl.name as language_name
    FROM challenge_submissions cs
    JOIN users u ON cs.user_id = u.id
    JOIN coding_challenges cc ON cs.challenge_id = cc.id
    JOIN programming_languages pl ON cc.language_id = pl.id
    ORDER BY cs.submission_date DESC
");
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
    $result->free();
}
$stmt->close();

include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Review Challenge Submissions</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">All Submissions</h2>
        <?php if (!empty($submissions)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Challenge</th>
                            <th>Language</th>
                            <th>Code</th>
                            <th>Status</th>
                            <th>Points</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?php echo $submission['id']; ?></td>
                                <td><?php echo htmlspecialchars($submission['username']); ?></td>
                                <td><?php echo htmlspecialchars($submission['challenge_title']); ?></td>
                                <td><?php echo htmlspecialchars($submission['language_name']); ?></td>
                                <td>
                                    <pre class="bg-gray-800 text-gray-100 p-2 rounded-lg text-xs max-h-20 overflow-auto whitespace-pre-wrap break-words"><?php echo htmlspecialchars($submission['code_submission']); ?></pre>
                                </td>
                                <td>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?php
                                            if ($submission['status'] === 'pending') echo 'bg-yellow-900 text-yellow-300';
                                            elseif ($submission['status'] === 'approved') echo 'bg-green-900 text-green-300';
                                            else echo 'bg-red-900 text-red-300';
                                        ?>">
                                        <?php echo htmlspecialchars(ucfirst($submission['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $submission['points_awarded']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($submission['submission_date'])); ?></td>
                                <td class="flex space-x-2">
                                    <?php if ($submission['status'] === 'pending'): ?>
                                        <button onclick="reviewSubmission(<?php echo htmlspecialchars(json_encode($submission)); ?>)"
                                                class="admin-button-primary">Review</button>
                                    <?php else: ?>
                                        <button onclick="viewSubmissionDetails(<?php echo htmlspecialchars(json_encode($submission)); ?>)"
                                                class="admin-button-secondary">View</button>
                                        <form action="challenge_submissions.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this submission?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                            <button type="submit" class="admin-button-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No challenge submissions to review yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Review/View Submission Modal (Hidden by default) -->
<div id="reviewSubmissionModal" class="message-overlay hidden">
    <div class="message-box max-w-2xl w-full">
        <div class="message-box-header" id="modal_header">Review Submission</div>
        <div class="message-box-content">
            <p class="text-lg text-purple-100 mb-2">User: <span id="modal_username" class="font-semibold"></span></p>
            <p class="text-lg text-purple-100 mb-4">Challenge: <span id="modal_challenge_title" class="font-semibold"></span></p>
            <h3 class="text-xl font-semibold mb-2 text-purple-200">Submitted Code:</h3>
            <pre id="modal_code_submission" class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto whitespace-pre-wrap break-words text-sm mb-4"></pre>
            <h3 class="text-xl font-semibold mb-2 text-purple-200">Admin Notes:</h3>
            <textarea id="modal_admin_notes" name="admin_notes" rows="5" placeholder="Add notes for the user (optional)"
                      class="w-full px-4 py-2 rounded-lg mb-4"></textarea>
            
            <div class="message-box-footer flex justify-end space-x-4">
                <button type="button" onclick="closeReviewModal()" class="admin-button-secondary">Close</button>
                <form id="approveForm" action="challenge_submissions.php" method="POST" class="inline-block">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" id="approve_submission_id" name="submission_id">
                    <input type="hidden" id="approve_admin_notes" name="admin_notes">
                    <button type="submit" class="admin-button-primary" id="approve_button">Approve</button>
                </form>
                <form id="rejectForm" action="challenge_submissions.php" method="POST" class="inline-block">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" id="reject_submission_id" name="submission_id">
                    <input type="hidden" id="reject_admin_notes" name="admin_notes">
                    <button type="submit" class="admin-button-danger" id="reject_button">Reject</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let currentSubmissionId = null;

    function populateModal(submission, isReviewMode) {
        document.getElementById('modal_header').textContent = isReviewMode ? 'Review Submission' : 'Submission Details';
        document.getElementById('modal_username').textContent = submission.username;
        document.getElementById('modal_challenge_title').textContent = submission.challenge_title;
        document.getElementById('modal_code_submission').textContent = submission.code_submission;
        document.getElementById('modal_admin_notes').value = submission.admin_notes || '';

        const approveButton = document.getElementById('approve_button');
        const rejectButton = document.getElementById('reject_button');
        const adminNotesTextarea = document.getElementById('modal_admin_notes');

        if (isReviewMode) {
            approveButton.style.display = 'inline-block';
            rejectButton.style.display = 'inline-block';
            adminNotesTextarea.readOnly = false;
        } else {
            approveButton.style.display = 'none';
            rejectButton.style.display = 'none';
            adminNotesTextarea.readOnly = true;
        }

        currentSubmissionId = submission.id;
        document.getElementById('approve_submission_id').value = submission.id;
        document.getElementById('reject_submission_id').value = submission.id;

        document.getElementById('reviewSubmissionModal').classList.remove('hidden');
    }

    function reviewSubmission(submission) {
        populateModal(submission, true);
    }

    function viewSubmissionDetails(submission) {
        populateModal(submission, false);
    }

    function closeReviewModal() {
        document.getElementById('reviewSubmissionModal').classList.add('hidden');
        currentSubmissionId = null;
    }

    // Attach event listeners for admin notes to update hidden fields
    document.getElementById('modal_admin_notes').addEventListener('input', function() {
        if (currentSubmissionId !== null) {
            document.getElementById('approve_admin_notes').value = this.value;
            document.getElementById('reject_admin_notes').value = this.value;
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
