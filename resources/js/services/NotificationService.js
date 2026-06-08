import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Initialize Pusher for WebSocket
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'app-key',
    cluster: 'mt1',
    wsHost: window.location.hostname,
    wsPort: 6001,
    wssPort: 6001,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

// Notification Service
export const NotificationService = {
    /**
     * Subscribe to private user channel for real-time notifications
     */
    subscribeToUserNotifications(userId, onNotification) {
        window.Echo.private(`user.${userId}`)
            .listen('StudentCreated', (event) => {
                onNotification({
                    type: 'student',
                    action: 'created',
                    data: event,
                });
            })
            .listen('StudentUpdated', (event) => {
                onNotification({
                    type: 'student',
                    action: 'updated',
                    data: event,
                });
            })
            .listen('StudentDeleted', (event) => {
                onNotification({
                    type: 'student',
                    action: 'deleted',
                    data: event,
                });
            })
            .listen('PaymentCreated', (event) => {
                onNotification({
                    type: 'payment',
                    action: 'created',
                    data: event,
                });
            })
            .listen('PaymentStatusChanged', (event) => {
                onNotification({
                    type: 'payment',
                    action: 'status_changed',
                    data: event,
                });
            })
            .listen('UserCreated', (event) => {
                onNotification({
                    type: 'user',
                    action: 'created',
                    data: event,
                });
            })
            .listen('UserUpdated', (event) => {
                onNotification({
                    type: 'user',
                    action: 'updated',
                    data: event,
                });
            })
            .listen('UserDeleted', (event) => {
                onNotification({
                    type: 'user',
                    action: 'deleted',
                    data: event,
                });
            });
    },

    /**
     * Subscribe to admin channel for all events
     */
    subscribeToAdminChannel(onNotification) {
        window.Echo.private('admin')
            .listen('StudentCreated', (event) => {
                onNotification({
                    type: 'student',
                    action: 'created',
                    data: event,
                });
            })
            .listen('StudentUpdated', (event) => {
                onNotification({
                    type: 'student',
                    action: 'updated',
                    data: event,
                });
            })
            .listen('StudentDeleted', (event) => {
                onNotification({
                    type: 'student',
                    action: 'deleted',
                    data: event,
                });
            })
            .listen('PaymentCreated', (event) => {
                onNotification({
                    type: 'payment',
                    action: 'created',
                    data: event,
                });
            })
            .listen('PaymentStatusChanged', (event) => {
                onNotification({
                    type: 'payment',
                    action: 'status_changed',
                    data: event,
                });
            })
            .listen('UserCreated', (event) => {
                onNotification({
                    type: 'user',
                    action: 'created',
                    data: event,
                });
            })
            .listen('UserUpdated', (event) => {
                onNotification({
                    type: 'user',
                    action: 'updated',
                    data: event,
                });
            })
            .listen('UserDeleted', (event) => {
                onNotification({
                    type: 'user',
                    action: 'deleted',
                    data: event,
                });
            });
    },

    /**
     * Get notifications from API
     */
    async getNotifications(params = {}) {
        try {
            const query = new URLSearchParams(params).toString();
            const response = await fetch(`/api/notifications?${query}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching notifications:', error);
            return { data: [], pagination: {} };
        }
    },

    /**
     * Get unread notification count
     */
    async getUnreadCount() {
        try {
            const response = await fetch('/api/notifications/unread-count');
            const data = await response.json();
            return data.unread_count;
        } catch (error) {
            console.error('Error fetching unread count:', error);
            return 0;
        }
    },

    /**
     * Mark notification as read
     */
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/api/notifications/${notificationId}/mark-as-read`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });
            return await response.json();
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    },

    /**
     * Mark all notifications as read
     */
    async markAllAsRead() {
        try {
            const response = await fetch('/api/notifications/mark-all-as-read', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });
            return await response.json();
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    },

    /**
     * Delete notification
     */
    async deleteNotification(notificationId) {
        try {
            const response = await fetch(`/api/notifications/${notificationId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });
            return await response.json();
        } catch (error) {
            console.error('Error deleting notification:', error);
        }
    },

    /**
     * Delete all notifications
     */
    async deleteAllNotifications() {
        try {
            const response = await fetch('/api/notifications', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });
            return await response.json();
        } catch (error) {
            console.error('Error deleting all notifications:', error);
        }
    },
};

export default NotificationService;
