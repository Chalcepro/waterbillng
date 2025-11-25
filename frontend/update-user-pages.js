const fs = require('fs');
const path = require('path');

const userPagesDir = path.join(__dirname, 'frontend', 'user');
const bottomNavHtml = `
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="dashboard.html" class="nav-link">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="payment.html" class="nav-link">
            <i class="fas fa-credit-card"></i>
            <span>Pay</span>
        </a>
        <a href="history.html" class="nav-link">
            <i class="fas fa-history"></i>
            <span>History</span>
        </a>
        <a href="report-fault.html" class="nav-link">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Report</span>
        </a>
        <a href="profile.html" class="nav-link">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>
`;

// Get all HTML files in the user directory
fs.readdir(userPagesDir, (err, files) => {
    if (err) {
        console.error('Error reading user pages directory:', err);
        return;
    }

    const htmlFiles = files.filter(file => file.endsWith('.html') && file !== 'login.html' && file !== 'register.html');
    
    htmlFiles.forEach(file => {
        const filePath = path.join(userPagesDir, file);
        let content = fs.readFileSync(filePath, 'utf8');
        
        // Add bottom-nav.css if not already present
        if (!content.includes('bottom-nav.css')) {
            content = content.replace(
                '<link rel="stylesheet" href="../assets/css/main.css">',
                '<link rel="stylesheet" href="../assets/css/main.css">\n    <link rel="stylesheet" href="../assets/css/bottom-nav.css">'
            );
        }
        
        // Add bottom navigation before the closing body tag
        if (!content.includes('class="bottom-nav"')) {
            content = content.replace(
                /<\/body>/, 
                `${bottomNavHtml}\n    <script src="../assets/js/notify.js"></script>\n    <script src="../assets/js/auth-guard.js"></script>\n    <script src="../assets/js/bottom-nav.js"></script>\n</body>`
            );
            
            fs.writeFileSync(filePath, content, 'utf8');
            console.log(`Updated ${file} with bottom navigation`);
        } else {
            console.log(`${file} already has bottom navigation`);
        }
    });
    
    console.log('\nUpdate complete!');
});
