<?php
// admin/index.php
require_once '../includes/config.php';

// If user is not logged in, redirect to main index
if (!is_logged_in()) {
    redirect('../index.php');
}

// If user is logged in but not admin, redirect to dashboard
if (!is_admin($conn)) {
    redirect('../dashboard.php');
}

// If admin, redirect to admin dashboard
redirect('dashboard.php');
?>
