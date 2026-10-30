<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SerializeDataWrites
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }
        try {
            return Cache::lock('nest-data-write', 300)->block(8, fn () => $next($request));
        } catch (LockTimeoutException $e) {
            return back()->withErrors(['system' => 'Hệ thống đang sao lưu/phục hồi hoặc xử lý dữ liệu. Vui lòng thử lại sau.']);
        }
    }
}
