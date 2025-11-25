document.addEventListener('DOMContentLoaded', function() {
    // Initialize tabs
    const tabElms = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabElms.forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', function (event) {
            // Handle tab switching if needed
        });
    });

    // Character counters
    const messageInputs = [
        { input: 'message', counter: 'char-count' },
        { input: 'both-message', counter: 'both-char-count' }
    ];

    messageInputs.forEach(({input, counter}) => {
        const element = document.getElementById(input);
        if (element) {
            element.addEventListener('input', function() {
                document.getElementById(counter).textContent = this.value.length;
            });
        }
    });

    // Handle form submission
    const notificationForm = document.getElementById('notification-form');
    if (notificationForm) {
        notificationForm.addEventListener('submit', handleNotificationSubmit);
    }

    // Load notifications
    loadNotifications();
});

async function handleNotificationSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const activeTab = document.querySelector('#notificationTabs .nav-link.active').id;
    
    // Collect data based on active tab
    let notificationData = {
        recipients: formData.get('recipients'),
        type: formData.get('type'),
        notification_type: 'in_app' // Default
    };

    // Handle different tabs
    if (activeTab === 'email-tab') {
        notificationData.notification_type = 'email';
        notificationData.subject = formData.get('email-subject');
        notificationData.message = formData.get('email-content');
    } else if (activeTab === 'both-tab') {
        notificationData.notification_type = 'both';
        notificationData.in_app_message = formData.get('both-message');
        notificationData.email_subject = formData.get('both-email-subject');
        notificationData.email_content = formData.get('both-email-content');
    } else {
        // In-app tab
        notificationData.message = formData.get('message');
    }

    // Add CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) {
        notificationData._token = csrfToken;
    }

    try {
        const response = await fetch('/api/admin/send_notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(notificationData)
        });

        const result = await response.json();
        
        if (result.success) {
            showNotification('Notification sent successfully!', 'success');
            form.reset();
            loadNotifications(); // Refresh the list
        } else {
            showNotification(result.error || 'Failed to send notification', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred while sending the notification', 'error');
    }
}

function showNotification(message, type = 'info') {
    // Create notification element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to page
    const container = document.querySelector('.alerts-container');
    if (!container) {
        const alertsDiv = document.createElement('div');
        alertsDiv.className = 'alerts-container position-fixed top-0 end-0 p-3';
        alertsDiv.style.zIndex = '1100';
        document.body.prepend(alertsDiv);
        alertsDiv.appendChild(alert);
    } else {
        container.appendChild(alert);
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 5000);
}

async function loadNotifications() {
    const container = document.getElementById('notificationsContainer');
    if (!container) return;
    
    try {
        container.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Loading notifications...</p>
            </div>
        `;
        
        const response = await fetch('/api/admin/notifications.php?action=list');
        const result = await response.json();
        
        if (result.success) {
            renderNotifications(result.data);
        } else {
            throw new Error(result.error || 'Failed to load notifications');
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        container.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                Failed to load notifications. Please try again later.
            </div>
        `;
    }
}

function renderNotifications(notifications) {
    const container = document.getElementById('notificationsContainer');
    if (!container) return;
    
    if (!notifications || notifications.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <h5>No Notifications</h5>
                <p class="text-muted">No notifications have been sent yet.</p>
            </div>
        `;
        return;
    }
    
    // Group notifications by date
    const grouped = notifications.reduce((groups, notification) => {
        const date = new Date(notification.created_at).toLocaleDateString();
        if (!groups[date]) {
            groups[date] = [];
        }
        groups[date].push(notification);
        return groups;
    }, {});
    
    // Render grouped notifications
    container.innerHTML = Object.entries(grouped).map(([date, items]) => `
        <div class="mb-4">
            <h6 class="text-muted mb-3">${date}</h6>
            <div class="list-group">
                ${items.map(notification => `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0">
                                <i class="fas ${getNotificationIcon(notification.type)} me-2"></i>
                                ${notification.subject || 'No Subject'}
                            </h6>
                            <small class="text-muted">${new Date(notification.created_at).toLocaleTimeString()}</small>
                        </div>
                        <p class="mb-1">${notification.message || ''}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-users me-1"></i>
                                ${notification.recipient_count || 0} recipients
                            </small>
                            <span class="badge bg-${getNotificationBadgeClass(notification.type)}">
                                ${notification.type}
                            </span>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');
}

function getNotificationIcon(type) {
    const icons = {
        'info': 'fa-info-circle text-primary',
        'warning': 'fa-exclamation-triangle text-warning',
        'important': 'fa-exclamation-circle text-danger',
        'update': 'fa-sync-alt text-info',
        'payment': 'fa-credit-card text-success',
        'maintenance': 'fa-tools text-secondary',
        'emergency': 'fa-exclamation-triangle text-danger',
        'default': 'fa-bell text-muted'
    };
    return icons[type] || icons['default'];
}

function getNotificationBadgeClass(type) {
    const classes = {
        'info': 'info',
        'warning': 'warning',
        'important': 'danger',
        'update': 'primary',
        'payment': 'success',
        'maintenance': 'secondary',
        'emergency': 'danger',
        'default': 'secondary'
    };
    return classes[type] || classes['default'];
}
