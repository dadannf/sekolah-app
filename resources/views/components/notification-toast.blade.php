@props(['userId' => null, 'userRole' => null])

<div id="notification-toast-container"></div>

<script>
    // Real-time Notification Toast System - Responsive
    const notificationContainer = document.getElementById('notification-toast-container');

    // Responsive container positioning
    function updateContainerPosition() {
        const isMobile = window.innerWidth < 768;
        if (isMobile) {
            notificationContainer.style.position = 'fixed';
            notificationContainer.style.top = '10px';
            notificationContainer.style.left = '10px';
            notificationContainer.style.right = '10px';
            notificationContainer.style.zIndex = '9999';
            notificationContainer.style.display = 'flex';
            notificationContainer.style.flexDirection = 'column-reverse';
            notificationContainer.style.gap = '0.5rem';
            notificationContainer.style.pointerEvents = 'none';
            notificationContainer.style.maxWidth = 'calc(100vw - 20px)';
        } else {
            notificationContainer.style.position = 'fixed';
            notificationContainer.style.top = '20px';
            notificationContainer.style.right = '20px';
            notificationContainer.style.left = 'auto';
            notificationContainer.style.zIndex = '9999';
            notificationContainer.style.display = 'flex';
            notificationContainer.style.flexDirection = 'column-reverse';
            notificationContainer.style.gap = '0.75rem';
            notificationContainer.style.pointerEvents = 'none';
            notificationContainer.style.maxWidth = '450px';
        }
    }

    // Initial positioning
    updateContainerPosition();

    // Update on resize
    window.addEventListener('resize', updateContainerPosition);

    function createToast(notification) {
        const toast = document.createElement('div');
        toast.className = 'alert alert-dismissible fade show';
        toast.style.pointerEvents = 'auto';
        toast.style.animation = 'slideInRight 0.3s ease-out';

        const isMobile = window.innerWidth < 768;
        toast.style.fontSize = isMobile ? '0.85rem' : '0.95rem';
        toast.style.padding = isMobile ? '0.75rem' : '1rem';
        toast.style.borderRadius = '0.375rem';
        toast.style.wordBreak = 'break-word';

        const typeColors = {
            student: {
                bg: 'alert-primary',
                icon: '👤',
                title: 'Siswa',
            },
            payment: {
                bg: 'alert-success',
                icon: '💰',
                title: 'Pembayaran',
            },
            user: {
                bg: 'alert-info',
                icon: '👥',
                title: 'Pengguna',
            },
            information: {
                bg: 'alert-warning',
                icon: '📢',
                title: 'Informasi',
            },
        };

        const typeInfo = typeColors[notification.type] || typeColors.student;

        toast.innerHTML = `
            <div class="${typeInfo.bg}" role="alert">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="d-flex gap-2 flex-grow-1" style="min-width: 0;">
                        <span style="font-size: ${isMobile ? '1.25rem' : '1.5rem'}; flex-shrink: 0;">${typeInfo.icon}</span>
                        <div style="min-width: 0; flex: 1;">
                            <div class="fw-bold" style="word-break: break-word;">${typeInfo.title} - ${notification.action.toUpperCase()}</div>
                            <div class="small mt-1" style="word-break: break-word;">${notification.message}</div>
                            ${notification.performed_by_name ? `<div class="small mt-1" style="word-break: break-word; opacity: 0.85; font-style: italic;">Oleh: <strong>${notification.performed_by_name}</strong></div>` : ''}
                        </div>
                    </div>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close" style="width: ${isMobile ? '1rem' : '1.5rem'}; height: ${isMobile ? '1rem' : '1.5rem'};"></button>
                </div>
            </div>
        `;

        notificationContainer.appendChild(toast);

        // Auto remove after 6 seconds
        const timeoutId = setTimeout(() => {
            const bsAlert = new bootstrap.Alert(toast.querySelector('.alert'));
            bsAlert.close();
        }, 6000);

        // Cleanup after fade out
        toast.addEventListener('closed.bs.alert', () => {
            clearTimeout(timeoutId);
            toast.remove();
        });

        // Update container position to ensure visibility
        updateContainerPosition();
    }

    // Initialize Echo and WebSocket connection
    // Check if Echo is available
    let retries = 0;
    const maxRetries = 50; // 5 seconds with 100ms interval

    function initializeWebSocket() {
        if (typeof window.Echo !== 'undefined') {
            // Guard against double initialization if this component is rendered more than once
            // or if scripts are evaluated multiple times.
            if (window.__notificationToastWebSocketInitialized) {
                return;
            }
            window.__notificationToastWebSocketInitialized = true;

            const userId = @json($userId);
            const userRole = @json($userRole);

            if (userRole === 'admin') {
                window.Echo.private('admin')
                    .listen('StudentCreated', (event) => {
                        createToast({
                            type: 'student',
                            action: 'created',
                            message: event.message,
                        });
                    })
                    .listen('StudentUpdated', (event) => {
                        createToast({
                            type: 'student',
                            action: 'updated',
                            message: event.message,
                        });
                    })
                    .listen('StudentDeleted', (event) => {
                        createToast({
                            type: 'student',
                            action: 'deleted',
                            message: event.message,
                        });
                    })
                    .listen('PaymentCreated', (event) => {
                        createToast({
                            type: 'payment',
                            action: 'created',
                            message: event.message,
                        });
                    })
                    .listen('PaymentStatusChanged', (event) => {
                        createToast({
                            type: 'payment',
                            action: 'status_changed',
                            message: event.message,
                        });
                    })
                    .listen('UserCreated', (event) => {
                        createToast({
                            type: 'user',
                            action: 'created',
                            message: event.message,
                        });
                    })
                    .listen('UserUpdated', (event) => {
                        createToast({
                            type: 'user',
                            action: 'updated',
                            message: event.message,
                        });
                    })
                    .listen('UserDeleted', (event) => {
                        createToast({
                            type: 'user',
                            action: 'deleted',
                            message: event.message,
                        });
                    });
            } else if (userId) {
                window.Echo.private(`student.${userId}`)
                    .listen('StudentUpdated', (event) => {
                        createToast({
                            type: 'student',
                            action: 'updated',
                            message: event.message,
                        });
                    })
                    .listen('PaymentCreated', (event) => {
                        createToast({
                            type: 'payment',
                            action: 'created',
                            message: event.message,
                        });
                    })
                    .listen('PaymentStatusChanged', (event) => {
                        createToast({
                            type: 'payment',
                            action: 'status_changed',
                            message: event.message,
                        });
                    });
            }
        } else if (retries < maxRetries) {
            retries++;
            setTimeout(initializeWebSocket, 100);
        }
    }

    // Start initialization after DOM is ready
    document.addEventListener('DOMContentLoaded', initializeWebSocket);
</script>

<style>
    #notification-toast-container {
        display: flex;
        flex-direction: column-reverse;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Mobile responsive toast */
    @media (max-width: 576px) {
        .alert {
            border-radius: 0.375rem !important;
        }

        .alert .btn-close {
            width: 1rem !important;
            height: 1rem !important;
            padding: 0.125rem !important;
        }
    }

    /* Tablet responsive */
    @media (min-width: 577px) and (max-width: 991px) {
        .alert {
            border-radius: 0.4rem !important;
        }
    }

    /* Desktop */
    @media (min-width: 992px) {
        .alert {
            border-radius: 0.5rem !important;
        }
    }
</style>
