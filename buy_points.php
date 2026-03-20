<?php
// buy_points.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn);
$message = '';
$message_type = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action']);

    if ($action === 'buy_points') {
        $points_amount = (int)sanitize_input($_POST['points_amount']);
        $payment_ref = sanitize_input($_POST['payment_ref']); // User enters their KBZ Pay transaction reference

        if ($points_amount > 0 && !empty($payment_ref)) {
            // Record the pending transaction
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, status, payment_method, qr_image_url, transaction_ref) VALUES (?, 'buy_points', ?, 'pending', 'KBZ Pay', ?, ?)");
            $qr_image_url = KBZ_PAY_QR_IMAGE; // Use the defined placeholder
            $stmt->bind_param("iiss", $user['id'], $points_amount, $qr_image_url, $payment_ref);

            if ($stmt->execute()) {
                $message = 'Your request to buy ' . $points_amount . ' points is pending. Please wait for admin approval.';
                $message_type = 'success';
            } else {
                $message = 'Error submitting request: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Please enter a valid points amount and payment reference.';
            $message_type = 'error';
        }
    } elseif ($action === 'buy_premium') {
        $premium_cost = 1000; // Example cost for permanent premium access in points
        if ($user['points'] >= $premium_cost) {
            // Deduct points and grant premium (permanent)
            update_user_points($conn, $user['id'], -$premium_cost, 'spend_points');

            $stmt = $conn->prepare("UPDATE users SET is_premium = TRUE, premium_expires_at = NULL WHERE id = ?"); // Set expires_at to NULL for permanent
            $stmt->bind_param("i", $user['id']);
            if ($stmt->execute()) {
                $message = 'Congratulations! You are now a permanent premium user.';
                $message_type = 'success';
                // Refresh user data
                $user = get_app_current_user($conn);
            } else {
                $message = 'Error granting premium access: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = 'You do not have enough points to buy permanent premium access. You need ' . $premium_cost . ' points.';
            $message_type = 'error';
        }
    } elseif ($action === 'buy_temp_premium') {
        $temp_premium_cost = 500; // Example cost for temporary premium access in points
        $duration_days = 30; // Example: 30 days of premium
        if ($user['points'] >= $temp_premium_cost) {
            // Deduct points and grant temporary premium
            update_user_points($conn, $user['id'], -$temp_premium_cost, 'spend_points');

            $expiry_date = date('Y-m-d H:i:s', strtotime("+" . $duration_days . " days"));
            $stmt = $conn->prepare("UPDATE users SET is_premium = TRUE, premium_expires_at = ? WHERE id = ?");
            $stmt->bind_param("si", $expiry_date, $user['id']);
            if ($stmt->execute()) {
                $message = 'Congratulations! You have gained ' . $duration_days . ' days of premium access!';
                $message_type = 'success';
                // Refresh user data
                $user = get_app_current_user($conn);
            } else {
                $message = 'Error granting temporary premium access: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = 'You do not have enough points to buy temporary premium access. You need ' . $temp_premium_cost . ' points.';
            $message_type = 'error';
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-purple-300">Get More Points & Premium Access</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Buy Points Section -->
        <div class="form-card p-8 rounded-xl shadow-lg">
            <h2 class="text-3xl font-semibold mb-6 text-purple-200">Buy Points via KBZ Pay</h2>
            <p class="text-lg text-purple-100 mb-4">
                You can purchase points by transferring money via KBZ Pay.
                Please scan the QR code below and enter your transaction reference.
            </p>

            <div class="flex justify-center mb-6">
                <img src="<?php echo KBZ_PAY_QR_IMAGE; ?>" alt="KBZ Pay QR Code" class="w-64 h-64 object-contain border border-gray-700 rounded-lg shadow-md">
            </div>
            <p class="text-sm text-gray-400 text-center mb-6">
                (This is a placeholder QR code. In a real application, this would be a dynamic, verifiable QR.)
            </p>

            <form action="buy_points.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="buy_points">
                <div>
                    <label for="points_amount" class="block text-purple-200 text-lg font-medium mb-2">Points Amount (Example: 1000 points = 1000 MMK)</label>
                    <input type="number" id="points_amount" name="points_amount" min="100" step="100" placeholder="e.g., 1000" required
                           class="w-full px-4 py-2 rounded-lg bg-gray-700 bg-opacity-50 border border-gray-600 text-white focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label for="payment_ref" class="block text-purple-200 text-lg font-medium mb-2">Your KBZ Pay Transaction Reference</label>
                    <input type="text" id="payment_ref" name="payment_ref" placeholder="e.g., KBP-XXXXXXXX" required
                           class="w-full px-4 py-2 rounded-lg bg-gray-700 bg-opacity-50 border border-gray-600 text-white focus:ring-purple-500 focus:border-purple-500">
                </div>
                <button type="submit" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 w-full">
                    Submit Payment Request
                </button>
            </form>
        </div>

        <!-- Buy Premium Section -->
        <div class="form-card p-8 rounded-xl shadow-lg">
            <h2 class="text-3xl font-semibold mb-6 text-purple-200">Unlock Premium Access</h2>
            <?php if ($user['is_premium']): ?>
                <div class="bg-green-900 bg-opacity-50 border border-green-700 text-green-200 p-4 rounded-lg mb-6 text-center">
                    <p class="font-bold">You are already a premium user!</p>
                    <?php if ($user['premium_expires_at']): ?>
                        <p>Your premium access expires on <?php echo date('Y-m-d H:i', strtotime($user['premium_expires_at'])); ?>.</p>
                    <?php else: ?>
                        <p>Enjoy unlimited access to all premium lessons.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="text-lg text-purple-100 mb-4">
                    Get unlimited access to all premium lessons.
                </p>
                <p class="text-sm text-gray-400 mb-6">
                    (You currently have <span class="font-bold text-yellow-300"><?php echo $user['points']; ?></span> points.)
                </p>

                <div class="space-y-4">
                    <h3 class="text-xl font-semibold text-purple-200">Permanent Premium: <span class="text-yellow-300">1000 Points</span></h3>
                    <form action="buy_points.php" method="POST">
                        <input type="hidden" name="action" value="buy_premium">
                        <button type="submit"
                                class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 w-full
                                <?php echo ($user['points'] < 1000) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                <?php echo ($user['points'] < 1000) ? 'disabled' : ''; ?>>
                            Buy Permanent Premium
                        </button>
                    </form>

                    <h3 class="text-xl font-semibold text-purple-200 pt-4">Temporary Premium (30 Days): <span class="text-yellow-300">500 Points</span></h3>
                    <form action="buy_points.php" method="POST">
                        <input type="hidden" name="action" value="buy_temp_premium">
                        <button type="submit"
                                class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 w-full
                                <?php echo ($user['points'] < 500) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                <?php echo ($user['points'] < 500) ? 'disabled' : ''; ?>>
                            Buy 30-Day Premium
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            <div class="mt-8 text-center">
                <a href="dashboard.php" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
