<?php
// redeem_gifts.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn);
$message = '';
$message_type = ''; // 'success' or 'error'

// Handle gift redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize_input($_POST['action']);

    if ($action === 'redeem_gift') {
        $gift_id = (int)sanitize_input($_POST['gift_id']);

        // Fetch gift details
        $gift = null;
        $stmt = $conn->prepare("SELECT * FROM gifts WHERE id = ?");
        $stmt->bind_param("i", $gift_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $gift = $result->fetch_assoc();
        }
        $stmt->close();

        if ($gift) {
            if ($user['points'] >= $gift['points_cost']) {
                // Check stock if limited
                if ($gift['stock'] !== -1 && $gift['stock'] <= 0) {
                    $message = 'Sorry, this gift is currently out of stock.';
                    $message_type = 'error';
                } else {
                    // Deduct points
                    update_user_points($conn, $user['id'], -$gift['points_cost'], 'redeem_gift');

                    // Decrease stock if limited
                    if ($gift['stock'] !== -1) {
                        $stmt_stock = $conn->prepare("UPDATE gifts SET stock = stock - 1 WHERE id = ?");
                        $stmt_stock->bind_param("i", $gift_id);
                        $stmt_stock->execute();
                        $stmt_stock->close();
                    }

                    // Log the specific gift redemption in transactions
                    $stmt_log_gift = $conn->prepare("INSERT INTO transactions (user_id, type, amount, status, payment_method, gift_id) VALUES (?, 'redeem_gift', ?, 'completed', 'points', ?)");
                    $stmt_log_gift->bind_param("iii", $user['id'], $gift['points_cost'], $gift['id']);
                    $stmt_log_gift->execute();
                    $stmt_log_gift->close();

                    $message = 'Congratulations! You have successfully redeemed "' . htmlspecialchars($gift['name']) . '". Your points balance is now ' . ($user['points'] - $gift['points_cost']) . '.';
                    $message_type = 'success';
                    // Refresh user data after point deduction
                    $user = get_app_current_user($conn);
                }
            } else {
                $message = 'You do not have enough points to redeem "' . htmlspecialchars($gift['name']) . '". You need ' . $gift['points_cost'] . ' points.';
                $message_type = 'error';
            }
        } else {
            $message = 'Gift not found.';
            $message_type = 'error';
        }
    } elseif ($action === 'redeem_profile_item' || $action === 'equip_profile_item') {
        $item_id = (int)sanitize_input($_POST['item_id']);

        // Fetch profile item details
        $item = null;
        $stmt = $conn->prepare("SELECT * FROM profile_items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();
        }
        $stmt->close();

        if (!$item) {
            $message = 'Profile item not found.';
            $message_type = 'error';
        } else {
            // Check if user already owns this item
            $owns_item = false;
            $stmt_check_own = $conn->prepare("SELECT COUNT(*) FROM user_profile_items WHERE user_id = ? AND item_id = ?");
            $stmt_check_own->bind_param("ii", $user['id'], $item_id);
            $stmt_check_own->execute();
            $owns_item = $stmt_check_own->get_result()->fetch_row()[0] > 0;
            $stmt_check_own->close();

            if ($action === 'redeem_profile_item') {
                if ($owns_item) {
                    $message = 'You already own this profile item.';
                    $message_type = 'info';
                } elseif ($item['is_premium_exclusive'] && !$user['is_premium']) {
                    $message = 'This is a premium exclusive item. Upgrade to premium to unlock.';
                    $message_type = 'error';
                } elseif ($user['points'] >= $item['points_cost']) {
                    // Deduct points and add to user's owned items
                    update_user_points($conn, $user['id'], -$item['points_cost'], 'spend_points');
                    
                    $stmt_add_item = $conn->prepare("INSERT INTO user_profile_items (user_id, item_id) VALUES (?, ?)");
                    $stmt_add_item->bind_param("ii", $user['id'], $item_id);
                    if ($stmt_add_item->execute()) {
                        $message = 'You have successfully purchased "' . htmlspecialchars($item['name']) . '"!';
                        $message_type = 'success';
                        $user = get_app_current_user($conn); // Refresh user data
                    } else {
                        $message = 'Error purchasing item: ' . $conn->error;
                        $message_type = 'error';
                    }
                    $stmt_add_item->close();
                } else {
                    $message = 'You do not have enough points to purchase "' . htmlspecialchars($item['name']) . '". You need ' . $item['points_cost'] . ' points.';
                    $message_type = 'error';
                }
            } elseif ($action === 'equip_profile_item') {
                if (!$owns_item) {
                    $message = 'You must purchase this item before you can equip it.';
                    $message_type = 'error';
                } else {
                    // Unequip any other item of the same type
                    $stmt_unequip = $conn->prepare("UPDATE user_profile_items upi JOIN profile_items pi ON upi.item_id = pi.id SET upi.equipped = FALSE WHERE upi.user_id = ? AND pi.type = ?");
                    $stmt_unequip->bind_param("is", $user['id'], $item['type']);
                    $stmt_unequip->execute();
                    $stmt_unequip->close();

                    // Equip the selected item
                    $stmt_equip = $conn->prepare("UPDATE user_profile_items SET equipped = TRUE WHERE user_id = ? AND item_id = ?");
                    $stmt_equip->bind_param("ii", $user['id'], $item_id);
                    if ($stmt_equip->execute()) {
                        $message = htmlspecialchars($item['name']) . ' has been equipped!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error equipping item: ' . $conn->error;
                        $message_type = 'error';
                    }
                    $stmt_equip->close();
                }
            }
        }
    }
}


// Fetch all available gifts
$gifts = [];
$result = $conn->query("SELECT * FROM gifts ORDER BY points_cost ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $gifts[] = $row;
    }
    $result->free();
}

// Fetch all available profile items and user's owned/equipped status
$profile_items = [];
$stmt = $conn->prepare("
    SELECT 
        pi.*,
        upi.equipped IS NOT NULL as is_owned,
        upi.equipped as is_equipped
    FROM profile_items pi
    LEFT JOIN user_profile_items upi ON upi.item_id = pi.id AND upi.user_id = ?
    ORDER BY pi.type, pi.points_cost ASC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $profile_items[] = $row;
    }
    $result->free();
}
$stmt->close();


include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-purple-300">Redeem Gifts & Customize Profile!</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : ($message_type === 'info' ? 'bg-blue-900 bg-opacity-50 border border-blue-700 text-blue-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg mb-8 text-center">
        <h2 class="text-2xl font-semibold mb-4 text-purple-200">Your Current Points:</h2>
        <p class="text-5xl font-bold text-yellow-300"><?php echo $user['points']; ?></p>
    </div>

    <!-- Gifts Section -->
    <div class="form-card p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Physical & Digital Gifts</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (!empty($gifts)): ?>
                <?php foreach ($gifts as $gift): ?>
                    <div class="form-card p-6 rounded-xl shadow-lg flex flex-col items-center text-center">
                        <img src="<?php echo htmlspecialchars($gift['image_url'] ?: 'https://placehold.co/150x150/4a0e7e/ffffff?text=Gift'); ?>" alt="<?php echo htmlspecialchars($gift['name']); ?>" class="w-32 h-32 object-cover rounded-lg mb-4 border border-purple-700">
                        <h2 class="text-2xl font-semibold mb-2 text-purple-200"><?php echo htmlspecialchars($gift['name']); ?></h2>
                        <p class="text-lg text-yellow-300 font-bold mb-3"><?php echo $gift['points_cost']; ?> Points</p>
                        <p class="text-sm text-gray-300 mb-4"><?php echo htmlspecialchars($gift['description']); ?></p>
                        <?php if ($gift['stock'] !== -1): ?>
                            <p class="text-sm text-gray-400 mb-4">Stock: <?php echo $gift['stock'] > 0 ? $gift['stock'] : 'Out of Stock'; ?></p>
                        <?php endif; ?>

                        <form action="redeem_gifts.php" method="POST" class="mt-auto w-full">
                            <input type="hidden" name="action" value="redeem_gift">
                            <input type="hidden" name="gift_id" value="<?php echo $gift['id']; ?>">
                            <button type="submit"
                                    class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 w-full
                                    <?php echo ($user['points'] < $gift['points_cost'] || ($gift['stock'] !== -1 && $gift['stock'] <= 0)) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                    <?php echo ($user['points'] < $gift['points_cost'] || ($gift['stock'] !== -1 && $gift['stock'] <= 0)) ? 'disabled' : ''; ?>>
                                Redeem Now
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-purple-100 col-span-full text-center">No gifts available for redemption yet. Check back later!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Customization Section -->
    <div class="form-card p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Profile Customization Items</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (!empty($profile_items)): ?>
                <?php foreach ($profile_items as $item): ?>
                    <div class="form-card p-6 rounded-xl shadow-lg flex flex-col items-center text-center">
                        <img src="<?php echo htmlspecialchars($item['value'] ?: 'https://placehold.co/150x150/4a0e7e/ffffff?text=Item'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-32 h-32 object-cover rounded-lg mb-4 border border-purple-700">
                        <h2 class="text-2xl font-semibold mb-2 text-purple-200"><?php echo htmlspecialchars($item['name']); ?></h2>
                        <p class="text-lg text-yellow-300 font-bold mb-3"><?php echo $item['points_cost']; ?> Points</p>
                        <p class="text-sm text-gray-300 mb-4">Type: <?php echo htmlspecialchars(ucfirst($item['type'])); ?></p>
                        <?php if ($item['is_premium_exclusive']): ?>
                            <span class="text-yellow-400 text-sm font-bold bg-yellow-900 bg-opacity-50 px-2 py-1 rounded-full mb-2">PREMIUM EXCLUSIVE</span>
                        <?php endif; ?>

                        <div class="mt-auto w-full space-y-2">
                            <?php if ($item['is_owned']): ?>
                                <button class="transparent-button bg-gray-700 px-6 py-3 rounded-full text-lg font-medium text-white w-full cursor-not-allowed opacity-70">
                                    Owned
                                </button>
                                <?php if (!$item['is_equipped']): ?>
                                    <form action="redeem_gifts.php" method="POST" class="w-full">
                                        <input type="hidden" name="action" value="equip_profile_item">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="transparent-button bg-blue-700 hover:bg-blue-800 px-6 py-3 rounded-full text-lg font-medium text-white w-full">
                                            Equip
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="transparent-button bg-green-700 px-6 py-3 rounded-full text-lg font-medium text-white w-full cursor-not-allowed opacity-70">
                                        Equipped
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <form action="redeem_gifts.php" method="POST" class="w-full">
                                    <input type="hidden" name="action" value="redeem_profile_item">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit"
                                            class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 w-full
                                            <?php echo ($user['points'] < $item['points_cost'] || ($item['is_premium_exclusive'] && !$user['is_premium'])) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                            <?php echo ($user['points'] < $item['points_cost'] || ($item['is_premium_exclusive'] && !$user['is_premium'])) ? 'disabled' : ''; ?>>
                                        Purchase
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-purple-100 col-span-full text-center">No profile items available yet. Check back later!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
