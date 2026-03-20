<?php
// admin/settings.php
require_once '../includes/config.php';

// Redirect if not logged in or not admin
if (!is_logged_in() || !is_admin($conn)) {
    redirect('../index.php');
}

$message = '';
$message_type = '';

// Handle setting updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action']);

    if ($action === 'update_ads_url') {
        $ads_url = sanitize_input($_POST['ads_url']);
        update_admin_setting($conn, 'ads_url', $ads_url);
        $message = 'Ad URL updated successfully!';
        $message_type = 'success';
    }
}

// Fetch current settings
$current_ads_url = get_admin_setting($conn, 'ads_url');

include '../includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-red-300">Application Settings</h1>

    <?php if ($message): ?>
        <div class="form-card p-4 rounded-lg mb-6 text-center
            <?php echo $message_type === 'success' ? 'bg-green-900 bg-opacity-50 border border-green-700 text-green-200' : 'bg-red-900 bg-opacity-50 border border-red-700 text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-3xl font-semibold mb-6 text-purple-200">Ad URL Settings</h2>
        <form action="settings.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_ads_url">
            <div>
                <label for="ads_url" class="block text-purple-200 text-lg font-medium mb-2">Ad Display URL (for normal users)</label>
                <input type="url" id="ads_url" name="ads_url" placeholder="https://example.com/your-ads"
                       value="<?php echo htmlspecialchars($current_ads_url); ?>"
                       class="w-full px-4 py-2 rounded-lg">
                <p class="text-sm text-gray-400 mt-2">This URL will be shown to non-premium users to view ads.</p>
            </div>
            <button type="submit" class="admin-button-primary w-full">Update Ad URL</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
