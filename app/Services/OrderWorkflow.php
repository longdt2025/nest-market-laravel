<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflow
{
    public function changeStatus(Order $order, string $status, User $actor, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $status, $actor, $reason) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $actor->isManager()) {
                abort_unless((int) $locked->user_id === (int) $actor->id, 403);
                if ($status !== 'cancelled' || $locked->status !== 'pending') {
                    throw ValidationException::withMessages(['order' => 'Bạn chỉ có thể hủy đơn đang chờ xác nhận.']);
                }
            }
            if (! in_array($status, Order::NEXT[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Trạng thái đơn đã thay đổi hoặc thao tác không hợp lệ. Vui lòng tải lại trang.']);
            }
            $previous = $locked->status;
            if ($status === 'cancelled') {
                foreach ($locked->items()->orderBy('product_id')->get() as $item) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }
            $locked->update(['status' => $status]);
            $locked->events()->create([
                'actor_id' => $actor->id, 'actor_role' => $actor->role,
                'type' => 'status_changed', 'from_status' => $previous, 'status' => $status,
                'note' => $status === 'cancelled' ? $reason : null,
            ]);
        }, 3);
    }

    public function updateDelivery(Order $order, User $customer, array $data): void
    {
        DB::transaction(function () use ($order, $customer, $data) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            abort_unless((int) $locked->user_id === (int) $customer->id, 403);
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Cửa hàng đã xử lý đơn. Bạn chỉ có thể sửa thông tin khi đơn đang chờ xác nhận.']);
            }
            $locked->fill(Arr::only($data, ['recipient_name', 'phone', 'shipping_address', 'note']));
            if (! $locked->isDirty()) {
                return;
            }
            $locked->save();
            $locked->events()->create([
                'actor_id' => $customer->id, 'actor_role' => 'customer',
                'type' => 'delivery_updated', 'status' => $locked->status,
                'note' => 'Khách hàng cập nhật thông tin nhận hàng trước khi cửa hàng xác nhận.',
            ]);
        }, 3);
    }
}
