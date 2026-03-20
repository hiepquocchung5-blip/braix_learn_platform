-- -- database.sql

-- -- Create Database
-- CREATE DATABASE IF NOT EXISTS `earn_to_learn`;
-- USE `earn_to_learn`;

-- Table for Users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `telegram_id` VARCHAR(255) UNIQUE NOT NULL COMMENT 'Telegram User ID for login',
    `username` VARCHAR(255) NOT NULL,
    `points` INT DEFAULT 0 COMMENT 'Points earned by the user',
    `is_premium` BOOLEAN DEFAULT FALSE COMMENT 'True if user has premium access',
    `premium_expires_at` DATETIME DEFAULT NULL COMMENT 'Timestamp when premium access expires (for temporary premium)',
    `last_login` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Last login for daily bonus',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for Programming Languages
CREATE TABLE IF NOT EXISTS `programming_languages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) UNIQUE NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for Lessons
CREATE TABLE IF NOT EXISTS `lessons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `language_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content_type` ENUM('text', 'drive_link') NOT NULL DEFAULT 'text' COMMENT 'Type of lesson content',
    `content` TEXT COMMENT 'Lesson text content or Google Drive link',
    `is_premium` BOOLEAN DEFAULT FALSE COMMENT 'True if this lesson requires premium access',
    `points_cost` INT DEFAULT 0 COMMENT 'Points required to unlock this lesson if not premium',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`language_id`) REFERENCES `programming_languages`(`id`) ON DELETE CASCADE
);

-- Table for Quizzes (NEW)
CREATE TABLE IF NOT EXISTS `quizzes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lesson_id` INT DEFAULT NULL COMMENT 'Quiz associated with a specific lesson',
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `points_per_correct_answer` INT DEFAULT 10,
    `pass_percentage` INT DEFAULT 70 COMMENT 'Percentage of correct answers needed to pass',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE SET NULL
);

-- Table for Quiz Questions (NEW)
CREATE TABLE IF NOT EXISTS `quiz_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quiz_id` INT NOT NULL,
    `question_text` TEXT NOT NULL,
    `question_type` ENUM('multiple_choice', 'fill_in_the_blank') NOT NULL DEFAULT 'multiple_choice',
    `correct_answer` TEXT NOT NULL COMMENT 'For MC: correct option letter (A,B,C,D); For Fill-in: the exact correct text',
    `option_a` VARCHAR(255) DEFAULT NULL,
    `option_b` VARCHAR(255) DEFAULT NULL,
    `option_c` VARCHAR(255) DEFAULT NULL,
    `option_d` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
);

-- Table for Quiz Attempts (NEW)
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `quiz_id` INT NOT NULL,
    `score` INT DEFAULT 0 COMMENT 'Number of correct answers',
    `total_questions` INT DEFAULT 0,
    `points_earned` INT DEFAULT 0,
    `passed` BOOLEAN DEFAULT FALSE,
    `attempt_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
);

-- Table for Coding Challenges (NEW)
CREATE TABLE IF NOT EXISTS `coding_challenges` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `language_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `expected_output` TEXT COMMENT 'For automated testing (optional)',
    `points_reward` INT DEFAULT 50,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`language_id`) REFERENCES `programming_languages`(`id`) ON DELETE CASCADE
);

-- Table for Challenge Submissions (NEW)
CREATE TABLE IF NOT EXISTS `challenge_submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `challenge_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `code_submission` TEXT NOT NULL COMMENT 'Submitted code or link to pastebin/gist',
    `submission_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `admin_notes` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `points_awarded` INT DEFAULT 0,
    FOREIGN KEY (`challenge_id`) REFERENCES `coding_challenges`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Table for User Lesson Progress (NEW)
CREATE TABLE IF NOT EXISTS `user_lesson_progress` (
    `user_id` INT NOT NULL,
    `lesson_id` INT NOT NULL,
    `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `lesson_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE
);

-- Table for Badges (NEW)
CREATE TABLE IF NOT EXISTS `badges` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) UNIQUE NOT NULL,
    `description` TEXT,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `criteria` TEXT COMMENT 'Description of how to earn the badge (e.g., "Complete all Python lessons")',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for User Badges (NEW)
CREATE TABLE IF NOT EXISTS `user_badges` (
    `user_id` INT NOT NULL,
    `badge_id` INT NOT NULL,
    `awarded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `badge_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`badge_id`) REFERENCES `badges`(`id`) ON DELETE CASCADE
);

-- Table for Gifts
CREATE TABLE IF NOT EXISTS `gifts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `points_cost` INT NOT NULL DEFAULT 0,
    `image_url` VARCHAR(255) DEFAULT NULL COMMENT 'URL to gift image',
    `stock` INT DEFAULT -1 COMMENT '-1 for unlimited, 0 for out of stock, >0 for limited stock',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for Transactions (for buying points/premium/redeeming gifts)
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('buy_points', 'buy_premium', 'earn_points', 'spend_points', 'redeem_gift', 'daily_bonus', 'quiz_reward', 'challenge_reward', 'system') NOT NULL,
    `amount` INT NOT NULL COMMENT 'Amount of points or money (e.g., in MMK for KBZ Pay)',
    `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `payment_method` VARCHAR(50) DEFAULT NULL COMMENT 'e.g., KBZ Pay, Points, System',
    `qr_image_url` VARCHAR(255) DEFAULT NULL COMMENT 'URL to the KBZ Pay QR image for manual verification',
    `transaction_ref` VARCHAR(255) UNIQUE DEFAULT NULL COMMENT 'Reference ID from payment gateway or user input',
    `gift_id` INT DEFAULT NULL COMMENT 'References gift if type is redeem_gift',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`gift_id`) REFERENCES `gifts`(`id`) ON DELETE SET NULL
);

-- Table for AI Prompts (text only, no AI integration)
CREATE TABLE IF NOT EXISTS `ai_prompts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ai_name` VARCHAR(255) NOT NULL COMMENT 'Name of the AI (e.g., "ChatGPT", "Gemini")',
    `language_id` INT DEFAULT NULL COMMENT 'Optional: Link to a specific programming language',
    `prompt_text` TEXT NOT NULL COMMENT 'The prompt text to be used',
    `points_cost` INT DEFAULT 0 COMMENT 'Points required to unlock this prompt',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`language_id`) REFERENCES `programming_languages`(`id`) ON DELETE SET NULL
);

-- Table for User Unlocked Prompts (NEW)
CREATE TABLE IF NOT EXISTS `user_unlocked_prompts` (
    `user_id` INT NOT NULL,
    `prompt_id` INT NOT NULL,
    `unlocked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `prompt_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`prompt_id`) REFERENCES `ai_prompts`(`id`) ON DELETE CASCADE
);

-- Table for Profile Items (NEW)
CREATE TABLE IF NOT EXISTS `profile_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `type` ENUM('avatar', 'banner', 'theme') NOT NULL,
    `value` TEXT NOT NULL COMMENT 'URL for avatar/banner, CSS class/JSON for theme',
    `points_cost` INT DEFAULT 0,
    `is_premium_exclusive` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for User Profile Items (NEW)
CREATE TABLE IF NOT EXISTS `user_profile_items` (
    `user_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `equipped` BOOLEAN DEFAULT FALSE COMMENT 'True if the item is currently active/equipped',
    `purchased_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `item_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `profile_items`(`id`) ON DELETE CASCADE
);

-- Table for Admin Settings (e.g., ad URLs, Discord links)
CREATE TABLE IF NOT EXISTS `admin_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(255) UNIQUE NOT NULL,
    `setting_value` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

