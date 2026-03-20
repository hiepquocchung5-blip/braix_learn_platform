<?php
// admin/transactions.php
require_once '../includes/config.php';

// Redirect if not logged in or not admin
if (!is_logged_in() || !is_admin($conn)) {
    redirect('../index.php');
}

$message = '';
$message_type = '';

// Handle transaction actions (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action']);
    $transaction_id = (int)sanitize_input($_POST['transaction_id']);

    // Fetch transaction details
    $transaction = null;
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $transaction = $result->fetch_assoc();
    }
    $stmt->close();

    if ($transaction) {
        if ($action === 'approve' && $transaction['status'] === 'pending') {
            // Update transaction status to completed
            $stmt = $conn->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $transaction_id);
            if ($stmt->execute()) {
                // Add points to user
                if ($transaction['type'] === 'buy_points') {
                    update_user_points($conn, $transaction['user_id'], $transaction['amount'], 'earn_points');
                    $message = 'Transaction approved and ' . $transaction['amount'] . ' points added to user!';
                } elseif ($transaction['type'] === 'buy_premium') {
                    // If premium was bought, directly set premium status (assuming amount is 1 for premium purchase)
                    $stmt_user = $conn->prepare("UPDATE users SET is_premium = TRUE WHERE id = ?");
                    $stmt_user->bind_param("i", $transaction['user_id']);
                    $stmt_user->execute();
                    $stmt_user->close();
                    $message = 'Transaction approved and user granted premium access!';
                }
                $message_type = 'success';
            } else {
                $message = 'Error approving transaction: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($action === 'reject' && $transaction['status'] === 'pending') {
            $stmt = $conn->prepare("UPDATE transactions SET status = 'failed' WHERE id = ?");
            $stmt->bind_param("i", $transaction_id);
            if ($stmt->execute()) {
                $message = 'Transaction rejected!';
                $message_type = 'success';
            } else {
                $message = 'Error rejecting transaction: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } else {
        $message = 'Transaction not found.';
        $message_type = 'error';
    }
}

// Fetch all transactions with user details
$transactions = [];
$stmt = $conn->prepare("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $result->free();
}
$stmt->close();

include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Manage Transactions</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">All Transactions</h2>
        <?php if (!empty($transactions)): ?>
            <div class="overflow-x-auto">
                <table class="space-table min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th>Ref</th>
                            <th>QR</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo $t['id']; ?></td>
                                <td><?php echo htmlspecialchars($t['username']); ?></td>
                                <td><?php echo htmlspecialchars($t['type']); ?></td>
                                <td><?php echo $t['amount']; ?></td>
                                <td>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?php
                                            if ($t['status'] === 'pending') echo 'bg-yellow-900 text-yellow-300';
                                            elseif ($t['status'] === 'completed') echo 'bg-green-900 text-green-300';
                                            else echo 'bg-red-900 text-red-300';
                                        ?>">
                                        <?php echo htmlspecialchars(ucfirst($t['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($t['payment_method'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($t['transaction_ref'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($t['qr_image_url']): ?>
                                        <a href="<?php echo htmlspecialchars($t['qr_image_url']); ?>" target="_blank" class="text-blue-400 hover:underline">View QR</a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($t['created_at'])); ?></td>
                                <td class="flex space-x-2">
                                    <?php if ($t['status'] === 'pending'): ?>
                                        <form action="transactions.php" method="POST" onsubmit="return confirm('Approve this transaction?');">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" class="admin-button-primary">Approve</button>
                                        </form>
                                        <form action="transactions.php" method="POST" onsubmit="return confirm('Reject this transaction?');">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" class="admin-button-danger">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-gray-500 text-sm">No actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-purple-100 text-center">No transactions recorded yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
