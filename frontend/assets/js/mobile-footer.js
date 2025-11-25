(function(){
  if (window.WBMobileFooter) return; // singleton
  function injectStyles(){
    if (document.getElementById('wb-mobile-footer-style')) return;
    const css = `
    .wb-bottom-nav {
      position: fixed;
      left: 0;
      right: 0;
      bottom: 0;
      background: #2c3e50;
      display: flex;
      justify-content: space-around;
      align-items: center;
      padding: 8px 0 calc(8px + env(safe-area-inset-bottom));
      z-index: 1000;
      border-top: 1px solid rgba(255,255,255,0.1);
    }
    .wb-bottom-nav .nav-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      color: rgba(255,255,255,0.7);
      text-decoration: none;
      font-size: 11px;
      padding: 4px 8px;
      border-radius: 4px;
      transition: all 0.2s;
    }
    .wb-bottom-nav .nav-item i {
      font-size: 18px;
      margin-bottom: 4px;
    }
    .wb-bottom-nav .nav-item.active {
      color: #fff;
      background: rgba(255,255,255,0.1);
    }
    .wb-bottom-nav .nav-item:hover {
      color: #fff;
    }
    @media (min-width: 768px) {
      .wb-bottom-nav {
        display: none;
      }
    }`;
    const s = document.createElement('style');
    s.id = 'wb-mobile-footer-style';
    s.textContent = css;
    document.head.appendChild(s);
  }
  function createNav(){
    let nav = document.querySelector('.wb-bottom-nav');
    if (nav) return nav;
    
    nav = document.createElement('nav');
    nav.className = 'wb-bottom-nav';
    const base = location.pathname.replace(/[^\/]+$/, '');
    const currentPage = location.pathname.split('/').pop() || 'dashboard.html';
    
    // Only show on mobile devices
    if (window.innerWidth >= 768) return null;
    
    nav.innerHTML = `
      <a href="${base}dashboard.html" class="nav-item ${currentPage === 'dashboard.html' ? 'active' : ''}" title="Dashboard">
        <i class="fas fa-tachometer-alt"></i>
        <span>Dashboard</span>
      </a>
      <a href="${base}payment.html" class="nav-item ${currentPage === 'payment.html' ? 'active' : ''}" title="Payments">
        <i class="fas fa-credit-card"></i>
        <span>Pay</span>
      </a>
      <a href="${base}history.html" class="nav-item ${currentPage === 'history.html' ? 'active' : ''}" title="History">
        <i class="fas fa-history"></i>
        <span>History</span>
      </a>
      <a href="${base}profile.html" class="nav-item ${currentPage === 'profile.html' ? 'active' : ''}" title="Profile">
        <i class="fas fa-user"></i>
        <span>Profile</span>
      </a>
    `;
    document.body.appendChild(nav);
    const logout = nav.querySelector('[data-logout]');
    if (logout) logout.addEventListener('click', async (e)=>{
      e.preventDefault();
      try{await fetch('../../api/auth/logout.php',{method:'POST',credentials:'include'});}catch(_){ }
      location.href = '../auth/login.html';
    });
    return nav;
  }
  function applyCompact(nav) {
    // Simplified - no longer needed with the new design
    if (!nav) return;
    
    // Update active state based on current page
    const currentPage = location.pathname.split('/').pop() || 'dashboard.html';
    nav.querySelectorAll('.nav-item').forEach(item => {
      const href = item.getAttribute('href') || '';
      if (href.endsWith(currentPage)) {
        item.classList.add('active');
      } else {
        item.classList.remove('active');
      }
    });
  }
  function ensurePadding(){
    const pad = '60px'; // Reduced padding for the simpler footer
    const target = document.querySelector('.dashboard-content') || document.body;
    if (target) {
      target.style.paddingBottom = pad;
    }
  }
  function viewportFix(nav){
    const ensureVisible = ()=>{
      try{
        const rect = nav.getBoundingClientRect();
        const h = (window.visualViewport && window.visualViewport.height) || window.innerHeight;
        applyCompact(nav);
        // If the bar is not visible or partly beyond the bottom, pull it up
        if (rect.bottom > h || rect.top > h) {
          nav.style.bottom = '16px';
        }
        // If some global CSS hides navs, force display back
        const comp = getComputedStyle(nav);
        if (comp.display === 'none'){ nav.style.display = 'flex'; }
      }catch(_){ }
    };
    ensureVisible();
    window.addEventListener('resize', ensureVisible, { passive: true });
    window.addEventListener('scroll', ensureVisible, { passive: true });
    if (window.visualViewport){
      window.visualViewport.addEventListener('resize', ensureVisible, { passive: true });
      window.visualViewport.addEventListener('scroll', ensureVisible, { passive: true });
    }
    // Run a couple of delayed checks to catch late layout shifts
    setTimeout(ensureVisible, 100);
    setTimeout(ensureVisible, 400);
  }
  function init(){
    injectStyles();
    const nav = createNav();
    ensurePadding();
    viewportFix(nav);
  }
  if (document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  }else{ init(); }
  window.WBMobileFooter = true;
})();
