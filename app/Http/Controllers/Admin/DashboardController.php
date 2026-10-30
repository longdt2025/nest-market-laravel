<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from']]);
        $from = Carbon::parse($input['from'] ?? now()->subDays(13)->toDateString())->startOfDay();
        $to = Carbon::parse($input['to'] ?? now()->toDateString())->endOfDay();
        if ($from->gt($to) || $from->diffInDays($to) > 90) {
            throw ValidationException::withMessages(['from' => 'Chọn khoảng thời gian hợp lệ, tối đa 90 ngày.']);
        }
        $rangeOrders = Order::whereBetween('created_at', [$from, $to])->get();
        $daily = [];
        foreach (CarbonPeriod::create($from->toDateString(), $to->toDateString()) as $day) {
            $daily[] = ['date' => $day->format('d/m'), 'sales' => $rangeOrders->where('status', 'delivered')->filter(fn ($o) => $o->created_at->toDateString() === $day->toDateString())->sum('total')];
        }

        return view('admin.dashboard', [
            'productCount' => Product::count(), 'categoryCount' => Category::count(),
            'customerCount' => User::where('role', 'customer')->count(),
            'pendingCount' => Order::where('status', 'pending')->count(),
            'sales' => $rangeOrders->where('status', 'delivered')->sum('total'),
            'recentOrders' => Order::with('user')->latest()->limit(7)->get(),
            'lowStock' => Product::with('category')->where('is_active', true)->where('stock', '<=', 5)->orderBy('stock')->limit(5)->get(),
            'daily' => $daily, 'maxSales' => max(1, max(array_column($daily, 'sales'))), 'from' => $from->toDateString(), 'to' => $to->toDateString(),
        ]);
    }
}
