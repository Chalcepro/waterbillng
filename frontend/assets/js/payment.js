// Payment specific functionality
document.addEventListener('DOMContentLoaded', function() {
    // Process external payment
    const externalPaymentForm = document.getElementById('external-payment-form');
    if (externalPaymentForm) {
        externalPaymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Show loading indicator
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            
            // Send to OCR API
            fetch('../../api/ocr.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Auto-fill form with extracted data
                    document.querySelector('input[name="name"]').value = data.data.name;
                    document.querySelector('input[name="amount"]').value = data.data.amount;
                    document.querySelector('input[name="date"]').value = data.data.date;
                    
                    // Submit the form
                    document.getElementById('payment-form').submit();
                } else {
                    if (window.notify) notify.error('Error processing receipt: ' + (data.error || 'Unknown error'));
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Receipt';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (window.notify) notify.error('An error occurred while processing your receipt');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Receipt';
            });
        });
    }
});