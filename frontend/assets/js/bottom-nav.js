document.addEventListener('DOMContentLoaded', async function() {
    // Get user data from session
    try {
        const response = await fetch('../../api/auth/check-session.php', {
            credentials: 'include'
        });
        
        if (response.ok) {
            const data = await response.json();
            if (!data.loggedIn || !data.user) {
                // Redirect to login if not logged in
                window.location.href = '../auth/login.html';
            }
        } else {
            window.location.href = '../auth/login.html';
        }
    } catch (error) {
        console.error('Session check error:', error);
        window.location.href = '../auth/login.html';
    }

    // Update active link based on current page
    const currentPage = window.location.pathname.split('/').pop() || 'dashboard.html';
    const navLinks = document.querySelectorAll('.bottom-nav .nav-link');
    
    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref === currentPage || 
            (currentPage === '' && linkHref === 'dashboard.html') ||
            (currentPage === 'index.html' && linkHref === 'dashboard.html')) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
});
