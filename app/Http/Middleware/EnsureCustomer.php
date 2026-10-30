<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role === 'customer', 403, 'Tài khoản quản trị không sử dụng chức năng đặt hàng.');

        return $next($request);
    }
}
