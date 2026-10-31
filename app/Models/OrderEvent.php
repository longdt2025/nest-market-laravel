<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEvent extends Model
{
    protected $fillable = ['actor_id', 'actor_role', 'type', 'from_status', 'status', 'note'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function getTitleAttribute(): string
    {
        return match ($this->type) {
            'placed' => 'Đã đặt hàng',
            'payment' => 'Cập nhật thanh toán',
            'delivery_updated' => 'Đã cập nhật thông tin nhận hàng',
            'legacy' => 'Bắt đầu theo dõi · '.(Order::LABELS[$this->status] ?? $this->status),
            default => Order::LABELS[$this->status] ?? $this->status,
        };
    }

    public function getActorLabelAttribute(): string
    {
        return match ($this->actor_role) {
            'customer' => 'Khách hàng',
            'admin' => 'Cửa hàng',
            'manager' => 'Quản lý cửa hàng',
            default => 'Hệ thống',
        };
    }
}
