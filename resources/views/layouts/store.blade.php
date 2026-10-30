<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="NEST Market — Nội thất và đồ trang trí cho tổ ấm. Khám phá phòng khách, phòng ngủ, phòng ăn và góc làm việc.">
    <title>@yield('title','NEST Market — Nội thất cho tổ ấm')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fonts/inter/inter.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}?v={{ filemtime(public_path('css/storefront.css')) }}">
<link rel="stylesheet" href="{{ asset('css/workspace.css') }}?v={{ filemtime(public_path('css/workspace.css')) }}">
    @stack('styles')
</head>
<body class="furniture-store">
    <div class="nest-topbar"><span>Không gian đẹp, bắt đầu từ những điều vừa đủ.</span><a href="{{ route('shop.policies') }}#giao-hang">Miễn phí giao hàng từ 800.000₫ <x-icon name="arrow-right" size="13"/></a></div>
    <header class="nest-header">
        <div class="container-wide nest-masthead">
            <button type="button" class="icon-button nest-menu-toggle" data-menu-toggle aria-expanded="false" aria-controls="nest-mobile-menu" aria-label="Mở menu"><x-icon name="menu" size="23"/></button>
            <a href="{{ route('shop.home') }}" class="nest-logo" aria-label="NEST Market Trang chủ">nest<span>.</span><small>NỘI THẤT CHO TỔ ẤM</small></a>
            <form action="{{ route('shop.index') }}" method="GET" class="nest-search" role="search"><input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm sofa, bàn ăn, giường ngủ…" aria-label="Tìm sản phẩm"><button type="submit" aria-label="Tìm kiếm"><x-icon name="search" size="21"/></button></form>
            <div class="nest-account">
                @auth <x-workspace-account/> @else
                <a href="{{ route('login') }}" aria-label="Đăng nhập"><x-icon name="user" size="22"/><span>Đăng nhập</span></a>
                @endauth
            </div>
            <a href="{{ route('cart.index') }}" class="nest-cart" aria-label="Giỏ hàng"><span class="nest-cart-icon"><x-icon name="shopping-bag" size="26"/><b data-cart-count>{{ \App\Support\Cart::count() }}</b></span><span>Giỏ hàng</span></a>
        </div>
        <nav class="container-wide nest-nav" aria-label="Điều hướng chính">
            <a href="{{ route('shop.index') }}" class="{{ request()->routeIs('shop.index','shop.show') && !request('room') ? 'active' : '' }}">Tất cả sản phẩm</a>
            @foreach(\App\Models\Product::ROOMS as $key=>$label)<a href="{{ route('shop.index',['room'=>$key]) }}" class="{{ request('room')===$key ? 'active' : '' }}">{{ $label }}</a>@endforeach
            <a href="{{ route('shop.offers') }}" class="nest-sale-link {{ request()->routeIs('shop.offers') ? 'active' : '' }}">Ưu đãi</a>
            <a href="{{ route('shop.new') }}" class="{{ request()->routeIs('shop.new') ? 'active' : '' }}" @if(request()->routeIs('shop.new')) aria-current="page" @endif>Hàng mới</a>
            <a href="{{ route('shop.policies') }}">Chính sách</a>
        </nav>
        <div class="mobile-panel nest-mobile-panel" id="nest-mobile-menu" data-mobile-panel hidden>
            <a href="{{ route('shop.home') }}">Trang chủ</a><a href="{{ route('shop.index') }}">Tất cả sản phẩm</a>
            @foreach(\App\Models\Product::ROOMS as $key=>$label)<a href="{{ route('shop.index',['room'=>$key]) }}">{{ $label }}</a>@endforeach
            <a href="{{ route('shop.offers') }}">Ưu đãi</a><a href="{{ route('shop.new') }}">Hàng mới</a><a href="{{ route('shop.policies') }}">Giao hàng & bảo hành</a>
            @auth
                @if(auth()->user()->isAdmin())<a href="{{ route('system.dashboard') }}">Trang quản trị</a>@elseif(auth()->user()->isManager())<a href="{{ route('manager.dashboard') }}">Quản lý cửa hàng</a>@else<a href="{{ route('profile.edit') }}">Hồ sơ & địa chỉ</a><a href="{{ route('orders.index') }}">Đơn hàng của tôi</a>@endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="nest-mobile-logout">Đăng xuất</button></form>
            @else<a href="{{ route('login') }}">Đăng nhập / Đăng ký</a>@endauth
        </div>
    </header>
    <main>@yield('content')</main>
    <section class="nest-service-strip"><div class="container-wide"><a href="{{ route('shop.policies') }}#giao-hang"><x-icon name="truck" size="27"/><span><strong>Giao hàng thuận tiện</strong><small>Thông tin chi phí rõ ràng</small></span></a><a href="{{ route('shop.policies') }}#bao-hanh"><x-icon name="shield" size="27"/><span><strong>An tâm lựa chọn</strong><small>Bảo hành theo từng sản phẩm</small></span></a><a href="{{ route('shop.policies') }}#doi-tra"><x-icon name="package" size="27"/><span><strong>Kiểm tra khi nhận</strong><small>Giữ bao bì và chứng từ mua hàng</small></span></a><a href="{{ auth()->check() && auth()->user()->role === 'customer' ? route('orders.index') : route('login') }}"><x-icon name="clock" size="27"/><span><strong>Theo dõi đơn hàng</strong><small>Cập nhật từng bước xử lý</small></span></a></div></section>
    <footer class="nest-footer" id="our-story"><div class="container-wide nest-footer-grid">
        <div><a class="nest-logo" href="{{ route('shop.home') }}">nest<span>.</span></a><h2>NỘI THẤT CHO TỔ ẤM</h2><p>NEST MARKET chọn những món nội thất thiết thực, màu sắc gần gũi và đường nét vừa đủ cho không gian sống mỗi ngày.</p><span class="nest-demo-note">Website đồ án môn PHP · Không giao dịch thương mại thật.</span></div>
        <div><h3>Khám phá NEST</h3>@foreach(\App\Models\Product::ROOMS as $key=>$label)<a href="{{ route('shop.index',['room'=>$key]) }}">{{ $label }}</a>@endforeach</div>
        <div><h3>Hỗ trợ mua hàng</h3><a href="{{ route('shop.policies') }}#giao-hang">Giao hàng & thanh toán</a><a href="{{ route('shop.policies') }}#bao-hanh">Chính sách bảo hành</a><a href="{{ route('shop.policies') }}#doi-tra">Kiểm tra & đổi trả</a><a href="{{ route('shop.offers') }}">Ưu đãi đang có</a><a href="{{ route('shop.new') }}">Hàng mới về</a></div>
        <div><h3>Tài khoản của bạn</h3><a href="{{ route('profile.edit') }}">Hồ sơ & địa chỉ</a><a href="{{ route('orders.index') }}">Theo dõi đơn hàng</a><a href="{{ route('cart.index') }}">Giỏ hàng</a><p>Thanh toán khi nhận hàng (COD).<br>Kiểm tra kích thước và lối vận chuyển trước khi chọn nội thất lớn.</p></div>
    </div><div class="container-wide nest-footer-bottom"><span>© {{ date('Y') }} NEST MARKET.</span><span>Ảnh nội thất minh họa tham khảo <a href="https://moho.com.vn/" target="_blank" rel="noopener noreferrer">MOHO</a> & Unsplash.</span></div></footer>
    <x-notifications /><x-confirm-dialog />
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
    <script src="{{ asset('js/storefront.js') }}?v={{ filemtime(public_path('js/storefront.js')) }}" defer></script>
    @stack('scripts')
<script src="{{ asset('js/workspace.js') }}?v={{ filemtime(public_path('js/workspace.js')) }}" defer></script></body></html>