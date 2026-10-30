<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next)
    {
        $request->user()?->refresh();
        $stamp = $request->session()->get('auth_stamp');
        $stale = $stamp && $request->user() && (($stamp['id'] ?? null) !== $request->user()->id
            || ! hash_equals($stamp['hash'] ?? '', $request->user()->password)
            || ($stamp['epoch'] ?? 'initial') !== Cache::get('auth_epoch', 'initial'));
        $stale = $stale || (! $stamp && Cache::get('auth_epoch', 'initial') !== 'initial');
        if ($request->user() && (! $request->user()->is_active || $stale)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Phiên đăng nhập đã hết hiệu lực hoặc tài khoản đã bị khóa. Vui lòng đăng nhập lại.']);
        }

        return $next($request);
    }
}
