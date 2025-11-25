/**
 * Authentication related functions
 */

/**
 * Logout function - Handles user logout
 * Clears authentication data and redirects to login page
 */
function logout() {
    console.log('Logout initiated');
    
    // Send logout request to server
    fetch('../../api/auth/logout.php', { 
        method: 'POST', 
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Logout response status:', response.status);
        
        // Clear client-side data regardless of server response
        clearAuthData();
        
        // Redirect to login page
        window.location.href = '../../frontend/auth/login.html';
        
        return response.json();
    })
    .catch(error => {
        console.error('Logout error:', error);
        // Still redirect even if there's an error
        clearAuthData();
        window.location.href = '../../frontend/auth/login.html';
    });
}

/**
 * Clear authentication data from client-side storage
 */
function clearAuthData() {
    // Clear localStorage
    if (typeof localStorage !== 'undefined') {
        localStorage.removeItem('authToken');
        localStorage.removeItem('userData');
    }
    
    // Clear session cookies
    document.cookie = 'PHPSESSID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
}

// Make logout function globally available
if (typeof window.logout !== 'function') {
    window.logout = logout;
}
