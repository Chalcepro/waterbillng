// Main application JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Payment amount selection
    document.querySelectorAll('.amount-option').forEach(option => {
        option.addEventListener('click', function() {
            const amount = this.getAttribute('data-amount');
            document.querySelector('#payment-amount').value = amount;
            
            // Highlight selected option
            document.querySelectorAll('.amount-option').forEach(el => {
                el.classList.remove('selected');
            });
            this.classList.add('selected');
        });
    });
    
    // File upload drag and drop
    const dropZone = document.getElementById('drop-zone');
    if (dropZone) {
        const fileInput = dropZone.querySelector('input[type="file"]');
        
        dropZone.addEventListener('click', () => fileInput.click());
        
        ['dragover', 'dragenter'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight(e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('highlight');
        }
        
        function unhighlight(e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('highlight');
        }
        
        dropZone.addEventListener('drop', function(e) {
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                // Update UI to show file name
                dropZone.querySelector('p').textContent = files[0].name;
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const amountInput = form.querySelector('input[name="amount"]');
            if (amountInput) {
                const minAmount = parseFloat(amountInput.getAttribute('min'));
                const amount = parseFloat(amountInput.value);
                
                if (amount % minAmount !== 0) {
                    e.preventDefault();
                    if (window.notify) notify.warn(`Amount must be a multiple of ${minAmount}`);
                }
            }
        });
    });
});