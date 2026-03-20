<?php
// community.php
require_once 'includes/config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$user = get_app_current_user($conn);

// Get Discord links from admin settings
$discord_invite_link = get_admin_setting($conn, 'discord_invite_link');
$premium_discord_invite_link = get_admin_setting($conn, 'premium_discord_invite_link');

include 'includes/header.php';
?>

<div class="container py-8">
    <h1 class="text-4xl font-bold mb-8 text-center text-purple-300">Join Our Community!</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Public Discord Group -->
        <div class="form-card p-8 rounded-xl shadow-lg text-center">
            <h2 class="text-3xl font-semibold mb-4 text-purple-200">General Study Group</h2>
            <p class="text-lg text-purple-100 mb-6">Connect with other learners, ask questions, and share resources in our public Discord server.</p>
            <?php if ($discord_invite_link): ?>
                <a href="<?php echo htmlspecialchars($discord_invite_link); ?>" target="_blank" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block">
                    Join Discord Server
                    <svg class="inline-block ml-2 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            <?php else: ?>
                <p class="text-red-300">Discord invite link not set by admin yet.</p>
            <?php endif; ?>
        </div>

        <!-- Premium Discord Group -->
        <div class="form-card p-8 rounded-xl shadow-lg text-center">
            <h2 class="text-3xl font-semibold mb-4 text-purple-200">Exclusive Premium Group</h2>
            <p class="text-lg text-purple-100 mb-6">Access a private Discord channel for premium users, featuring direct mentor support and exclusive voice chats.</p>
            <?php if ($user['is_premium']): ?>
                <?php if ($premium_discord_invite_link): ?>
                    <a href="<?php echo htmlspecialchars($premium_discord_invite_link); ?>" target="_blank" class="transparent-button px-6 py-3 rounded-full text-lg font-medium text-white hover:text-purple-200 inline-block">
                        Join Premium Discord
                        <svg class="inline-block ml-2 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                    <div class="mt-6">
                        <h3 class="text-xl font-semibold text-purple-200 mb-2">Voice Chat Idea:</h3>
                        <p class="text-sm text-gray-300">
                            For live voice discussions or coding sessions, consider using Discord's voice channels directly. 
                            You can schedule sessions and share the voice channel link within the Discord server.
                            (This would typically be managed within Discord itself, not directly linked from here unless you have a Discord bot to generate temporary voice links).
                        </p>
                    </div>
                <?php else: ?>
                    <p class="text-red-300">Premium Discord invite link not set by admin yet.</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="bg-red-900 bg-opacity-50 border border-red-700 text-red-200 p-4 rounded-lg mb-6">
                    <p class="font-bold">Premium Access Required!</p>
                    <p>Upgrade to premium to join this exclusive community.</p>
                    <a href="buy_points.php" class="transparent-button px-4 py-2 rounded-full text-sm font-medium text-white hover:text-red-200 inline-block mt-4">
                        Get Premium
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
