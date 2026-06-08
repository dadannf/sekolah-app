<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            // Biarkan user mengakses route ganti password atau logout
            $allowedRoutes = [
                'password.edit',
                'password.update',
                'student.password.edit',
                'student.password.update',
                'logout'
            ];

            if (!$request->routeIs($allowedRoutes)) {
                // Redirect ke halaman ganti password sesuai role
                if (Auth::user()->role === 'siswa') {
                    return redirect()->route('student.password.edit')->with('warning', 'Anda diwajibkan untuk mengganti password sementara Anda.');
                }
                
                return redirect()->route('password.edit')->with('warning', 'Anda diwajibkan untuk mengganti password sementara Anda.');
            }
        }

        return $next($request);
    }
}
