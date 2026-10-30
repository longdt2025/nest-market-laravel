<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureManager
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isManager(), 403, 'Chỉ Quản lý cửa hàng được sử dụng chức năng này.');

        return $next($request);
    }
}
