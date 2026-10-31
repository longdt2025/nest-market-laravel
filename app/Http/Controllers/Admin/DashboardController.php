<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->validate([
            'period' => ['nullable', 'in:today,week,month,year,custom'],
            'metric' => ['nullable', 'in:sales,orders'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $period = $input['period'] ?? ($request->filled('from') || $request->filled('to') ? 'custom' : 'year');
        [$from,$to] = match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [Carbon::parse($input['from'] ?? now()->startOfMonth()->toDateString())->startOfDay(), Carbon::parse($input['to'] ?? now()->toDateString())->endOfDay()],
        };
        if ($from->gt($to) || $from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['from' => 'Chọn khoảng thời gian hợp lệ, tối đa một năm.']);
        }
        $orders = Order::whereBetween('created_at', [$from, $to])->get(['created_at', 'status', 'total']);
        $days = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay());
        $granularity = $period === 'year' ? 'month' : ($days === 0 ? 'hour' : ($days <= 61 ? 'day' : 'month'));
        $monthly = $granularity === 'month';
        $groupLabel = ['hour' => 'giờ', 'day' => 'ngày', 'month' => 'tháng'][$granularity];
        $keyFormat = ['hour' => 'Y-m-d H', 'day' => 'Y-m-d', 'month' => 'Y-m'][$granularity];
        $groups = $orders->groupBy(fn ($order) => $order->created_at->format($keyFormat));
        $metric = $input['metric'] ?? 'sales';
        $points = [];
        $start = $monthly ? $from->copy()->startOfMonth() : $from->copy();
        foreach (CarbonPeriod::create($start, '1 '.$granularity, $to) as $date) {
            $rows = $groups->get($date->format($keyFormat), collect());
            $label = $date->format(['hour' => 'H:i', 'day' => 'd/m', 'month' => 'F'][$granularity]);
            $tooltip = $date->format(['hour' => 'd/m/Y H:i', 'day' => 'd/m/Y', 'month' => 'F Y'][$granularity]);
            $points[] = ['label' => $label, 'tooltip_label' => $tooltip, 'value' => $metric === 'orders' ? $rows->count() : $rows->where('status', 'delivered')->sum('total')];
        }
        $peak = max(1, max(array_column($points, 'value')));
        $step = pow(10, floor(log10($peak)));
        $ceiling = $metric === 'orders' ? max(4, ceil($peak / 4) * 4) : max(100000, ceil($peak / $step) * $step);
        $chartPoints = [];
        foreach ($points as $i => $point) {
            $chartPoints[] = $point + ['x' => count($points) === 1 ? 535 : 90 + $i * 880 / (count($points) - 1), 'y' => 305 - ($point['value'] / $ceiling) * 260];
        }

        return view('admin.dashboard', [
            'from' => $from->toDateString(), 'to' => $to->toDateString(), 'period' => $period, 'metric' => $metric, 'monthly' => $monthly, 'granularity' => $granularity, 'groupLabel' => $groupLabel,
            'chartPoints' => $chartPoints, 'ceiling' => $ceiling,
            'sales' => $orders->where('status', 'delivered')->sum('total'),
        ]);
    }
}
