<?php
// Set base URL dynamically
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/waterbill";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - WaterBill NG</title>
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="main-header">
    <div class="header-left">
        <img src="<?= $base_url ?>/assets/images/logo.png" alt="WaterBill NG Logo" class="logo">
        <h1 class="site-title"><?= htmlspecialchars($title) ?></h1>
    </div>
    <nav class="desktop-nav">
        <a href="<?= $base_url ?>/user/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="<?= $base_url ?>/user/payment.php"><i class="fas fa-credit-card"></i> Payments</a>
        <a href="<?= $base_url ?>/user/history.php"><i class="fas fa-history"></i> History</a>
        <a href="<?= $base_url ?>/user/report-fault.php"><i class="fas fa-exclamation-circle"></i> Report Fault</a>
        <a href="<?= $base_url ?>/user/profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="<?= $base_url ?>/user/notifications.php"><i class="fas fa-bell"></i> Notifications</a>
        <a href="<?= $base_url ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    <button class="hamburger" id="menu-btn" aria-label="Open Menu">
        <i class="fas fa-bars"></i>
    </button>
</header>

<!-- Sidebar for mobile -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?= $base_url ?>/assets/images/logo.png" alt="WaterBill NG Logo" class="sidebar-logo">
        <button class="close-btn" id="close-sidebar"><i class="fas fa-times"></i></button>
    </div>
    <a href="<?= $base_url ?>/user/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="<?= $base_url ?>/user/payment.php"><i class="fas fa-credit-card"></i> Payments</a>
    <a href="<?= $base_url ?>/user/history.php"><i class="fas fa-history"></i> History</a>
    <a href="<?= $base_url ?>/user/report-fault.php"><i class="fas fa-exclamation-circle"></i> Report Fault</a>
    <a href="<?= $base_url ?>/user/profile.php"><i class="fas fa-user"></i> Profile</a>
    <a href="<?= $base_url ?>/user/notifications.php"><i class="fas fa-bell"></i> Notifications</a>
    <a href="<?= $base_url ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="overlay" id="overlay"></div>


<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <a href="<?= $base_url ?>/user/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="<?= $base_url ?>/user/payment.php"><i class="fas fa-credit-card"></i> Payments</a>
    <a href="<?= $base_url ?>/user/history.php"><i class="fas fa-history"></i> History</a>
    <a href="<?= $base_url ?>/user/report-fault.php"><i class="fas fa-exclamation-circle"></i> Report Fault</a>
    <a href="<?= $base_url ?>/user/profile.php"><i class="fas fa-user"></i> Profile</a>
    <a href="<?= $base_url ?>/user/notifications.php"><i class="fas fa-bell"></i> Notifications</a>
    <a href="<?= $base_url ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="overlay" id="overlay"></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('menu-btn');
    const closeBtn = document.getElementById('close-sidebar');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    function toggleSidebar(show) {
        sidebar.classList.toggle('open', show);
        overlay.classList.toggle('show', show);
    }

    menuBtn.addEventListener('click', () => toggleSidebar(true));
    closeBtn.addEventListener('click', () => toggleSidebar(false));
    overlay.addEventListener('click', () => toggleSidebar(false));
});
</script>

<div class="container">