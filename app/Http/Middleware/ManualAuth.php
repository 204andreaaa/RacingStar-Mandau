<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class ManualAuth {
  public function handle(Request $request, Closure $next) {
    if (!$request->session()->has('auth_user')) {
      return redirect()->route('login');
    }
    return $next($request);
  }
}