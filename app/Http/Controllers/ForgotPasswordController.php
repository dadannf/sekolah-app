<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResetPasswordRequest;
use App\Models\User;

class ForgotPasswordController extends Controller
{
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    public function submitRequest(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'full_name' => 'required|string',
            'nis_nip' => 'nullable|string',
            'major' => 'nullable|string',
            'class' => 'nullable|string',
            'reason' => 'required|string|min:10',
        ]);

        // Coba cari user berdasarkan username/nis
        $user = User::where('email', $request->username)->first();

        // Cek jika sudah ada request pending untuk user yang sama (untuk spam prevention)
        if ($user) {
            $existingRequest = ResetPasswordRequest::where('user_id', $user->id)
                ->where('status', 'Pending')
                ->exists();
                
            if ($existingRequest) {
                return back()->with('error', 'Permohonan reset password untuk akun ini sedang diproses. Harap tunggu persetujuan admin.');
            }
        }

        ResetPasswordRequest::create([
            'user_id' => $user ? $user->id : null,
            'username' => $request->username,
            'full_name' => $request->full_name,
            'nis_nip' => $request->nis_nip,
            'reason' => "Kelas: " . ($request->class ?? '-') . " | Jurusan: " . ($request->major ?? '-') . " | Alasan: " . $request->reason,
            'status' => 'Pending',
        ]);

        return redirect()->route('login')->with('success', 'Permohonan reset password berhasil dikirim. Silakan hubungi admin sekolah jika permohonan belum diproses.');
    }
}
