// Shared layout loader for Admin pages
// Injects header and footer partials into #admin-header and #admin-footer containers

async function loadAdminLayout(options = {}) {
  const { active = '' } = options;
  try {
    // Ensure global notify utility is loaded
    if (!window.notify) {
      const existing = document.querySelector('script[data-wb="notify-js"]');
      if (!existing) {
        const s = document.createElement('script');
        s.src = '../assets/js/notify.js';
        s.defer = true;
        s.dataset.wb = 'notify-js';
        document.head.appendChild(s);
      }
    }
    // Ensure auth guard is available
    if (!window.AuthGuard) {
      const existingGuard = document.querySelector('script[data-wb="guard-js"]');
      if (!existingGuard) {
        const g = document.createElement('script');
        g.src = '../assets/js/auth-guard.js';
        g.defer = true;
        g.dataset.wb = 'guard-js';
        document.head.appendChild(g);
      }
    }
    // Header
    const headerRes = await fetch('../assets/partials/admin-header.html', { cache: 'no-store' });
    const headerHtml = await headerRes.text();
    const headerMount = document.getElementById('admin-header');
    if (headerMount) {
      headerMount.innerHTML = headerHtml;
    }

    // Footer
    const footerRes = await fetch('../assets/partials/admin-footer.html', { cache: 'no-store' });
    const footerHtml = await footerRes.text();
    const footerMount = document.getElementById('admin-footer');
    if (footerMount) {
      footerMount.innerHTML = footerHtml;
    }

    // Highlight active link in header nav
    if (active) {
      document.querySelectorAll('.header-nav .nav-link').forEach(a => {
        if (a.getAttribute('href') && a.getAttribute('href').includes(active)) {
          a.classList.add('active');
        } else {
          a.classList.remove('active');
        }
      });
    } else {
      // Auto-detect based on pathname
      const path = location.pathname.split('/').pop();
      document.querySelectorAll('.header-nav .nav-link').forEach(a => {
        if (a.getAttribute('href') && a.getAttribute('href').includes(path)) {
          a.classList.add('active');
        }
      });
    }
  } catch (e) {
    console.error('Failed to load admin layout:', e);
  }
}

// Expose globally
window.loadAdminLayout = loadAdminLayout;
