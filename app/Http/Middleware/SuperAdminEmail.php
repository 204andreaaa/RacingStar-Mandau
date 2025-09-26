<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminEmail
{
    // Ubah ke ENV kalau perlu: env('SUPERADMIN_EMAIL', 'superadmin@mandau.id')
    private string $super = 'superadmin@mandau.id';

    public function handle(Request $request, Closure $next)
    {
        $email = session('auth_user')['email'] ?? null;

        if ($email !== $this->super) {
            // Bisa abort 403, atau redirect dengan flash.
            // return abort(403, 'Forbidden');
            return redirect()
                ->route('admin.home')
                ->with('forbidden', 'Hanya SUPER ADMIN yang boleh mengakses halaman ini.');
        }

        return $next($request);
    }
}