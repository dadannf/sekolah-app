<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login
     */
    public function showLogin()
    {
        // Jika sudah login, redirect ke dashboard berdasarkan role
        if (Auth::check()) {
            if (Auth::user()->role === 'siswa') {
                return redirect()->route('student.dashboard');
            }
            return redirect()->route('dashboard');
        }
        
        $hasAdmin = User::whereIn('role', ['admin', 'kepala_sekolah'])->exists();
        $canBootstrapAdmin = !$hasAdmin && (string) config('app.admin_registration_key') !== '';

        return view('auth.login', [
            'canBootstrapAdmin' => $canBootstrapAdmin,
        ]);
    }

    /**
     * Proses login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ], [
            'email.required' => 'Email/NIS wajib diisi',
            'password.required' => 'Password wajib diisi',
        ]);

        $credential = $request->email;
        
        // Cari user berdasarkan email atau NIS
        // Cek apakah input adalah email atau NIS (angka saja)
        if (filter_var($credential, FILTER_VALIDATE_EMAIL)) {
            // Input adalah email format - untuk admin
            $user = User::where('email', $credential)->first();
        } else {
            // Input adalah NIS (untuk siswa) atau username biasa
            $user = User::where('email', $credential)->first();
        }
        
        // Cek jika user ada dan password cocok (MD5 atau Bcrypt)
        if ($user) {
            $password = $request->password;
            $hashedPassword = trim((string) $user->password);
            
            // Cek MD5 (hex 32 char) atau Bcrypt
            if (preg_match('/^[a-f0-9]{32}$/i', $hashedPassword) === 1) {
                // Password di-hash dengan MD5
                $isValid = hash_equals(strtolower($hashedPassword), md5($password));
            } else {
                // Password di-hash dengan Bcrypt
                $isValid = Hash::check($password, $hashedPassword);
            }
            
            if ($isValid) {
                Auth::login($user, $request->has('remember'));
                $request->session()->regenerate();

                $successMessage = 'Selamat datang, ' . Auth::user()->name;

                // Safer redirect logic:
                // - Laravel stores the originally requested URL in session as "url.intended".
                // - If a siswa hits an admin-only URL like /dashboard before login, the intended URL
                //   becomes /dashboard and will cause a 403 after login.
                // - Only honor intended URLs that match the user's allowed area.
                $intended = $request->session()->get('url.intended');
                $intendedPath = '';
                if (is_string($intended) && $intended !== '') {
                    $intendedPath = (string) (parse_url($intended, PHP_URL_PATH) ?? '');
                }

                if ($user->role === 'siswa') {
                    $allowedPrefixes = ['/student', '/notifications', '/api/notifications'];
                    $isAllowed = false;
                    foreach ($allowedPrefixes as $prefix) {
                        if ($intendedPath !== '' && str_starts_with($intendedPath, $prefix)) {
                            $isAllowed = true;
                            break;
                        }
                    }

                    if ($isAllowed && is_string($intended) && $intended !== '') {
                        return redirect()->to($intended)->with('success', $successMessage);
                    }

                    $request->session()->forget('url.intended');
                    return redirect()->route('student.dashboard')->with('success', $successMessage);
                }

                // Admin/Kepala sekolah
                $allowedAdminPrefixes = ['/dashboard', '/information', '/notifications', '/api/notifications'];
                $isAllowedAdmin = false;
                foreach ($allowedAdminPrefixes as $prefix) {
                    if ($intendedPath !== '' && str_starts_with($intendedPath, $prefix)) {
                        $isAllowedAdmin = true;
                        break;
                    }
                }

                if ($isAllowedAdmin && is_string($intended) && $intended !== '') {
                    return redirect()->to($intended)->with('success', $successMessage);
                }

                $request->session()->forget('url.intended');
                return redirect()->route('dashboard')->with('success', $successMessage);
            }
        }

        return back()
            ->withErrors(['email' => 'Email atau password salah'])
            ->withInput($request->only('email'));
    }

    /**
     * Proses logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')
            ->with('success', 'Anda berhasil logout');
    }
}
