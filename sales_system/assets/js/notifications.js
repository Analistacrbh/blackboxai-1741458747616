/**
 * Notification Handler
 * Manages real-time notifications in the UI
 */

class NotificationHandler {
    constructor() {
        this.unreadCount = 0;
        this.checkInterval = 30000; // Check every 30 seconds
        this.initialized = false;
        
        // Initialize notification checking
        this.init();
    }
    
    /**
     * Initialize notification handler
     */
    init() {
        if (this.initialized) return;
        
        // Start periodic checking
        this.checkNotifications();
        setInterval(() => this.checkNotifications(), this.checkInterval);
        
        // Initialize notification dropdown
        this.initializeDropdown();
        
        this.initialized = true;
    }
    
    /**
     * Check for new notifications
     */
    checkNotifications() {
        fetch('../ajax/notifications.php?action=get_unread')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateNotifications(data.notifications, data.count);
                }
            })
            .catch(error => console.error('Error checking notifications:', error));
    }
    
    /**
     * Update notifications in UI
     * @param {Array} notifications New notifications
     * @param {number} count Total unread count
     */
    updateNotifications(notifications, count) {
        // Update notification count
        this.updateCount(count);
        
        // Update notification list if dropdown exists
        const container = document.getElementById('notification-list');
        if (container && notifications.length > 0) {
            // Add new notifications at the top
            notifications.reverse().forEach(notification => {
                // Check if notification already exists
                if (!container.querySelector(`[data-id="${notification.id}"]`)) {
                    const temp = document.createElement('div');
                    temp.innerHTML = notification.html;
                    container.insertBefore(temp.firstChild, container.firstChild);
                }
            });
        }
        
        // Show notification toast for new notifications
        if (this.unreadCount < count) {
            notifications.slice(0, count - this.unreadCount).forEach(notification => {
                this.showToast(notification);
            });
        }
        
        this.unreadCount = count;
    }
    
    /**
     * Update notification count in UI
     * @param {number} count New count
     */
    updateCount(count) {
        const badge = document.getElementById('notification-count');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        }
    }
    
    /**
     * Show notification toast
     * @param {Object} notification Notification data
     */
    showToast(notification) {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="toast-header">
                <i class="fas fa-bell me-2"></i>
                <strong class="me-auto">Nova Notificação</strong>
                <small>${notification.created_at}</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${notification.message}
            </div>
        `;
        
        document.getElementById('toast-container')?.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }
    
    /**
     * Initialize notification dropdown
     */
    initializeDropdown() {
        const dropdown = document.getElementById('notification-dropdown');
        if (!dropdown) return;
        
        // Load notifications when dropdown is opened
        dropdown.addEventListener('show.bs.dropdown', () => {
            this.checkNotifications();
        });
    }
    
    /**
     * Mark notification as read
     * @param {number} id Notification ID
     */
    markRead(id) {
        fetch('../ajax/notifications.php?action=mark_read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                const notification = document.querySelector(`.notification-item[data-id="${id}"]`);
                if (notification) {
                    notification.classList.add('read');
                    notification.querySelector('.mark-read')?.remove();
                }
                this.updateCount(data.count);
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }
    
    /**
     * Mark all notifications as read
     */
    markAllRead() {
        fetch('../ajax/notifications.php?action=mark_all_read', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                document.querySelectorAll('.notification-item').forEach(notification => {
                    notification.classList.add('read');
                    notification.querySelector('.mark-read')?.remove();
                });
                this.updateCount(0);
            }
        })
        .catch(error => console.error('Error marking all notifications as read:', error));
    }
    
    /**
     * Delete notification
     * @param {number} id Notification ID
     */
    delete(id) {
        if (!confirm('Deseja realmente excluir esta notificação?')) return;
        
        fetch('../ajax/notifications.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove notification from UI
                document.querySelector(`.notification-item[data-id="${id}"]`)?.remove();
                this.updateCount(data.count);
            }
        })
        .catch(error => console.error('Error deleting notification:', error));
    }
}

// Initialize notification handler
const notificationHandler = new NotificationHandler();

// Global functions for event handlers
window.markRead = (id) => notificationHandler.markRead(id);
window.markAllRead = () => notificationHandler.markAllRead();
window.deleteNotification = (id) => notificationHandler.delete(id);
