<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminForgotPasswordController extends Controller
{
    public function index()
    {
        $requests = ResetPasswordRequest::with('user', 'approver')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('dashboard.forgot-password.index', compact('requests'));
    }

    public function approve(Request $request, $id)
    {
        $resetRequest = ResetPasswordRequest::findOrFail($id);

        if ($resetRequest->status !== 'Pending') {
            return back()->with('error', 'Permohonan ini sudah diproses.');
        }

        // Cari user yang akan direset
        // Prioritas ke user_id, jika null coba cari dari username
        $user = $resetRequest->user;
        if (!$user) {
            $user = User::where('email', $resetRequest->username)->first();
        }

        if (!$user) {
            return back()->with('error', 'Akun pengguna tidak ditemukan.');
        }

        // Generate temporary password
        $tempPassword = Str::random(8);

        // Update User
        $user->password = Hash::make($tempPassword); // Selalu pakai bcrypt untuk temp password demi keamanan, atau MD5 jika sistem strict MD5 untuk siswa. Kita gunakan Hash bcrypt karena AuthController mendukung fallback hash.
        $user->must_change_password = true;
        $user->save();

        // Update Request
        $resetRequest->status = 'Approved';
        $resetRequest->approved_by = Auth::id();
        $resetRequest->approved_at = now();
        $resetRequest->user_id = $user->id; // set if null
        $resetRequest->save();

        return back()->with('success', 'Permohonan disetujui. Password sementara untuk pengguna ' . $user->name . ' adalah: ' . $tempPassword);
    }

    public function reject(Request $request, $id)
    {
        $resetRequest = ResetPasswordRequest::findOrFail($id);

        if ($resetRequest->status !== 'Pending') {
            return back()->with('error', 'Permohonan ini sudah diproses.');
        }

        $resetRequest->status = 'Rejected';
        $resetRequest->approved_by = Auth::id();
        $resetRequest->approved_at = now();
        $resetRequest->save();

        return back()->with('success', 'Permohonan telah ditolak.');
    }
}
