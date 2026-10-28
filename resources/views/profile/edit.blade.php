@extends('layouts.store')
@section('title', 'Tài khoản của tôi — NEST Market')
@section('content')
<section class="container-wide interior-page account-page">
    <div class="interior-heading"><span class="eyebrow">MY ACCOUNT / PROFILE</span><h1>Tài khoản <em>của tôi.</em></h1><p>Lưu thông tin để lần mua tiếp theo thuận tiện hơn.</p></div>
    <x-account-nav />
    <div class="profile-layout">
        <form method="POST" action="{{ route('profile.update') }}" class="info-block form-stack">@csrf @method('PATCH')
            <h2>Hồ sơ & địa chỉ mặc định</h2>
            <label><span class="field-label">Tên tài khoản <span>*</span></span><input name="name" value="{{ old('name', $customer->name) }}" required minlength="2" maxlength="120" autocomplete="name"></label>
            <label>Email đăng nhập<input type="email" value="{{ $customer->email }}" readonly aria-readonly="true"></label>
            <div class="form-section-title"><x-icon name="truck" size="20"/><div><strong>Thông tin nhận hàng</strong><small>Tự điền ở trang thanh toán. Bạn vẫn có thể đổi riêng cho từng đơn.</small></div></div>
            <label>Tên người nhận mặc định<input name="delivery_name" value="{{ old('delivery_name', $customer->delivery_name ?? $customer->name) }}" minlength="2" maxlength="120" autocomplete="shipping name"></label>
            <label>Số điện thoại<input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="20" autocomplete="shipping tel" placeholder="09xxxxxxxx"></label>
            <label>Địa chỉ nhận hàng<textarea name="shipping_address" rows="3" minlength="10" maxlength="500" autocomplete="shipping street-address" placeholder="Số nhà, đường, phường/xã, tỉnh/thành phố">{{ old('shipping_address', $customer->shipping_address) }}</textarea></label>
            <div><button class="btn-dark" type="submit" data-submit-once>Lưu thông tin <x-icon name="check" size="18"/></button></div>
        </form>
        <aside class="account-help"><x-icon name="shield" size="27"/><h2>Thông tin của bạn</h2><p>Địa chỉ mặc định chỉ dùng cho lần đặt hàng tiếp theo. Các đơn đã đặt giữ nguyên thông tin nhận hàng của từng đơn.</p><a class="text-link" href="{{ route('orders.index') }}">Theo dõi đơn hàng <x-icon name="arrow-right" size="17"/></a></aside>
    </div>
</section>
@endsection
