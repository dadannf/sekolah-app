@php
    $layout = auth()->user()?->role === 'siswa' ? 'layouts.student' : 'layouts.app';
@endphp

@extends($layout)

@section('title', 'Semua Notifikasi')
@section('page-title', 'Semua Notifikasi')

@section('content')
<div class="container py-3 py-md-4">

    @if ($notifications->count() === 0)
        <div class="alert alert-light border text-muted mb-0">
            Tidak ada notifikasi.
        </div>
    @else
        <form id="bulkDeleteNotificationsForm" action="{{ route('notifications.bulk-delete') }}" method="POST">
            @csrf
            <input type="hidden" id="selectAllNotificationsFlag" name="select_all_notifications" value="false">
            
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-primary fw-semibold" style="border-radius: 10px;">
                        <i class="fas fa-check-square me-1"></i>Pilih Semua
                    </button>
                    <button type="button" id="clearSelectionBtn" class="btn btn-sm btn-outline-secondary fw-semibold" style="border-radius: 10px;">
                        <i class="fas fa-square me-1"></i>Bersihkan
                    </button>
                </div>
                <button type="button" id="bulkDeleteBtn" class="btn btn-sm btn-danger fw-semibold" style="border-radius: 10px;" disabled onclick="submitBulkDeleteNotifications()">
                    <i class="fas fa-trash me-1"></i>Hapus Terpilih
                </button>
            </div>
        
        <div class="list-group mb-3">
            @foreach ($notifications as $notification)
                <div class="list-group-item list-group-item-action" data-notification-id="{{ $notification->id }}">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="d-flex align-items-start gap-2 flex-grow-1" style="min-width: 0;">
                            <div class="d-flex align-items-center mt-1">
                                <input type="checkbox" class="notification-checkbox form-check-input" name="notification_ids[]" value="{{ $notification->id }}" style="cursor: pointer; margin-top: 2px;">
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <strong>{{ $notification->title }}</strong>
                                        @if (!$notification->is_read)
                                            <span class="badge bg-warning text-dark">Baru</span>
                                        @endif
                                        <span class="badge bg-secondary">{{ strtoupper($notification->type) }}</span>
                                        <span class="badge bg-secondary">{{ strtoupper($notification->action) }}</span>
                                    </div>

                                    <button
                                        type="button"
                                        class="btn btn-link btn-sm text-muted p-0 flex-shrink-0"
                                        title="Hapus notifikasi"
                                        onclick="deleteNotificationPage(@json($notification->id), event)"
                                        style="text-decoration: none;"
                                    >
                                        <i class="fas fa-times fs-6"></i>
                                    </button>
                                </div>
                                <div class="text-muted small mt-1">{!! nl2br(e($notification->message)) !!}</div>
                                <div class="text-muted small mt-2">
                                    {{ $notification->created_at?->format('d/m/Y H:i') }}
                                    @if ($notification->performed_by_name)
                                        • Oleh: {{ $notification->performed_by_name }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        </form>

        <div>
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function updateBulkDeleteNotificationsButton() {
        const selectAllFlag = document.getElementById('selectAllNotificationsFlag');
        const isFlagSet = selectAllFlag && selectAllFlag.value === 'true';
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const checkedCount = document.querySelectorAll('.notification-checkbox:checked').length;

        if (bulkDeleteBtn) {
            if (isFlagSet) {
                const totalCount = {{ $notifications->total() }};
                bulkDeleteBtn.disabled = false;
                bulkDeleteBtn.innerHTML = `<i class="fas fa-trash me-1"></i>Hapus Semua (${totalCount})`;
            } else {
                bulkDeleteBtn.disabled = checkedCount === 0;
                bulkDeleteBtn.innerHTML = checkedCount > 0
                    ? `<i class="fas fa-trash me-1"></i>Hapus Terpilih (${checkedCount})`
                    : '<i class="fas fa-trash me-1"></i>Hapus Terpilih';
            }
        }
    }

    function submitBulkDeleteNotifications() {
        const selectAllFlag = document.getElementById('selectAllNotificationsFlag');
        const isFlagSet = selectAllFlag && selectAllFlag.value === 'true';
        const checkedCount = document.querySelectorAll('.notification-checkbox:checked').length;

        if (!isFlagSet && checkedCount === 0) {
            alert('Pilih minimal satu notifikasi untuk dihapus.');
            return;
        }

        let confirmMessage;
        if (isFlagSet) {
            const totalCount = {{ $notifications->total() }};
            confirmMessage = `Apakah Anda yakin ingin menghapus SEMUA ${totalCount} notifikasi? Tindakan ini tidak dapat dibatalkan!`;
        } else {
            confirmMessage = checkedCount === 1
                ? 'Apakah Anda yakin ingin menghapus 1 notifikasi terpilih?'
                : `Apakah Anda yakin ingin menghapus ${checkedCount} notifikasi terpilih?`;
        }

        if (confirm(confirmMessage)) {
            document.getElementById('bulkDeleteNotificationsForm')?.submit();
        }
    }

    function syncSelectAllNotificationsState() {
        const checkboxes = Array.from(document.querySelectorAll('.notification-checkbox'));
        const checkedCount = document.querySelectorAll('.notification-checkbox:checked').length;

        updateBulkDeleteNotificationsButton();
    }

    function toggleAllNotifications(checked) {
        const selectAllFlag = document.getElementById('selectAllNotificationsFlag');
        
        if (checked) {
            if (selectAllFlag) {
                selectAllFlag.value = 'true';
            }
            document.querySelectorAll('.notification-checkbox').forEach((checkbox) => {
                checkbox.checked = true;
            });
        } else {
            if (selectAllFlag) {
                selectAllFlag.value = 'false';
            }
            document.querySelectorAll('.notification-checkbox').forEach((checkbox) => {
                checkbox.checked = false;
            });
        }
        syncSelectAllNotificationsState();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const selectAllBtn = document.getElementById('selectAllBtn');
        const clearSelectionBtn = document.getElementById('clearSelectionBtn');

        document.querySelectorAll('.notification-checkbox').forEach((checkbox) => {
            checkbox.addEventListener('change', syncSelectAllNotificationsState);
        });

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function () {
                toggleAllNotifications(true);
            });
        }

        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', function () {
                toggleAllNotifications(false);
            });
        }

        syncSelectAllNotificationsState();
    });

    window.deleteNotificationPage = async function(notificationId, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        if (!csrfToken) {
            alert('Kesalahan: CSRF token tidak ditemukan. Silakan refresh halaman.');
            return;
        }

        const row = document.querySelector(`[data-notification-id="${notificationId}"]`);
        const btn = row?.querySelector('button[onclick*="deleteNotificationPage"]');
        if (btn) btn.disabled = true;

        try {
            const response = await fetch(`/api/notifications/${notificationId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                alert('Gagal menghapus notifikasi. Status: ' + response.status);
                if (btn) btn.disabled = false;
                return;
            }

            if (row) {
                row.remove();
            }

            // If list becomes empty, show empty state by reloading minimal
            const remaining = document.querySelectorAll('[data-notification-id]').length;
            if (remaining === 0) {
                location.reload();
            }
        } catch (err) {
            console.error(err);
            alert('Terjadi kesalahan saat menghapus notifikasi: ' + err.message);
            if (btn) btn.disabled = false;
        }
    };
</script>
@endpush

@section('scripts')
<script>
    window.deleteNotificationPage = async function(notificationId, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        if (!csrfToken) {
            alert('Kesalahan: CSRF token tidak ditemukan. Silakan refresh halaman.');
            return;
        }

        const row = document.querySelector(`[data-notification-id="${notificationId}"]`);
        const btn = row?.querySelector('button[onclick*="deleteNotificationPage"]');
        if (btn) btn.disabled = true;

        try {
            const response = await fetch(`/api/notifications/${notificationId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                alert('Gagal menghapus notifikasi. Status: ' + response.status);
                if (btn) btn.disabled = false;
                return;
            }

            if (row) {
                row.remove();
            }

            const remaining = document.querySelectorAll('[data-notification-id]').length;
            if (remaining === 0) {
                location.reload();
            }
        } catch (err) {
            console.error(err);
            alert('Terjadi kesalahan saat menghapus notifikasi: ' + err.message);
            if (btn) btn.disabled = false;
        }
    };
</script>
@endsection
