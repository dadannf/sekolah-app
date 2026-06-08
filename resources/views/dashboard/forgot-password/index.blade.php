@extends('layouts.app')

@section('title', 'Permohonan Reset Password')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Permohonan Reset Password</h2>
            <p class="text-muted mb-0">Daftar permohonan reset password dari pengguna</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">NIS/Email (Input)</th>
                            <th class="px-4 py-3">Nama Pemohon</th>
                            <th class="px-4 py-3">Alasan / Detail</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td class="px-4 py-3">{{ $req->created_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span class="fw-medium">{{ $req->username }}</span><br>
                                    @if($req->nis_nip)
                                        <small class="text-muted">NIS/NIP: {{ $req->nis_nip }}</small>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $req->full_name }}</td>
                                <td class="px-4 py-3" style="max-width: 250px;">
                                    <p class="mb-0 text-truncate" title="{{ $req->reason }}">{{ $req->reason }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    @if($req->status === 'Pending')
                                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Pending</span>
                                    @elseif($req->status === 'Approved')
                                        <span class="badge bg-success px-3 py-2 rounded-pill">Approved</span><br>
                                        <small class="text-muted">Oleh: {{ $req->approver->name ?? '-' }}</small>
                                    @else
                                        <span class="badge bg-danger px-3 py-2 rounded-pill">Rejected</span><br>
                                        <small class="text-muted">Oleh: {{ $req->approver->name ?? '-' }}</small>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end">
                                    @if($req->status === 'Pending')
                                        <div class="d-flex justify-content-end gap-2">
                                            <form action="{{ route('admin.forgot-password.approve', $req->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menyetujui dan membuat password sementara?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.forgot-password.reject', $req->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menolak permohonan ini?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada permohonan reset password.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
                <div class="p-3 border-top">
                    {{ $requests->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
