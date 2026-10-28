<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeliveryDetailsRequest;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()->withCount('items')->latest()->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);
        $order->load(['items.product', 'events']);

        return view('orders.show', compact('order'));
    }

    public function editDelivery(Request $request, Order $order): View|RedirectResponse
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);
        if ($order->status !== 'pending') {
            return redirect()->route('orders.show', $order)->withErrors(['order' => 'Chỉ có thể sửa đơn đang chờ xác nhận.']);
        }

        return view('orders.edit-delivery', compact('order'));
    }

    public function updateDelivery(DeliveryDetailsRequest $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $workflow->updateDelivery($order, $request->user(), $request->validated());

        return redirect()->route('orders.show', $order)->with('success', 'Đã cập nhật thông tin nhận hàng của đơn này.');
    }

    public function cancel(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);
        $data = $request->validate(['cancel_reason' => ['required', 'string', 'min:3', 'max:500']]);
        $workflow->changeStatus($order, 'cancelled', $request->user(), $data['cancel_reason']);

        return redirect()->route('orders.show', $order)->with('success', 'Đã hủy đơn hàng và hoàn lại tồn kho.');
    }
}
