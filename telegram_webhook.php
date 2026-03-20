<?php
// telegram_webhook.php
// This file receives updates from Telegram and processes them.

// Include your main configuration file
require_once 'includes/config.php';

// Get the raw POST data from Telegram
$update = json_decode(file_get_contents('php://input'), true);

// Log the incoming update for debugging (optional, but highly recommended)
file_put_contents('telegram_webhook_log.txt', date('[Y-m-d H:i:s]') . " Incoming Update:\n" . print_r($update, true) . "\n\n", FILE_APPEND);

// Check if it's a message update
if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $from_id = $message['from']['id'] ?? null;
    $username = $message['from']['username'] ?? $message['from']['first_name'] ?? 'Guest';

    // Example: Respond to a simple /start command
    if ($text === '/start') {
        $response_text = "Hello " . htmlspecialchars($username) . "! Welcome to LearnX. Please use the web application for full features: https://earntolearn.ojt2025.com/";
        sendTelegramMessage($chat_id, $response_text);
    }

    // Example: Process a payment confirmation message (simplified)
    // This is a very basic example. In a real scenario, you'd need more robust parsing
    // and potentially a way for the user to link their Telegram chat_id to a transaction.
    if (strpos(strtolower($text), 'payment ref:') !== false) {
        $payment_ref = trim(str_ireplace('payment ref:', '', $text));
        if (!empty($payment_ref)) {
            // Here, you would ideally:
            // 1. Look up pending transactions in your database by $payment_ref.
            // 2. Verify the user (e.g., by matching $from_id to a user's telegram_id).
            // 3. Update the transaction status to 'awaiting_admin_review' or similar.
            // 4. Notify the admin (e.g., via a Telegram message to ADMIN_TELEGRAM_ID).

            // For now, just send a confirmation back to the user
            sendTelegramMessage($chat_id, "Received your payment reference: " . htmlspecialchars($payment_ref) . ". Your payment will be reviewed by an admin shortly.");

            // Optional: Notify admin (replace ADMIN_TELEGRAM_ID with your actual admin's Telegram ID)
            $admin_notification = "New payment reference received from @" . htmlspecialchars($username) . " (ID: " . $from_id . "): " . htmlspecialchars($payment_ref) . ". Please check the admin panel.";
            sendTelegramMessage(ADMIN_TELEGRAM_ID, $admin_notification);
        } else {
            sendTelegramMessage($chat_id, "Please provide a valid payment reference after 'Payment Ref:'.");
        }
    }

    // You can add more logic here to handle different types of messages or commands
    // For instance, if you want to allow users to check points via Telegram, etc.

} elseif (isset($update['callback_query'])) {
    // Handle inline keyboard button presses (if you implement them)
    $callback_query = $update['callback_query'];
    $chat_id = $callback_query['message']['chat']['id'];
    $data = $callback_query['data']; // Data sent with the button

    // Answer the callback query to remove the loading state on the button
    sendTelegramApiRequest('answerCallbackQuery', ['callback_query_id' => $callback_query['id']]);

    // Process the callback data
    // Example: if ($data === 'show_points') { ... }
}

// Function to send a message back to Telegram
function sendTelegramMessage($chat_id, $text) {
    $parameters = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML' // Or MarkdownV2, depending on your needs
    ];
    sendTelegramApiRequest('sendMessage', $parameters);
}

// Generic function to send requests to Telegram Bot API
function sendTelegramApiRequest($method, $parameters = []) {
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/' . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    // Log Telegram API response for debugging
    file_put_contents('telegram_webhook_log.txt', date('[Y-m-d H:i:s]') . " Telegram API Response for method {$method}:\n" . print_r(json_decode($response, true), true) . "\nError: {$error}\n\n", FILE_APPEND);

    return json_decode($response, true);
}

// Telegram requires a 200 OK response to confirm successful receipt of the update.
http_response_code(200);

?>
