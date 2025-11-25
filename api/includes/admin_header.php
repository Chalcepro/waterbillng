<?php
// Set base URL dynamically
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/waterbill";

// Set session cookie parameters for consistency
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 3600 * 24 * 30, // 30 days
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) session_start();
$title = isset($title) ? $title : 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> - WaterBill NG Admin</title>
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { font-family: Inter, Arial, sans-serif; background:#f6fafd; color:#222; }
    .admin-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, #2e6fff, #1a2746);
      color: #fff;
      padding: 0 16px;
      height: 56px;
      position: sticky;
      top: 0;
      z-index: 1001;
      box-shadow: 0 2px 12px rgba(46,111,255,0.07);
    }
    .header-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .logo {
      height: 34px;
      width: auto;
    }
    .admin-title {
      font-size: 1.1rem;
      font-weight: 700;
      letter-spacing: 1px;
    }
    .hamburger {
      background: none;
      border: none;
      color: #fff;
      font-size: 2rem;
      cursor: pointer;
      padding: 8px;
      display: flex;
      align-items: center;
    }
    /* Sidebar (mobile nav) */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: 220px;
      height: 100vh;
      background: #fff;
      box-shadow: 2px 0 16px rgba(46,111,255,0.09);
      z-index: 1200;
      transform: translateX(-100%);
      transition: transform 0.3s cubic-bezier(.4,0,.2,1);
      display: flex;
      flex-direction: column;
      padding-top: 56px;
    }
    .sidebar.open {
      transform: translateX(0);
    }
    .sidebar a {
      padding: 16px 22px;
      color: #1a2746;
      text-decoration: none;
      font-weight: 600;
      font-size: 1.08rem;
      border-bottom: 1px solid #f0f0f0;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: background 0.18s;
    }
    .sidebar a:hover {
      background: #f6fafd;
      color: #2e6fff;
    }
    .sidebar-header {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      background: #2e6fff;
      color: #fff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 16px;
      z-index: 1;
    }
    .sidebar-logo {
      height: 28px;
    }
    .close-btn {
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #fff;
      cursor: pointer;
    }
    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0, 0, 0, 0.5);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s;
      z-index: 1199;
    }
    .overlay.show {
      opacity: 1;
      pointer-events: auto;
    }
    @media (min-width: 900px) {
      .admin-header { max-width: 420px; margin: 0 auto; border-radius: 0 0 18px 18px; }
      /* Sidebar and overlay are now always visible, but hidden by default unless toggled */
      .sidebar { display: flex !important; }
      .overlay { display: block !important; }
    }
  </style>
</head>
<body>
  <header class="admin-header">
    <div class="header-left">
      <img src="<?= $base_url ?>/assets/images/logo.png" alt="WaterBill NG Logo" class="logo">
      <span class="admin-title">Admin</span>
    </div>
    <button class="hamburger" id="menu-btn" aria-label="Open Menu">
      <i class="fas fa-bars"></i>
    </button>
  </header>
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <img src="<?= $base_url ?>/assets/images/logo.png" alt="WaterBill NG Logo" class="sidebar-logo">
      <button class="close-btn" id="close-sidebar"><i class="fas fa-times"></i></button>
    </div>
    <a href="<?= $base_url ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="<?= $base_url ?>/admin/users.php"><i class="fas fa-users"></i> Users</a>
    <a href="<?= $base_url ?>/admin/payments.php"><i class="fas fa-credit-card"></i> Payments</a>
    <a href="<?= $base_url ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
    <a href="<?= $base_url ?>/admin/notifications.php"><i class="fas fa-bell"></i> Notifications</a>
    <a href="<?= $base_url ?>/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
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
  </div>
</body>
</html>