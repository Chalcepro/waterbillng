// Payment with Paystack
function payWithPaystack(amount, email) {
    // Check if Paystack is loaded
    if (typeof PaystackPop === 'undefined') {
        if (window.notify) notify.error('Paystack is not loaded. Please refresh the page.');
        return;
    }
    
    const handler = PaystackPop.setup({
        key: 'pk_test_your_public_key_here', // This should be replaced with actual key from config
        email: email || 'customer@example.com',
        amount: amount * 100, // Convert to kobo
        currency: 'NGN',
        ref: 'WB-' + Math.floor(Math.random() * 1000000000 + 1),
        onClose: function() {
            if (window.notify) notify.info('Payment window closed.');
        },
        callback: function(response) {
            // Verify payment on server
            fetch('../../api/paystack.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    reference: response.reference,
                    amount: amount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (window.notify) notify.success('Payment successful!');
                    window.location.href = '../user/dashboard.html';
                } else {
                    if (window.notify) notify.error('Payment failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Payment verification error:', error);
                if (window.notify) notify.error('Payment verification failed. Please contact support.');
            });
        }
    });
    handler.openIframe();
}

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('mobile-menu');
    const navMenu = document.getElementById('nav-menu');
    
    if (menuBtn) {
        menuBtn.addEventListener('click', function() {
            navMenu.classList.toggle('show');
        });
    }
});

// OCR Receipt Processing
function processReceipt(imageFile) {
    const formData = new FormData();
    formData.append('receipt', imageFile);
    
    fetch('../../api/ocr.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('amount').value = data.amount;
            document.getElementById('transaction_id').value = data.transaction_id;
        } else {
            if (window.notify) notify.error('Error processing receipt: ' + (data.message || 'Unknown error'));
        }
    });
}

// Event listener for receipt upload
document.getElementById('receipt-upload')?.addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        processReceipt(this.files[0]);
    }
});

function showUploadToast(msg, success) {
    var toast = document.getElementById('upload-toast');
    if (!toast) {
        // Create toast if missing
        toast = document.createElement('div');
        toast.id = 'upload-toast';
        toast.style.position = 'fixed';
        toast.style.top = '30px';
        toast.style.left = '50%';
        toast.style.transform = 'translateX(-50%)';
        toast.style.background = success ? '#00C853' : '#2e6fff';
        toast.style.color = '#fff';
        toast.style.padding = '14px 28px';
        toast.style.borderRadius = '8px';
        toast.style.zIndex = '9999';
        toast.style.fontWeight = '600';
        toast.style.fontSize = '1.08rem';
        toast.style.boxShadow = '0 2px 12px rgba(46,111,255,0.13)';
        toast.style.transition = 'opacity 0.3s';
        toast.style.opacity = '0';
        document.body.appendChild(toast);
    }
    toast.innerText = msg;
    toast.style.background = success ? '#00C853' : '#2e6fff';
    toast.style.opacity = '1';
    toast.style.display = 'block';
    setTimeout(function() {
        toast.style.opacity = '0';
        setTimeout(function(){ toast.style.display = 'none'; }, 400);
    }, 3500);
}