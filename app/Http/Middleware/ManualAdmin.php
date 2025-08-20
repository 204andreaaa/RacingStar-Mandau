<?php

// app/Http/Middleware/ManualAdmin.php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class ManualAdmin {
  public function handle(Request $request, Closure $next) {
    $u = $request->session()->get('auth_user');
    if (!($u['is_admin'] ?? false)) {
      return redirect()->route('teknisi.index');
    }
    return $next($request);
  }
}
