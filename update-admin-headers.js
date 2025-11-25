const fs = require('fs');
const path = require('path');

// Admin pages to update
const adminPages = [
    'dashboard.html',
    'fault-reports.html',
    'manual-payments.html',
    'notifications.html',
    'payments.html',
    'reports.html',
    'settings.html',
    'users.html'
];

// New header HTML
const newHeaderHTML = `
    <!-- Responsive Admin Header -->
    <div id="admin-header">
        <!-- Header will be loaded here -->
    </div>`;

// New header initialization script
const headerScript = `
    <!-- Load responsive admin header -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load responsive admin header
        fetch('/waterbill/frontend/includes/admin-header-responsive.html')
            .then(response => response.text())
            .then(html => {
                const header = document.getElementById('admin-header');
                if (header) {
                    header.innerHTML = html;
                    
                    // Highlight current menu item
                    const currentPage = window.location.pathname.split('/').pop() || 'dashboard.html';
                    document.querySelectorAll('.nav-link').forEach(link => {
                        const href = link.getAttribute('href');
                        if (href === currentPage || 
                            (currentPage.includes(href.replace('.html', '')) && href !== '#')) {
                            link.classList.add('active');
                        }
                    });
                }
            })
            .catch(error => console.error('Error loading header:', error));
    });
    </script>`;

// Function to update a single file
async function updateFile(filePath) {
    try {
        // Create backup
        const backupPath = `${filePath}.bak`;
        if (!fs.existsSync(backupPath)) {
            fs.copyFileSync(filePath, backupPath);
            console.log(`Created backup: ${backupPath}`);
        }

        // Read file content
        let content = fs.readFileSync(filePath, 'utf8');
        
        // Update header section
        content = content.replace(
            /<div id="admin-header">[\s\S]*?<\/div>/,
            newHeaderHTML
        );
        
        // Add or update header initialization script
        if (content.includes('// Load responsive admin header')) {
            // Update existing script
            content = content.replace(
                /<script>[\s\S]*?\/\/ Load responsive admin header[\s\S]*?<\/script>/,
                headerScript
            );
        } else {
            // Add new script before the closing body tag
            content = content.replace(
                /<\/body>/,
                `${headerScript}\n    </body>`
            );
        }
        
        // Save updated content
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Updated: ${filePath}`);
        
    } catch (error) {
        console.error(`Error updating ${filePath}:`, error.message);
    }
}

// Process all admin pages
const adminDir = path.join(__dirname, 'frontend', 'admin');
adminPages.forEach(page => {
    const filePath = path.join(adminDir, page);
    if (fs.existsSync(filePath)) {
        updateFile(filePath);
    } else {
        console.log(`File not found: ${filePath}`);
    }
});

console.log('\nUpdate complete!');
console.log('Please test all admin pages to ensure the new header works correctly.');
