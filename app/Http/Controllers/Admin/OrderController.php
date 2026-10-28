<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $input = $request->validate(['status' => ['nullable', 'in:pending,confirmed,shipping,delivered,cancelled'], 'q' => ['nullable', 'string', 'max:100']]);
        $orders = Order::with('user')->when($input['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($input['q'] ?? null, fn ($q, $v) => $q->where('code', 'like', '%'.$v.'%'))
            ->latest()->paginate(12)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'user', 'events']);

        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:confirmed,shipping,delivered,cancelled'], 'cancel_reason' => ['nullable', 'string', 'max:500']]);
        $workflow->changeStatus($order, $data['status'], $request->user(), $data['cancel_reason'] ?? null);

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }
}
