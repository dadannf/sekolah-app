<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminRegistrationController extends Controller
{
    /**
     * Halaman registrasi khusus Admin/Kepala Sekolah.
     * Keamanan:
     * - Hanya untuk bootstrap (jika belum ada admin/kepsek sama sekali)
     * - Wajib memasukkan ADMIN_REGISTRATION_KEY
     */
    public function create()
    {
        if ($this->hasAnyAdmin()) {
            return redirect()
                ->route('login');
        }

        if (!$this->registrationKeyConfigured()) {
            return redirect()
                ->route('login')
                ->with('error', 'Registrasi admin belum diaktifkan (ADMIN_REGISTRATION_KEY belum diset).');
        }

        return view('auth.admin_register');
    }

    public function store(Request $request)
    {
        if ($this->hasAnyAdmin()) {
            return redirect()
                ->route('login');
        }

        if (!$this->registrationKeyConfigured()) {
            return redirect()
                ->route('login')
                ->with('error', 'Registrasi admin belum diaktifkan (ADMIN_REGISTRATION_KEY belum diset).');
        }

        $request->validate([
            'registration_key' => ['required', 'string'],
        ], [
            'registration_key.required' => 'Registration key wajib diisi',
        ]);

        if (!$this->registrationKeyValid((string) $request->input('registration_key'))) {
            return back()
                ->withErrors(['registration_key' => 'Registration key salah'])
                ->withInput($request->except(['password', 'password_confirmation', 'registration_key']));
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
            ->route('login')
            ->with('success', 'Akun ' . ucwords(str_replace('_', ' ', $user->role)) . ' berhasil dibuat. Silakan login.');
    }

    private function hasAnyAdmin(): bool
    {
        return User::whereIn('role', ['admin', 'kepala_sekolah'])->exists();
    }

    private function registrationKeyConfigured(): bool
    {
        $key = config('app.admin_registration_key');
        return is_string($key) && $key !== '';
    }

    private function registrationKeyValid(string $input): bool
    {
        $key = (string) config('app.admin_registration_key');
        return hash_equals($key, $input);
    }
}
