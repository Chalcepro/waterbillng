<?php global $base_url; ?>
</div> <!-- End container -->
    <footer id="main-footer" class="footer-nav">
        <div class="footer-nav">
            <a href="<?= $base_url ?>/user/dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="<?= $base_url ?>/user/history.php"><i class="fas fa-history"></i> History</a>
            <a href="<?= $base_url ?>/user/payment.php"><i class="fas fa-credit-card"></i> Pay</a>
            <a href="<?= $base_url ?>/user/report-fault.php"><i class="fas fa-exclamation-triangle"></i> Report</a>
            <a href="<?= $base_url ?>/user/profile.php"><i class="fas fa-user"></i> Profile</a>
        </div>
        <button id="footer-hide-btn" style="position:absolute;top:-32px;right:12px;background:#2e6fff;color:#fff;border:none;border-radius:16px;padding:4px 14px;font-size:1.1rem;cursor:pointer;z-index:1001;">Hide</button>
        <div class="copyright">
            &copy; <?= date('Y') ?> WaterBill NG. All rights reserved.
        </div>
    </footer>
    <div id="footer-bubble" title="Toggle Footer" style="position:fixed;left:16px;top:50vh;transform:translateY(-50%);z-index:2000;width:54px;height:54px;background:#2e6fff;border-radius:50%;box-shadow:0 2px 12px rgba(46,111,255,0.18);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:box-shadow 0.2s;"><span style="color:#fff;font-size:2rem;line-height:1;">&#9776;</span></div>
    <script src="<?= $base_url ?>/assets/js/main.js"></script>
    <script>
    (function(){
      const footer = document.getElementById('main-footer');
      const hideBtn = document.getElementById('footer-hide-btn');
      const bubble = document.getElementById('footer-bubble');
      let isDragging = false, offsetY = 0, startY = 0;
      if (hideBtn && bubble && footer) {
        hideBtn.onclick = function() {
          footer.style.display = 'none';
        };
        bubble.onclick = function(e) {
          if (!isDragging) {
            if (footer.style.display === 'none') {
              footer.style.display = '';
            } else {
              footer.style.display = 'none';
            }
          }
        };
        // Drag logic
        bubble.addEventListener('mousedown', function(e) {
          isDragging = false;
          startY = e.clientY;
          offsetY = bubble.getBoundingClientRect().top - window.scrollY;
          function onMove(ev) {
            isDragging = true;
            let newY = ev.clientY - (bubble.offsetHeight/2);
            // Clamp to viewport
            newY = Math.max(8, Math.min(window.innerHeight - bubble.offsetHeight - 8, newY));
            bubble.style.top = newY + 'px';
            bubble.style.bottom = '';
            bubble.style.transform = '';
          }
          function onUp(ev) {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
            setTimeout(()=>{isDragging=false;},100);
          }
          document.addEventListener('mousemove', onMove);
          document.addEventListener('mouseup', onUp);
        });
      }
    })();
    </script>
</body>
</html>