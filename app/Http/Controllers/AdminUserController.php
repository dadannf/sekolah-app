<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Form pendaftaran akun khusus Admin/Kepala Sekolah.
     * Akses sudah dibatasi oleh middleware route: auth + role:admin.
     */
    public function create()
    {
        if (!Auth::check() || Auth::user()->role !== 'kepala_sekolah') {
            abort(403, 'Unauthorized access');
        }
        return view('admin.users.create');
    }

    /**
     * Simpan akun admin/kepala sekolah baru.
     */
    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'kepala_sekolah') {
            abort(403, 'Unauthorized access');
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'kepala_sekolah'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'role.required' => 'Role wajib dipilih',
            'role.in' => 'Role tidak valid',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak sama',
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()
            ->route('admin.users.create')
            ->with('success', 'Akun ' . ucwords(str_replace('_', ' ', $user->role)) . ' berhasil dibuat untuk ' . $user->name);
    }
}
