<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['code', 'checkout_token', 'user_id', 'recipient_name', 'phone', 'shipping_address', 'note', 'subtotal', 'shipping_fee', 'total', 'payment_method', 'status', 'payment_status', 'paid_at', 'payment_details', 'payment_qr_path'];

    public const PAYMENT_LABELS = ['unpaid' => 'Chưa xác nhận thanh toán', 'pending_review' => 'Chờ kiểm tra chuyển khoản', 'paid' => 'Đã thanh toán', 'refund_required' => 'Cần hoàn tiền', 'refunded' => 'Đã hoàn tiền'];

    protected function casts(): array
    {
        return ['payment_details' => 'array', 'paid_at' => 'datetime'];
    }

    public function getPaymentLabelAttribute(): string
    {
        return self::PAYMENT_LABELS[$this->payment_status] ?? 'Chưa xác nhận thanh toán';
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return $this->payment_method === 'bank_transfer' ? 'Chuyển khoản ngân hàng' : 'COD — Thanh toán khi nhận hàng';
    }

    public const LABELS = ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'delivered' => 'Đã giao', 'cancelled' => 'Đã hủy'];

    public const NEXT = ['pending' => ['confirmed', 'cancelled'], 'confirmed' => ['shipping', 'cancelled'], 'shipping' => ['delivered'], 'delivered' => [], 'cancelled' => []];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::LABELS[$this->status] ?? $this->status;
    }
}
