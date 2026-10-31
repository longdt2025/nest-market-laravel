<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function status(Request $request, Order $order)
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);

        return response()->json(['status' => $order->payment_status, 'label' => $order->payment_label])->header('Cache-Control', 'private, no-store');
    }

    public function report(Request $request, Order $order)
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);
        DB::transaction(function () use ($order, $request) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);
            if ($locked->payment_method !== 'bank_transfer' || $locked->status === 'cancelled') {
                throw ValidationException::withMessages(['payment' => 'Không thể báo chuyển khoản cho đơn hàng này.']);
            }
            if ($locked->payment_status === 'pending_review') {
                return;
            }
            if ($locked->payment_method !== 'bank_transfer' || $locked->status === 'cancelled' || $locked->payment_status !== 'unpaid') {
                throw ValidationException::withMessages(['payment' => 'Không thể báo chuyển khoản cho đơn hàng này.']);
            }
            $locked->update(['payment_status' => 'pending_review']);
            $locked->events()->create(['actor_id' => $request->user()->id, 'actor_role' => 'customer', 'type' => 'payment', 'status' => $locked->status, 'note' => 'Khách báo đã chuyển khoản. Cửa hàng đang kiểm tra tiền nhận được.']);
        });

        return back()->with('success', 'Đã gửi thông báo chuyển khoản. Cửa hàng sẽ kiểm tra và xác nhận.');
    }

    public function verify(Request $request, Order $order)
    {
        $data = $request->validate(['action' => ['required', 'in:confirm,reject,refunded'], 'payment_note' => ['nullable', 'string', 'min:3', 'max:500']]);
        DB::transaction(function () use ($order, $request, $data) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);
            $target = match ($data['action']) {
                'confirm' => 'paid', 'reject' => 'unpaid', 'refunded' => 'refunded'
            };
            if ($locked->payment_status === $target) {
                return;
            }
            $valid = $data['action'] === 'refunded'
                ? $locked->status === 'cancelled' && in_array($locked->payment_status, ['refund_required', 'pending_review'])
                : $locked->status !== 'cancelled' && ($locked->payment_method === 'bank_transfer' ? in_array($locked->payment_status, ['unpaid', 'pending_review']) : $data['action'] === 'confirm' && $locked->status === 'delivered' && $locked->payment_status === 'unpaid');
            if (! $valid) {
                throw ValidationException::withMessages(['payment' => 'Trạng thái thanh toán đã thay đổi hoặc thao tác không hợp lệ.']);
            }
            $locked->update(['payment_status' => $target, 'paid_at' => $target === 'paid' ? now() : $locked->paid_at]);
            $locked->events()->create(['actor_id' => $request->user()->id, 'actor_role' => 'manager', 'type' => 'payment', 'status' => $locked->status, 'note' => Order::PAYMENT_LABELS[$target].': '.($data['payment_note'] ?? match ($data['action']) {
                'confirm' => 'Quản lý xác nhận đã kiểm tra và nhận đủ tiền.', 'reject' => 'Quản lý chưa nhận được tiền, yêu cầu khách kiểm tra giao dịch.', 'refunded' => 'Quản lý xác nhận đã đối soát và hoàn khoản tiền thực nhận.'
            })]);
        });

        return back()->with('success', 'Đã cập nhật kết quả đối soát thanh toán.');
    }
}
