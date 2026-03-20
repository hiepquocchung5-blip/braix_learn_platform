<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// index.php
require_once 'includes/config.php';

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Handle Telegram Login Callback
// This is a simplified example. A full implementation requires
// verifying the Telegram data hash for security.
if (isset($_GET['id']) && isset($_GET['first_name']) && isset($_GET['username']) && isset($_GET['auth_date']) && isset($_GET['hash'])) {
    $telegram_id = sanitize_input($_GET['id']);
    $username = sanitize_input($_GET['username']);
    $first_name = sanitize_input($_GET['first_name']);

    // Use first_name if username is empty
    if (empty($username)) {
        $username = $first_name;
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE telegram_id = ?");
    $stmt->bind_param("s", $telegram_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, log them in
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        redirect('dashboard.php');
    } else {
        // New user, register them
        $stmt_insert = $conn->prepare("INSERT INTO users (telegram_id, username) VALUES (?, ?)");
        $stmt_insert->bind_param("ss", $telegram_id, $username);
        if ($stmt_insert->execute()) {
            $_SESSION['user_id'] = $stmt_insert->insert_id;
            redirect('dashboard.php');
        } else {
            echo "<script>alert('Registration failed: " . $conn->error . "');</script>";
        }
        $stmt_insert->close();
    }
    $stmt->close();
}

include 'includes/header.php';
?>

<div class="container flex flex-col items-center justify-center min-h-[calc(100vh-10rem)] py-12">
    <div class="form-card p-8 rounded-xl shadow-xl text-center max-w-lg w-full">
        <h1 class="text-4xl font-bold mb-6 text-purple-300">Welcome to LearnX!</h1>
        <p class="text-lg mb-8 text-purple-100">Learn programming, earn points, and unlock premium content.</p>

        <!-- Telegram Login Button -->
        <script async src="https://telegram.org/js/telegram-widget.js?22"
                data-telegram-login="braixerbot" data-size="large"
                data-auth-url="https://braix.online/index.php"
                data-request-access="write">
        </script>

        <!-- Developer Portfolio Button -->
        <a href="https://techyyfilip.vercel.app" target="_blank"
           class="mt-8 inline-block px-6 py-3 rounded-full bg-purple-800 text-white font-semibold hover:bg-purple-700 transition">
            Developer: StephanFilip
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
