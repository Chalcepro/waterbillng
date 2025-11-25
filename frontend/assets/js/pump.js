// Pump status functionality
document.addEventListener('DOMContentLoaded', function() {
    const pumpForm = document.getElementById('pump-status-form');
    if (pumpForm) {
        pumpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const status = formData.get('status');
            
            if (status !== 'active') {
                const confirmMessage = "Are you sure you want to set pump status to " + 
                                      status.toUpperCase() + "? This will notify all users.";
                
                if (!confirm(confirmMessage)) {
                    return;
                }
            }
            
            this.submit();
        });
    }
});