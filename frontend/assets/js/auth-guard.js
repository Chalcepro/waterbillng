(function(){
  if (window.AuthGuard) return;

  const CHECK_URL = '../../api/auth/check-session.php';
  const fetchSession = async () => {
    try {
      const res = await fetch(CHECK_URL, { method: 'GET', credentials: 'include' });
      return await res.json();
    } catch (e) {
      if (window.notify) notify.error('Unable to verify session.');
      return { authenticated: false };
    }
  };

  async function requireAdmin(options={}) {
    const { redirectLogin = '../auth/login.html', redirectUser = '../user/dashboard.html' } = options;
    const data = await fetchSession();
    if (!data.authenticated) {
      if (window.notify) notify.warn('Please log in to continue');
      window.location.href = redirectLogin;
      return null;
    }
    if ((data.role || '').toLowerCase() !== 'admin') {
      if (window.notify) notify.warn('Admin access required');
      window.location.href = redirectUser;
      return null;
    }
    return data;
  }

  async function requireUser(options={}) {
    const { redirectLogin = '../auth/login.html', redirectAdmin = '../admin/dashboard.html' } = options;
    const data = await fetchSession();
    if (!data.authenticated) {
      if (window.notify) notify.warn('Please log in to continue');
      window.location.href = redirectLogin;
      return null;
    }
    // Allow any authenticated non-admin user (user, tenant, landlord, etc.)
    if ((data.role || '').toLowerCase() === 'admin') {
      if (window.notify) notify.info('Switching to admin dashboard');
      window.location.href = redirectAdmin;
      return null;
    }
    return data;
  }

  window.AuthGuard = { requireAdmin, requireUser };
})();
