@props(['unreadCount' => 0])

<div class="position-relative notification-bell-wrapper">
    <!-- Notification Bell Button -->
    <button
        id="notification-bell-btn"
        class="btn btn-link text-white p-2 position-relative notification-btn"
        title="Notifikasi"
        style="text-decoration: none;"
    >
        <i class="fas fa-bell fs-5"></i>
        
        <!-- Unread Badge -->
        <span id="notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              style="font-size: 0.65rem; padding: 0.25rem 0.4rem; display: none;">
            <span id="notification-count">0</span>
        </span>
    </button>

    <!-- Dropdown Panel -->
    <div
        id="notification-dropdown"
        class="notification-dropdown card shadow-lg"
    >
        <!-- Header -->
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold notification-title">Notifikasi</h5>
            <button
                id="mark-all-read-btn"
                class="btn btn-sm btn-link text-primary p-0 notification-btn-header"
                style="text-decoration: none; display: none; font-size: 0.75rem;"
            >
                Tandai dibaca
            </button>
        </div>

        <!-- Notification List -->
        <div id="notification-list" class="card-body p-0" style="overflow-y: auto;">
            <!-- Loading State -->
            <div id="loading-state" class="d-none d-flex justify-content-center align-items-center py-5">
                <div class="spinner-border text-secondary" role="status" style="width: 1.5rem; height: 1.5rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <!-- Empty State -->
            <div id="empty-state" class="d-flex flex-column align-items-center justify-content-center py-5 text-muted text-center px-3">
                <i class="fas fa-inbox fs-1 mb-3 opacity-50"></i>
                <p class="small mb-0">Tidak ada notifikasi</p>
            </div>

            <!-- Notifications Container -->
            <div id="notifications-container"></div>
        </div>

        <!-- Footer -->
        <div id="footer-links" class="card-footer bg-light border-top" style="display: none;">
            <a href="{{ route('notifications.all') }}" class="btn btn-link btn-sm text-primary p-0" style="text-decoration: none; font-size: 0.85rem;">
                Lihat semua notifikasi →
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bellBtn = document.getElementById('notification-bell-btn');
    const dropdown = document.getElementById('notification-dropdown');
    const notificationBadge = document.getElementById('notification-badge');
    const notificationCount = document.getElementById('notification-count');
    const notificationList = document.getElementById('notification-list');
    const loadingState = document.getElementById('loading-state');
    const emptyState = document.getElementById('empty-state');
    const notificationsContainer = document.getElementById('notifications-container');
    const markAllReadBtn = document.getElementById('mark-all-read-btn');
    const footerLinks = document.getElementById('footer-links');
    
    let isOpen = false;
    let notifications = [];

    // Helper function to check if mobile
    function isMobile() {
        return window.innerWidth < 768;
    }

    // Helper function to adjust dropdown position
    function adjustDropdownPosition() {
        const bellRect = bellBtn.getBoundingClientRect();
        const dropdownRect = dropdown.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        // Reset positioning
        dropdown.style.right = 'auto';
        dropdown.style.left = 'auto';
        dropdown.style.top = 'auto';
        dropdown.style.bottom = 'auto';

        if (isMobile()) {
            // Mobile: Full width or near full width, centered
            const dropdownWidth = Math.min(380, viewportWidth - 16);
            const leftPos = (viewportWidth - dropdownWidth) / 2;
            
            dropdown.style.left = leftPos + 'px';
            dropdown.style.width = dropdownWidth + 'px';
            dropdown.style.top = (bellRect.bottom + 8) + 'px';
        } else {
            // Desktop: Align to right of bell
            const rightPos = viewportWidth - bellRect.right;
            dropdown.style.right = rightPos + 'px';
            dropdown.style.width = '400px';
            dropdown.style.top = (bellRect.bottom + 8) + 'px';
        }

        // Check if dropdown goes off-screen vertically
        const finalDropdownRect = dropdown.getBoundingClientRect();
        if (finalDropdownRect.bottom > viewportHeight - 8) {
            dropdown.style.top = 'auto';
            dropdown.style.bottom = (viewportHeight - bellRect.top + 8) + 'px';
        }
    }

    // Toggle dropdown
    bellBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        isOpen = !isOpen;
        
        if (isOpen) {
            dropdown.style.display = 'block';
            // Adjust position after display
            setTimeout(adjustDropdownPosition, 0);
            loadNotifications();
        } else {
            dropdown.style.display = 'none';
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!bellBtn.contains(e.target) && !dropdown.contains(e.target)) {
            isOpen = false;
            dropdown.style.display = 'none';
        }
    });

    // Handle touch events on mobile
    document.addEventListener('touchstart', (e) => {
        if (!bellBtn.contains(e.target) && !dropdown.contains(e.target)) {
            isOpen = false;
            dropdown.style.display = 'none';
        }
    });

    // Adjust dropdown on window resize
    window.addEventListener('resize', () => {
        if (isOpen) {
            adjustDropdownPosition();
        }
    });

    // Close dropdown when clicking on dropdown (but not on buttons/links)
    dropdown.addEventListener('click', (e) => {
        e.stopPropagation();
        // Close if clicking on notification item (but not on buttons)
        if (e.target.closest('.notification-item') && !e.target.closest('button')) {
            // Keep open for mobile, close for desktop
            if (!isMobile()) {
                isOpen = false;
                dropdown.style.display = 'none';
            }
        }
    });

    async function loadNotifications() {
        // Show loading state initially
        loadingState.classList.remove('d-none');
        loadingState.style.display = 'flex';
        emptyState.classList.add('d-none');
        emptyState.style.display = 'none';
        notificationsContainer.innerHTML = '';
        
        try {
            console.log('[Notification] Fetching from /api/notifications?status=unread&per_page=10');
            const response = await fetch('/api/notifications?status=unread&per_page=10');
            console.log('[Notification] Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('[Notification] Received data:', data);
            notifications = data.data || [];
            console.log('[Notification] Total notifications:', notifications.length);
            
            // HIDE loading state when data is received
            loadingState.classList.add('d-none');
            loadingState.style.display = 'none';
            
            if (notifications.length === 0) {
                // No notifications - show empty state
                emptyState.classList.remove('d-none');
                emptyState.style.display = 'flex';
                markAllReadBtn.style.display = 'none';
                footerLinks.style.display = 'none';
                console.log('[Notification] No notifications - showing empty state');
            } else {
                // Has notifications - show them
                emptyState.classList.add('d-none');
                emptyState.style.display = 'none';
                markAllReadBtn.style.display = 'inline-block';
                footerLinks.style.display = 'block';
                renderNotifications();
                console.log('[Notification] Showing ' + notifications.length + ' notifications');
            }
            
            updateUnreadCount();
        } catch (error) {
            console.error('[Notification] Error loading notifications:', error);
            console.error('[Notification] Error details:', error.message);
            loadingState.classList.add('d-none');
            loadingState.style.display = 'none';
            emptyState.classList.remove('d-none');
            emptyState.style.display = 'flex';
        }
    }

    function renderNotifications() {
        notificationsContainer.innerHTML = notifications.map(notification => `
            <div class="notification-item border-bottom px-3 py-3" data-notification-id="${notification.id}" style="border-bottom: 1px solid #e9ecef; ${!notification.is_read ? 'background-color: #f8f9fa;' : ''} cursor: pointer; transition: all 0.3s ease-out;">
                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                    <div class="d-flex gap-2 flex-wrap flex-grow-1" style="min-width: 0;">
                        <span class="badge ${getTypeBadgeClass(notification.type)}" style="white-space: nowrap; font-size: 0.7rem;">
                            ${notification.type.toUpperCase()}
                        </span>
                        <span class="badge bg-secondary" style="white-space: nowrap; font-size: 0.7rem;">
                            ${notification.action.toUpperCase()}
                        </span>
                    </div>
                    <button class="btn btn-link btn-sm text-muted p-0 flex-shrink-0" onclick="deleteNotification(${notification.id}, event)" style="text-decoration: none; cursor: pointer; transition: opacity 0.2s;" title="Hapus notifikasi">
                        <i class="fas fa-times fs-6"></i>
                    </button>
                </div>
                <div class="mb-2">
                    <p class="fw-bold text-dark mb-1 small" onclick="markAsRead(${notification.id}, event)" style="cursor: pointer; word-break: break-word; transition: all 0.2s;">
                        ${notification.title}
                    </p>
                    <p class="text-muted mb-1 small" style="word-break: break-word;">
                        ${notification.message}
                    </p>
                    ${notification.performed_by_name ? `<p class="text-muted mb-0 small" style="font-size: 0.75rem; font-style: italic;">
                        Oleh: <strong>${notification.performed_by_name}</strong>
                    </p>` : ''}
                </div>
                <div class="text-muted small" style="font-size: 0.75rem;">
                    ${formatTime(notification.created_at)}
                </div>
            </div>
        `).join('');
    }

    function getTypeBadgeClass(type) {
        const classes = {
            student: 'bg-primary',
            payment: 'bg-success',
            user: 'bg-info',
            information: 'bg-warning text-dark',
        };
        return classes[type] || 'bg-secondary';
    }

    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Baru saja';
        if (minutes < 60) return `${minutes}m lalu`;
        if (hours < 24) return `${hours}h lalu`;
        if (days < 7) return `${days}d lalu`;
        return date.toLocaleDateString('id-ID');
    }

    async function updateUnreadCount() {
        try {
            const response = await fetch('/api/notifications/unread-count');
            const data = await response.json();
            const count = data.unread_count || 0;
            
            notificationCount.textContent = count;
            if (count > 0) {
                notificationBadge.style.display = 'inline-block';
            } else {
                notificationBadge.style.display = 'none';
            }
        } catch (error) {
            console.error('Error updating unread count:', error);
        }
    }

    window.markAsRead = async function(notificationId, event) {
        // Prevent bubbling
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        try {
            console.log('[Notification] Starting mark as read for notification ID:', notificationId);
            
            // Find element
            const notifElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (!notifElement) {
                console.warn('Notification element not found:', notificationId);
                return;
            }
            
            // Get CSRF token
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (!csrfTokenMeta) {
                console.error('[Notification] CSRF token meta tag not found!');
                return;
            }
            
            const csrfToken = csrfTokenMeta.getAttribute('content');
            if (!csrfToken) {
                console.error('[Notification] CSRF token value is empty!');
                return;
            }
            
            // Remove notification from array immediately (optimistic update)
            notifications = notifications.filter(n => n.id !== notificationId);
            
            // Animate removal with smooth transition
            notifElement.style.transition = 'all 0.3s ease-out';
            notifElement.style.opacity = '0.5';
            notifElement.style.textDecoration = 'line-through';
            notifElement.style.color = '#ccc';
            
            // Remove from UI after animation
            setTimeout(() => {
                if (notifElement.parentNode) {
                    notifElement.style.opacity = '0';
                    notifElement.style.transform = 'translateX(100%)';
                    notifElement.style.marginTop = '-100%';
                    
                    setTimeout(() => {
                        if (notifElement.parentNode) {
                            notifElement.remove();
                        }
                        
                        // Check if container is empty
                        if (notificationsContainer.children.length === 0) {
                            emptyState.classList.remove('d-none');
                            emptyState.style.display = 'flex';
                            markAllReadBtn.style.display = 'none';
                            console.log('[Notification] All notifications marked as read - showing empty state');
                        }
                    }, 200);
                }
            }, 150);
            
            // Send mark as read request to server
            console.log('[Notification] Sending PUT request to /api/notifications/' + notificationId + '/mark-as-read');
            const response = await fetch(`/api/notifications/${notificationId}/mark-as-read`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            
            console.log('[Notification] Mark as read response status:', response.status);
            
            if (!response.ok) {
                const errorData = await response.text();
                console.error('Failed to mark notification as read - server error:', response.status);
                console.error('[Notification] Response body:', errorData);
            } else {
                console.log('[Notification] Successfully marked as read:', notificationId);
            }
            
            updateUnreadCount();
        } catch (error) {
            console.error('[Notification] Error marking notification as read:', error);
            // Reload in case of error
            setTimeout(() => {
                loadNotifications();
            }, 500);
        }
    };

    markAllReadBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        try {
            // Get CSRF token
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (!csrfTokenMeta) {
                console.error('[Notification] CSRF token meta tag not found!');
                alert('Kesalahan: CSRF token tidak ditemukan. Silakan refresh halaman.');
                return;
            }
            
            const csrfToken = csrfTokenMeta.getAttribute('content');
            if (!csrfToken) {
                console.error('[Notification] CSRF token value is empty!');
                alert('Kesalahan: CSRF token kosong. Silakan refresh halaman.');
                return;
            }
            
            console.log('[Notification] Sending PUT request to /api/notifications/mark-all-as-read');
            const response = await fetch('/api/notifications/mark-all-as-read', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            
            console.log('[Notification] Mark all as read response status:', response.status);
            
            if (!response.ok) {
                const errorData = await response.text();
                console.error('[Notification] Failed with status:', response.status);
                console.error('[Notification] Response body:', errorData);
                alert('Gagal menandai semua notifikasi. Status: ' + response.status);
                return;
            }
            
            // Fade out all notifications
            const notifElements = document.querySelectorAll('.notification-item');
            notifElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '0';
                    element.style.transform = 'translateX(100%)';
                }, index * 50);
            });
            
            // Clear after animation
            setTimeout(() => {
                notifications = [];
                notificationsContainer.innerHTML = '';
                emptyState.style.display = 'flex';
                markAllReadBtn.style.display = 'none';
                updateUnreadCount();
                console.log('[Notification] Successfully marked all as read');
            }, notifElements.length * 50 + 300);
        } catch (error) {
            console.error('[Notification] Error marking all notifications as read:', error);
            console.error('[Notification] Error stack:', error.stack);
            alert('Terjadi kesalahan saat menandai semua notifikasi: ' + error.message);
        }
    });

    window.deleteNotification = async function(notificationId, event) {
        // Prevent bubbling
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        try {
            console.log('[Notification] Starting delete for notification ID:', notificationId);
            
            // Find element
            const notifElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (!notifElement) {
                console.warn('Notification element not found:', notificationId);
                return;
            }
            
            // Get CSRF token
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (!csrfTokenMeta) {
                console.error('[Notification] CSRF token meta tag not found!');
                alert('Kesalahan: CSRF token tidak ditemukan. Silakan refresh halaman.');
                return;
            }
            
            const csrfToken = csrfTokenMeta.getAttribute('content');
            if (!csrfToken) {
                console.error('[Notification] CSRF token value is empty!');
                alert('Kesalahan: CSRF token kosong. Silakan refresh halaman.');
                return;
            }
            
            console.log('[Notification] CSRF token retrieved successfully');
            
            // Disable button immediately
            const deleteBtn = notifElement.querySelector('button[onclick*="deleteNotification"]');
            if (deleteBtn) {
                deleteBtn.disabled = true;
                deleteBtn.style.opacity = '0.5';
            }
            
            // Remove notification from array immediately (optimistic update)
            notifications = notifications.filter(n => n.id !== notificationId);
            
            // Animate removal with smooth transition
            notifElement.style.transition = 'all 0.3s ease-out';
            notifElement.style.opacity = '0';
            notifElement.style.transform = 'translateX(100%)';
            notifElement.style.marginTop = '-100%';
            
            // Remove from UI after animation
            setTimeout(() => {
                if (notifElement.parentNode) {
                    notifElement.remove();
                }
                
                // Check if container is empty
                if (notificationsContainer.children.length === 0) {
                    emptyState.classList.remove('d-none');
                    emptyState.style.display = 'flex';
                    markAllReadBtn.style.display = 'none';
                    console.log('[Notification] All notifications deleted - showing empty state');
                }
            }, 300);
            
            // Send delete request to server
            console.log('[Notification] Sending DELETE request to /api/notifications/' + notificationId);
            const response = await fetch(`/api/notifications/${notificationId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            
            console.log('[Notification] DELETE response status:', response.status);
            
            if (!response.ok) {
                const errorData = await response.text();
                console.error('[Notification] Failed to delete notification - server error:', response.status);
                console.error('[Notification] Response body:', errorData);
                alert('Gagal menghapus notifikasi. Status: ' + response.status);
                // Reload notifications in case of error
                setTimeout(() => {
                    loadNotifications();
                }, 500);
            } else {
                console.log('[Notification] Successfully deleted notification:', notificationId);
            }
            
            updateUnreadCount();
        } catch (error) {
            console.error('[Notification] Error deleting notification:', error);
            console.error('[Notification] Error stack:', error.stack);
            alert('Terjadi kesalahan saat menghapus notifikasi: ' + error.message);
            // Reload in case of error
            setTimeout(() => {
                loadNotifications();
            }, 500);
        }
    };

    // Initial load
    updateUnreadCount();
});
</script>

<style>
    .notification-bell-wrapper {
        display: inline-block;
    }
    
    .notification-btn {
        padding: 0.5rem !important;
        border-radius: 0.375rem;
        transition: background-color 0.2s ease;
    }

    .notification-btn:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
    }

    .notification-dropdown {
        position: fixed;
        z-index: 9999;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        border-radius: 0.5rem;
        border: none;
        max-height: 80vh;
        display: none;
    }

    .notification-dropdown .card-body {
        max-height: calc(80vh - 120px);
    }

    .notification-title {
        font-size: 1rem;
    }

    .notification-btn-header {
        font-size: 0.75rem;
    }

    .notification-item {
        transition: all 0.3s ease-in-out;
    }

    .notification-item:hover {
        background-color: #f8f9fa !important;
    }

    /* Mobile responsive */
    @media (max-width: 576px) {
        .notification-dropdown {
            width: calc(100vw - 32px) !important;
            max-width: 380px !important;
        }

        .notification-title {
            font-size: 0.9rem;
        }

        .notification-dropdown .card-body {
            max-height: calc(80vh - 110px);
        }

        .notification-item {
            padding: 0.75rem 0.75rem !important;
        }

        .notification-item p {
            margin-bottom: 0.5rem !important;
        }

        .badge {
            font-size: 0.65rem !important;
            padding: 0.25rem 0.4rem !important;
        }
    }

    /* Tablet responsive */
    @media (min-width: 577px) and (max-width: 991px) {
        .notification-dropdown {
            width: 350px !important;
        }

        .notification-title {
            font-size: 0.95rem;
        }
    }

    /* Desktop */
    @media (min-width: 992px) {
        .notification-dropdown {
            width: 400px !important;
        }

        .notification-item:hover {
            background-color: #f8f9fa !important;
        }
    }
</style>
