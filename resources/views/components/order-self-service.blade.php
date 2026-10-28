@props(['order'])
@if($order->status === 'pending')
<section class="info-block order-self-service">
    <h2>Bạn muốn thay đổi đơn?</h2><p>Bạn có thể sửa thông tin nhận hàng hoặc hủy đơn trước khi cửa hàng xác nhận.</p>
    <a class="btn-outline-dark" href="{{ route('orders.edit-delivery', $order) }}"><x-icon name="user" size="17"/> Sửa thông tin nhận hàng</a>
    <details class="cancel-request" @if($errors->has('cancel_reason')) open @endif>
        <summary>Hủy đơn hàng <x-icon name="chevron-right" size="16"/></summary>
        <form method="POST" action="{{ route('orders.cancel', $order) }}" class="form-stack" data-confirm="Bạn có chắc muốn hủy đơn {{ $order->code }}? Các sản phẩm trong đơn sẽ được hoàn lại tồn kho.">@csrf
            <label><span class="field-label">Lý do hủy <span>*</span></span><textarea name="cancel_reason" rows="2" minlength="3" maxlength="500" required placeholder="Ví dụ: Muốn đổi sang sản phẩm khác...">{{ old('cancel_reason') }}</textarea></label>
            <div><button type="submit" class="btn-danger-soft" data-submit-once>Xác nhận hủy đơn</button></div>
        </form>
    </details>
</section>
@elseif(!in_array($order->status, ['delivered', 'cancelled'], true))
<div class="order-processing-note"><x-icon name="truck" size="20"/><p>Đơn đã được cửa hàng xử lý. Thông tin nhận hàng được giữ nguyên để giao đúng đơn.</p></div>
@endif
