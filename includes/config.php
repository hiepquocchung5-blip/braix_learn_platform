<?php
// includes/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
// define('DB_SERVER', 'localhost'); // Your database server
// define('DB_USERNAME', 'pmwengs_earn_to_learn');     // Your database username
// define('DB_PASSWORD', 'superuser');         // Your database password
// define('DB_NAME', 'pmwengs_earn_to_learn'); // Your database name

define('DB_SERVER', 'localhost'); // Your database server
define('DB_USERNAME', 'zmmlpszw_filip');     // Your database username
define('DB_PASSWORD', '@fekgygn85cCM43');         // Your database password
define('DB_NAME', 'zmmlpszw_braix');

// Telegram Bot API configuration (for login webhook, if implemented)
// You need to create a bot via BotFather on Telegram to get these.
define('TELEGRAM_BOT_TOKEN', '8504182745:AAHlKhowBTEdOvzUT7oRN3dhVbAFLLErOcg'); // Updated with your actual bot token
define('TELEGRAM_WEBHOOK_URL', 'https://braix.online/telegram_webhook.php'); 

// KBZ Pay QR Image (Placeholder)
// This is a placeholder. In a real app, you'd generate this dynamically or use a service.
define('KBZ_PAY_QR_IMAGE', 'assets/img/kbz_pay_qr.png'); // Path to your placeholder QR image

// Admin Telegram ID (for manual payment verification, etc.)
// Replace with the Telegram ID of your admin user. This ID should also be in the 'users' table.
define('ADMIN_TELEGRAM_ID', '8125603481');

// Establish database connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
session_start();

// Function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to get current user data
// Reverted function name to get_app_current_user
if (!function_exists('get_app_current_user')) {
    function get_app_current_user($conn) {
        if (is_logged_in()) {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user;
        }
        return null;
    }
}

// Function to check if current user is admin
function is_admin($conn) {
    // Calling the reverted function name
    $user = get_app_current_user($conn); 
    return $user && $user['telegram_id'] == ADMIN_TELEGRAM_ID;
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to update user points
function update_user_points($conn, $user_id, $amount, $type) {
    $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $stmt->bind_param("ii", $amount, $user_id);
    $stmt->execute();
    $stmt->close();

    // Log the transaction
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, status, payment_method) VALUES (?, ?, ?, 'completed', 'system')");
    $stmt->bind_param("isi", $user_id, $type, $amount);
    $stmt->execute();
    $stmt->close();
}

// Function to get an admin setting
function get_admin_setting($conn, $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $setting = $result->fetch_assoc();
    $stmt->close();
    return $setting ? $setting['setting_value'] : null;
}

// Function to update an admin setting
function update_admin_setting($conn, $key, $value) {
    $stmt = $conn->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    $stmt->execute();
    $stmt->close();
}

?>
