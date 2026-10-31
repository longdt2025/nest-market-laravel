@extends('layouts.store')
@section('title', 'Sửa thông tin nhận hàng — NEST Market')
@section('content')
<section class="container-wide interior-page account-page">
    <div class="interior-heading"><h1>Sửa thông tin <em>nhận hàng.</em></h1></div>
    <form method="POST" action="{{ route('orders.update-delivery', $order) }}" class="info-block form-stack delivery-edit-form">@csrf @method('PATCH')
        <label><span class="field-label">Người nhận <span>*</span></span><input name="recipient_name" value="{{ old('recipient_name', $order->recipient_name) }}" required minlength="2" maxlength="120" autocomplete="shipping name"></label>
        <label><span class="field-label">Số điện thoại <span>*</span></span><input type="tel" name="phone" value="{{ old('phone', $order->phone) }}" required maxlength="20" autocomplete="shipping tel"></label>
        <label><span class="field-label">Địa chỉ nhận hàng <span>*</span></span><textarea name="shipping_address" required minlength="10" maxlength="500" rows="3" autocomplete="shipping street-address">{{ old('shipping_address', $order->shipping_address) }}</textarea></label>
        <label>Ghi chú<textarea name="note" rows="3" maxlength="1000">{{ old('note', $order->note) }}</textarea></label>
        <div class="order-action-buttons"><button type="submit" class="btn-dark" data-submit-once>Lưu thay đổi <x-icon name="check" size="18"/></button><a class="btn-outline-dark" href="{{ route('orders.show', $order) }}">Quay lại đơn hàng</a></div>
    </form>
</section>
@endsection
