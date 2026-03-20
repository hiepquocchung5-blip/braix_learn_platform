<?php
// includes/header.php
require_once 'config.php';
$current_user = get_app_current_user($conn); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earn to Learn Programming</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS for space theme and transparent buttons -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); /* Deep space gradient */
            color: #e0e7ff; /* Light bluish-purple for text */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }
        .transparent-button {
            background: rgba(255, 255, 255, 0.1); /* Slightly transparent white */
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(5px); /* Glassmorphism effect */
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .transparent-button:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        /* Mobile nav specific styles */
        @media (max-width: 767px) {
            .mobile-nav-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                background: rgba(0, 0, 0, 0.7); /* Darker transparent background for footer */
                backdrop-filter: blur(10px);
                z-index: 1000;
                padding: 0.5rem 0;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <header class="bg-gray-900 bg-opacity-70 backdrop-filter backdrop-blur-lg shadow-lg py-4 px-6 fixed top-0 left-0 w-full z-50">
        <div class="container flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center space-x-3">
                <img src="https://placehold.co/40x40/6a0dad/ffffff?text=Logo" alt="Logo" class="rounded-full">
                <a href="dashboard.php" class="text-2xl font-bold text-purple-300 hover:text-purple-400 transition-colors">Braix</a>
            </div>

            <!-- User Info and Points -->
            <nav class="hidden md:flex items-center space-x-6">
                <?php if (is_logged_in()): ?>
                    <span class="text-lg text-purple-200">Hello, <span class="font-semibold"><?php echo htmlspecialchars($current_user['username']); ?></span>!</span>
                    <span class="text-lg text-yellow-300">Points: <span class="font-bold"><?php echo $current_user['points']; ?></span></span>
                    <a href="dashboard.php" class="text-purple-200 hover:text-white transition-colors">Dashboard</a>
                    <a href="redeem_gifts.php" class="text-purple-200 hover:text-white transition-colors">Gifts</a>
                    <a href="quizzes.php" class="text-purple-200 hover:text-white transition-colors">Quizzes</a>
                    <a href="challenges.php" class="text-purple-200 hover:text-white transition-colors">Challenges</a>
                    <a href="leaderboard.php" class="text-purple-200 hover:text-white transition-colors">Leaderboard</a>
                    <a href="community.php" class="text-purple-200 hover:text-white transition-colors">Community</a>
                    <a href="profile.php" class="text-purple-200 hover:text-white transition-colors">Profile</a>
                    <?php if (is_admin($conn)): ?>
                        <a href="admin/dashboard.php" class="text-red-300 hover:text-red-400 transition-colors font-bold">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="transparent-button px-4 py-2 rounded-full text-sm font-medium text-white hover:text-purple-200">Logout</a>
                <?php else: ?>
                    <a href="index.php" class="transparent-button px-4 py-2 rounded-full text-sm font-medium text-white hover:text-purple-200">Login with Telegram</a>
                <?php endif; ?>
            </nav>

            <!-- Mobile Menu Button (Hamburger) -->
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-purple-200 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu (Hidden by default, toggled by JS) -->
        <div id="mobile-menu" class="hidden md:hidden bg-gray-800 bg-opacity-90 backdrop-filter backdrop-blur-lg mt-4 rounded-lg shadow-xl py-2">
            <nav class="flex flex-col items-center space-y-3">
                <?php if (is_logged_in()): ?>
                    <span class="text-lg text-purple-200">Hello, <span class="font-semibold"><?php echo htmlspecialchars($current_user['username']); ?></span>!</span>
                    <span class="text-lg text-yellow-300">Points: <span class="font-bold"><?php echo $current_user['points']; ?></span></span>
                    <a href="dashboard.php" class="block py-2 px-4 text-purple-200 hover:text-white transition-colors w-full text-center">Dashboard</a>
                    <a href="redeem_gifts.php" class="block py-2 px-4 text-purple-200 hover:text-white transition-colors w-full text-center">Gifts</a>
                    <a href="quizzes.php" class="block py-2 px-4 text-purple-200 hover:text-white transition-colors w-full text-center">Quizzes</a>
                    <a href="challenges.php" class="block py-2 px-4 text-purple-200 hover:text-white transition-colors w-full text-center">Challenges</a>
                    <a href="leaderboard.php" class="block py-2 px-4 text-purple-200 hover:text-white transition-colors w-full text-center">Leaderboard</a>
                    <a href="community.php" class="block py-2 px-4 text-purple-200 hover:text-white transition-colors w-full text-center">Community</a>
                    <a href="profile.php" class="block py-2 px-4 text-purple-200 hover:text-white transition-colors w-full text-center">Profile</a>
                    <?php if (is_admin($conn)): ?>
                        <a href="admin/dashboard.php" class="block py-2 px-4 text-red-300 hover:text-red-400 transition-colors font-bold w-full text-center">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="transparent-button px-4 py-2 rounded-full text-sm font-medium text-white hover:text-purple-200 w-auto">Logout</a>
                <?php else: ?>
                    <a href="index.php" class="transparent-button px-4 py-2 rounded-full text-sm font-medium text-white hover:text-purple-200 w-auto">Login with Telegram</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="flex-grow pt-24 pb-20 px-4"> <!-- Added padding-top to account for fixed header and padding-bottom for fixed footer -->
