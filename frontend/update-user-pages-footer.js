const fs = require('fs');
const path = require('path');

const userPagesDir = path.join(__dirname, 'frontend', 'user');
const footerScript = `    <script src="../assets/js/include-footer.js"></script>`;

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
        
        // Check if footer script is already included
        if (content.includes('include-footer.js')) {
            console.log(`${file} already has footer script`);
            return;
        }
        
        // Add footer script before the closing body tag
        if (content.includes('</body>')) {
            content = content.replace(
                '</body>',
                `    ${footerScript}\n</body>`
            );
            
            fs.writeFileSync(filePath, content, 'utf8');
            console.log(`Updated ${file} with footer script`);
        } else {
            console.log(`Could not find </body> tag in ${file}`);
        }
    });
    
    console.log('\nUpdate complete!');
});
