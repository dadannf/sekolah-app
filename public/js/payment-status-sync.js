/**
 * Real-time Payment Status Synchronizer
 * Sinkronisasi status pembayaran antara admin dan siswa secara real-time
 */
class PaymentStatusSynchronizer {
    constructor(options = {}) {
        this.pollingInterval = options.pollingInterval || 5000; // Poll every 5 seconds
        this.paymentIds = options.paymentIds || [];
        this.studentId = options.studentId || null;
        this.year = options.year || new Date().getFullYear();
        this.statusChangeCallback = options.onStatusChange || null;
        this.baseUrl = this.normalizeBaseUrl(options.baseUrl || '/api'); // Base URL for API endpoints
        this.cache = new Map();
        this.pollingTimers = new Map();
        this.failureCounts = new Map();
        this.maxFailuresBeforeStop = Number.isFinite(options.maxFailuresBeforeStop)
            ? options.maxFailuresBeforeStop
            : 3;
        this.isRunning = false;
    }

    normalizeBaseUrl(baseUrl) {
        const raw = String(baseUrl || '').trim() || '/api';
        const withLeadingSlash = raw.startsWith('/') ? raw : `/${raw}`;
        return withLeadingSlash.replace(/\/+$/, '');
    }

    buildUrl(path) {
        return new URL(path, window.location.origin).toString();
    }

    /**
     * Start synchronization for specific payment
     */
    startSyncPayment(paymentId) {
        const numericId = Number(paymentId);
        if (!Number.isFinite(numericId) || numericId <= 0) {
            console.warn('⚠️ Invalid paymentId for sync:', paymentId);
            return;
        }

        if (this.pollingTimers.has(paymentId)) {
            console.log(`Sync already running for payment ${paymentId}`);
            return;
        }

        // Initial fetch
        this.fetchPaymentStatus(numericId);

        // Set up periodic polling
        const timer = setInterval(() => {
            this.fetchPaymentStatus(numericId);
        }, this.pollingInterval);

        this.pollingTimers.set(numericId, timer);
        this.isRunning = true;

        console.log(`🔄 Started real-time sync for payment ${paymentId} (interval: ${this.pollingInterval}ms)`);
    }

    /**
     * Stop synchronization for specific payment
     */
    stopSyncPayment(paymentId) {
        if (this.pollingTimers.has(paymentId)) {
            clearInterval(this.pollingTimers.get(paymentId));
            this.pollingTimers.delete(paymentId);
            console.log(`⏹️ Stopped sync for payment ${paymentId}`);
        }

        this.failureCounts.delete(paymentId);

        if (this.pollingTimers.size === 0) {
            this.isRunning = false;
        }
    }

    /**
     * Start syncing all payments for a student
     */
    startSyncStudentPayments() {
        if (!this.studentId) {
            console.error('❌ studentId not set for startSyncStudentPayments');
            return;
        }

        // Initial fetch
        this.fetchStudentPaymentsSummary();

        // Set up periodic polling
        const timer = setInterval(() => {
            this.fetchStudentPaymentsSummary();
        }, this.pollingInterval);

        this.studentPaymentTimer = timer;
        this.isRunning = true;

        console.log(`🔄 Started real-time sync for all student payments (interval: ${this.pollingInterval}ms)`);
    }

    /**
     * Fetch single payment status from API
     */
    async fetchPaymentStatus(paymentId) {
        try {
            const url = this.buildUrl(`${this.baseUrl}/payment/${paymentId}/status`);
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                const failureCount = (this.failureCounts.get(paymentId) || 0) + 1;
                this.failureCounts.set(paymentId, failureCount);

                // Try to extract JSON message if available
                let message = `HTTP ${response.status}`;
                try {
                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        const errJson = await response.json();
                        if (errJson && errJson.message) message = errJson.message;
                    }
                } catch (_) {
                    // ignore JSON parse failure
                }

                if (failureCount >= this.maxFailuresBeforeStop) {
                    console.warn(`⏹️ Stop sync for payment ${paymentId} after ${failureCount} failures:`, message);
                    this.stopSyncPayment(paymentId);
                } else {
                    console.warn(`⚠️ Payment ${paymentId} status request failed (${failureCount}/${this.maxFailuresBeforeStop}):`, message);
                }
                return;
            }

            // Reset failures on success
            this.failureCounts.delete(paymentId);

            const result = await response.json();

            if (!result.success) {
                const failureCount = (this.failureCounts.get(paymentId) || 0) + 1;
                this.failureCounts.set(paymentId, failureCount);
                const message = result.message || 'Request failed';

                if (failureCount >= this.maxFailuresBeforeStop) {
                    console.warn(`⏹️ Stop sync for payment ${paymentId} after ${failureCount} failures:`, message);
                    this.stopSyncPayment(paymentId);
                } else {
                    console.warn(`⚠️ Payment ${paymentId} status error (${failureCount}/${this.maxFailuresBeforeStop}):`, message);
                }
                return;
            }

            const newData = result.data;
            const oldData = this.cache.get(paymentId);

            // Check if status changed
            if (oldData && oldData.status !== newData.status) {
                console.log(`📢 Payment ${paymentId} status changed: ${oldData.status} → ${newData.status}`);
                this.handleStatusChange(paymentId, newData.status, newData, oldData);
            }

            // Update cache
            this.cache.set(paymentId, newData);

        } catch (error) {
            const failureCount = (this.failureCounts.get(paymentId) || 0) + 1;
            this.failureCounts.set(paymentId, failureCount);

            if (failureCount >= this.maxFailuresBeforeStop) {
                console.warn(`⏹️ Stop sync for payment ${paymentId} after ${failureCount} failures (network):`, error);
                this.stopSyncPayment(paymentId);
            } else {
                console.warn(`⚠️ Error fetching payment status for ${paymentId} (${failureCount}/${this.maxFailuresBeforeStop}):`, error);
            }
        }
    }

    /**
     * Fetch all payments for a student from API
     */
    async fetchStudentPaymentsSummary() {
        try {
            const url = this.buildUrl(`${this.baseUrl}/payments-summary?year=${this.year}`);
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                console.warn(`⚠️ Student payments summary request failed: HTTP ${response.status}`);
                return;
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            const newData = result.data;
            const oldData = this.cache.get('student-summary') || {};

            // Check for status changes
            this.detectPaymentChanges(newData, oldData);

            // Update cache
            this.cache.set('student-summary', newData);

        } catch (error) {
            console.warn('⚠️ Error fetching student payments summary:', error);
        }
    }

    /**
     * Detect changes in payment status
     */
    detectPaymentChanges(newData, oldData) {
        for (const [type, payments] of Object.entries(newData)) {
            if (!oldData[type]) {
                // New payment type added
                for (const [key, payment] of Object.entries(payments)) {
                    console.log(`📢 New ${type} payment: ${payment.status}`);
                    this.handleStatusChange(payment.id, payment.status, payment, null);
                }
                continue;
            }

            // Check existing payments
            for (const [key, payment] of Object.entries(payments)) {
                const oldPayment = oldData[type][key];
                if (oldPayment && oldPayment.status !== payment.status) {
                    console.log(`📢 ${type} ${key} status changed: ${oldPayment.status} → ${payment.status}`);
                    this.handleStatusChange(payment.id, payment.status, payment, oldPayment);
                }
            }
        }
    }

    /**
     * Handle status change event
     */
    handleStatusChange(paymentId, newStatus, newData, oldData) {
        // Trigger callback if provided
        if (this.statusChangeCallback && typeof this.statusChangeCallback === 'function') {
            this.statusChangeCallback({
                paymentId,
                newStatus,
                oldStatus: oldData?.status || 'unknown',
                newData,
                oldData,
                timestamp: Date.now()
            });
        }

        // Show notification
        this.showNotification(newStatus, newData);

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('paymentStatusUpdated', {
            detail: { paymentId, newStatus, newData, oldData }
        }));
    }

    /**
     * Show browser notification for status change
     */
    showNotification(status, paymentData) {
        const type = paymentData.invoice_type || 'Pembayaran';
        let message = '';

        switch (status) {
            case 'verified':
                message = `✅ ${type} Anda telah diverifikasi!`;
                break;
            case 'rejected':
                message = `❌ ${type} Anda ditolak. Silakan upload ulang.`;
                break;
            case 'pending':
                message = `⏳ ${type} dalam proses verifikasi...`;
                break;
            default:
                message = `📋 Status ${type}: ${status}`;
        }

        // Browser notification (jika user allow)
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Update Status Pembayaran', {
                body: message,
                icon: '/images/icon.png'
            });
        }

        // Toast/Alert notification
        this.showToast(message, status);
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Bootstrap Toast
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type === 'verified' ? 'success' : type === 'rejected' ? 'danger' : 'info'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        const container = document.querySelector('.toast-container') || document.body;
        const toastElement = document.createElement('div');
        toastElement.innerHTML = toastHtml;
        container.appendChild(toastElement);

        const toast = new bootstrap.Toast(toastElement.querySelector('.toast'));
        toast.show();

        // Auto remove after 5 seconds
        setTimeout(() => {
            toastElement.remove();
        }, 5000);
    }

    /**
     * Stop all synchronization
     */
    stopAll() {
        // Stop individual payment polling
        this.pollingTimers.forEach((timer) => clearInterval(timer));
        this.pollingTimers.clear();

        // Stop student summary polling
        if (this.studentPaymentTimer) {
            clearInterval(this.studentPaymentTimer);
            this.studentPaymentTimer = null;
        }

        this.isRunning = false;
        console.log('⏹️ Stopped all real-time synchronization');
    }

    /**
     * Request browser notification permission
     */
    static requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PaymentStatusSynchronizer;
}
