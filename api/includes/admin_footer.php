<?php global $base_url; ?>
</div> <!-- End container -->
<footer id="main-footer" class="footer-nav">
    <div class="footer-nav">
        <a href="<?= $base_url ?>/admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="<?= $base_url ?>/admin/payments.php"><i class="fas fa-credit-card"></i> Payments</a>
        <a href="<?= $base_url ?>/admin/users.php"><i class="fas fa-users"></i> Users</a>
        <a href="<?= $base_url ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="<?= $base_url ?>/admin/notifications.php"><i class="fas fa-bell"></i> Notifications</a>
        <a href="<?= $base_url ?>/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="<?= $base_url ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <button id="footer-hide-btn" style="position:absolute;top:-32px;right:12px;background:#2e6fff;color:#fff;border:none;border-radius:16px;padding:4px 14px;font-size:1.1rem;cursor:pointer;z-index:1001;">Hide</button>
    <div class="copyright">
        &copy; <?= date('Y') ?> WaterBill NG Admin. All rights reserved.
    </div>
</footer>
<script src="<?= $base_url ?>/assets/js/main.js"></script>
<script>
(function(){
  const footer = document.getElementById('main-footer');
  const hideBtn = document.getElementById('footer-hide-btn');
  if (hideBtn && footer) {
    hideBtn.onclick = function() {
      footer.style.display = 'none';
      setTimeout(function(){footer.style.display = '';}, 2000); // fallback: auto-show after 2s
    };
  }
})();
</script>
</body>
</html>
