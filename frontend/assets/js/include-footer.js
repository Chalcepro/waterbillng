document.addEventListener('DOMContentLoaded', function() {
    // Create a container for the footer
    const footerContainer = document.createElement('div');
    footerContainer.id = 'footer-container';
    
    // Add the footer to the page
    fetch('../includes/footer.html')
        .then(response => response.text())
        .then(html => {
            footerContainer.innerHTML = html;
            document.body.appendChild(footerContainer);
            
            // Add padding to the main content to prevent footer from overlapping
            const mainContent = document.querySelector('main') || document.querySelector('.content') || document.querySelector('.container');
            if (mainContent) {
                mainContent.style.paddingBottom = '100px';
            }
            
            // Add smooth scroll to top for footer links
            document.querySelectorAll('.footer-section a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Handle newsletter form submission
            const newsletterForm = document.querySelector('.newsletter-form');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const emailInput = this.querySelector('input[type="email"]');
                    if (emailInput && emailInput.value) {
                        // Here you would typically send this to your server
                        console.log('Newsletter subscription:', emailInput.value);
                        alert('Thank you for subscribing to our newsletter!');
                        emailInput.value = '';
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error loading footer:', error);
        });
});
