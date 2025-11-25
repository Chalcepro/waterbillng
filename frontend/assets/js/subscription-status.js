class SubscriptionStatus {
    constructor(options = {}) {
        this.options = {
            updateInterval: 3600000, // 1 hour in milliseconds
            warningThreshold: 7, // days
            container: document.body,
            showOnAllPages: true,
            ...options
        };
        
        this.statusElement = null;
        this.initialize();
    }
    
    async initialize() {
        // Create status element if it doesn't exist
        if (!this.statusElement) {
            this.statusElement = document.createElement('div');
            this.statusElement.id = 'subscription-status-bar';
            this.statusElement.style.cssText = `
                position: fixed;
                bottom: 60px; /* Above the bottom nav */
                left: 0;
                right: 0;
                background: #2c3e50;
                color: white;
                padding: 10px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                z-index: 999;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.2);
                font-size: 14px;
            `;
            
            const statusContent = document.createElement('div');
            statusContent.id = 'subscription-status-content';
            statusContent.style.flex = '1';
            
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.style.cssText = `
                background: none;
                border: none;
                color: white;
                font-size: 20px;
                cursor: pointer;
                margin-left: 15px;
            `;
            closeBtn.addEventListener('click', () => this.hide());
            
            this.statusElement.appendChild(statusContent);
            this.statusElement.appendChild(closeBtn);
            this.options.container.appendChild(this.statusElement);
        }
        
        // Initial load
        await this.updateStatus();
        
        // Set up periodic updates
        this.updateInterval = setInterval(() => this.updateStatus(), this.options.updateInterval);
    }
    
    async updateStatus() {
        try {
            const response = await fetch('../../api/subscription/status.php', {
                credentials: 'include'
            });
            
            if (!response.ok) throw new Error('Failed to fetch subscription status');
            
            const data = await response.json();
            
            if (!data.has_subscription) {
                this.hide();
                return;
            }
            
            this.render(data);
            
            // If subscription is expired or about to expire, show notification
            if (data.status === 'expired' || data.days_remaining <= this.options.warningThreshold) {
                this.show();
            }
            
        } catch (error) {
            console.error('Error updating subscription status:', error);
            this.hide();
        }
    }
    
    render(data) {
        const content = document.getElementById('subscription-status-content');
        if (!content) return;
        
        const endDate = new Date(data.end_date).toLocaleDateString();
        let statusClass = '';
        let statusText = '';
        
        if (data.status === 'expired') {
            statusClass = 'status-expired';
            statusText = 'Subscription Expired';
        } else if (data.days_remaining <= 3) {
            statusClass = 'status-warning';
            statusText = `${data.days_remaining} ${data.days_remaining === 1 ? 'day' : 'days'} remaining`;
        } else {
            statusClass = 'status-ok';
            statusText = `Expires in ${data.days_remaining} days`;
        }
        
        // Update status element
        content.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <div>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                    <span>${data.percentage_used}% used (${data.days_used}/${data.total_days} days)</span>
                </div>
                <div style="margin-left: 20px; flex-shrink: 0;">
                    <span>Expires: ${endDate}</span>
                </div>
            </div>
            <div style="margin-top: 5px; height: 4px; background: rgba(255,255,255,0.2); border-radius: 2px; overflow: hidden;">
                <div style="width: ${data.percentage_used}%; height: 100%; background: ${this.getStatusColor(data)}; transition: width 0.3s;"></div>
            </div>
        `;
        
        // Show renew button if subscription is expiring soon or expired
        if (data.days_remaining <= this.options.warningThreshold || data.status === 'expired') {
            const renewBtn = document.createElement('a');
            renewBtn.href = 'payment.html';
            renewBtn.textContent = data.status === 'expired' ? 'Renew Now' : 'Extend Subscription';
            renewBtn.style.cssText = `
                display: inline-block;
                margin-left: 15px;
                padding: 3px 10px;
                background: #e74c3c;
                color: white;
                border-radius: 3px;
                text-decoration: none;
                font-size: 13px;
                transition: background 0.2s;
            `;
            renewBtn.addEventListener('mouseover', () => {
                renewBtn.style.background = '#c0392b';
            });
            renewBtn.addEventListener('mouseout', () => {
                renewBtn.style.background = '#e74c3c';
            });
            
            const statusContainer = content.querySelector('div > div:first-child');
            statusContainer.appendChild(renewBtn);
        }
        
        this.show();
    }
    
    getStatusColor(data) {
        if (data.status === 'expired') return '#e74c3c';
        if (data.days_remaining <= 3) return '#f39c12';
        if (data.days_remaining <= 7) return '#3498db';
        return '#2ecc71';
    }
    
    show() {
        if (this.statusElement) {
            this.statusElement.style.display = 'flex';
        }
    }
    
    hide() {
        if (this.statusElement) {
            this.statusElement.style.display = 'none';
        }
    }
    
    destroy() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
        if (this.statusElement && this.statusElement.parentNode) {
            this.statusElement.parentNode.removeChild(this.statusElement);
        }
    }
}

// Auto-initialize if this is included in a user page
if (document.body.classList.contains('user-page')) {
    document.addEventListener('DOMContentLoaded', () => {
        window.subscriptionStatus = new SubscriptionStatus();
    });
}
