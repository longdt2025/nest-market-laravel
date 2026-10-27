<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="NEST Market - Những vật dụng tinh giản, đáng yêu cho không gian sống mỗi ngày.">

    <title>@yield('title', 'NEST Market — Sống có gu, chọn có ý')</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!-- CSS bổ sung cho chức năng đăng xuất -->
    <style>
        .header-logout-form {
            margin: 0;
        }

        .header-logout-button {
            border: none;
            background: transparent;
            padding: 0;
            color: inherit;
            font: inherit;
            cursor: pointer;
        }

        .header-logout-button:hover {
            color: #748975;
        }

        .mobile-logout-form {
            margin: 0;
        }

        .mobile-logout-button {
            display: block;
            width: 100%;
            padding: 13px 6px;
            border: none;
            border-bottom: 1px solid #f1f2ed;
            background: transparent;
            color: inherit;
            font: inherit;
            font-size: 12px;
            text-align: left;
            cursor: pointer;
        }

        .mobile-logout-button:hover {
            color: #748975;
        }

        @media (max-width: 720px) {
            .header-logout-form {
                display: none;
            }
        }
    </style>
</head>

<body class="store-body">

    <!-- THANH THÔNG BÁO -->
    <div class="announcement-bar">
        <span>
            Miễn phí vận chuyển cho đơn từ
            <b>800.000đ</b>
        </span>

        <span class="announcement-right">
            Đổi trả hỗ trợ trong 7 ngày
            <span class="tiny-dot">·</span>
            Chăm chút từng lựa chọn
        </span>
    </div>

    <!-- HEADER -->
    <header class="site-header">
        <div class="container-wide header-main">

            <!-- MENU MOBILE -->
            <button
                type="button"
                class="icon-button mobile-menu-btn"
                aria-label="Mở menu"
                data-menu-toggle
            >
                <x-icon name="menu" size="22" />
            </button>

            <!-- LOGO -->
            <a
                href="{{ route('shop.home') }}"
                class="brand"
aria-label="NEST Market Trang chủ"
            >
                <span class="brand-mark">
                    N<span class="brand-dot">.</span>
                </span>

                <span class="brand-caption">
                    NEST<span> MARKET</span>
                </span>
            </a>

            <!-- MENU DESKTOP -->
            <nav class="desktop-nav" aria-label="Điều hướng chính">

                <a
                    href="{{ route('shop.home') }}"
                    class="{{ request()->routeIs('shop.home') ? 'active' : '' }}"
                >
                    Trang chủ
                </a>

                <a
                    href="{{ route('shop.index') }}"
                    class="{{ request()->routeIs('shop.*') && !request()->routeIs('shop.home') ? 'active' : '' }}"
                >
                    Sản phẩm
                </a>

                <a href="{{ route('shop.index', ['sort' => 'newest']) }}">
                    Hàng mới
                </a>

                <a href="{{ route('shop.home') }}#our-story">
                    Về NEST
                </a>

            </nav>

            <!-- TÌM KIẾM -->
            <form
                action="{{ route('shop.index') }}"
                method="GET"
                class="header-search"
                role="search"
            >
                <x-icon name="search" size="18" />

                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tìm một món đồ bạn thích..."
                    aria-label="Tìm kiếm sản phẩm"
                >

                <button type="submit" aria-label="Tìm kiếm">
                    <x-icon name="arrow-right" size="17" />
                </button>
            </form>

            <!-- TÀI KHOẢN VÀ GIỎ HÀNG -->
            <div class="header-actions">

                @auth

                    @if(auth()->user()->isAdmin())

                        <!-- ADMIN -->
                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="header-text-link"
                        >
                            Quản trị
                        </a>

                    @else

                        <!-- CUSTOMER: ĐƠN HÀNG CỦA TÔI -->
                        <a
                            href="{{ route('orders.index') }}"
                            class="icon-button"
                            title="Đơn hàng của tôi"
                            aria-label="Đơn hàng của tôi"
                        >
                            <x-icon name="user" size="21" />
                        </a>

                    @endif

                    <!-- NÚT ĐĂNG XUẤT DESKTOP -->
                    <form
                        action="{{ route('logout') }}"
method="POST"
                        class="header-logout-form"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="header-text-link header-logout-button"
                            title="Đăng xuất khỏi tài khoản"
                        >
                            Đăng xuất
                        </button>
                    </form>

                @else

                    <!-- KHÁCH CHƯA ĐĂNG NHẬP -->
                    <a
                        href="{{ route('login') }}"
                        class="icon-button"
                        title="Đăng nhập"
                        aria-label="Đăng nhập"
                    >
                        <x-icon name="user" size="21" />
                    </a>

                @endauth

                <!-- GIỎ HÀNG -->
                <a
                    href="{{ route('cart.index') }}"
                    class="icon-button cart-action"
                    title="Giỏ hàng"
                    aria-label="Giỏ hàng"
                >
                    <x-icon name="shopping-bag" size="22" />

                    <span class="cart-badge" data-cart-count>
                        {{ \App\Support\Cart::count() }}
                    </span>
                </a>

            </div>
        </div>

        <!-- MENU MOBILE -->
        <div class="mobile-panel" data-mobile-panel hidden>

            <!-- TÌM KIẾM MOBILE -->
            <form
                action="{{ route('shop.index') }}"
                method="GET"
                class="mobile-search"
            >
                <input
                    name="q"
                    type="search"
                    placeholder="Tìm sản phẩm..."
                    value="{{ request('q') }}"
                >

                <button type="submit">
                    Tìm
                </button>
            </form>

            <a href="{{ route('shop.home') }}">
                Trang chủ
            </a>

            <a href="{{ route('shop.index') }}">
                Tất cả sản phẩm
            </a>

            <!-- DANH MỤC -->
            @foreach($menuCategories as $category)

                <a href="{{ route('shop.index', ['category' => $category->slug]) }}">
                    {{ $category->name }}
                </a>

            @endforeach

            <!-- TÀI KHOẢN MOBILE -->
            @auth

                @if(auth()->user()->isAdmin())

                    <a href="{{ route('admin.dashboard') }}">
                        Trang quản trị
                    </a>

                @else

                    <a href="{{ route('orders.index') }}">
                        Đơn hàng của tôi
                    </a>

                @endif

                <!-- ĐĂNG XUẤT MOBILE -->
                <form
method="POST"
                    action="{{ route('logout') }}"
                    class="mobile-logout-form"
                >
                    @csrf

                    <button
                        type="submit"
                        class="mobile-logout-button"
                    >
                        Đăng xuất
                    </button>
                </form>

            @else

                <a href="{{ route('login') }}">
                    Đăng nhập
                </a>

            @endauth

        </div>
    </header>

    <!-- NỘI DUNG CHÍNH -->
    <main>

        <div class="container-wide flash-region">

            <!-- THÔNG BÁO THÀNH CÔNG -->
            @if(session('success'))

                <div class="notice success">

                    <x-icon name="check" size="18" />

                    {{ session('success') }}

                    <button
                        class="notice-close"
                        type="button"
                        data-dismiss-notice
                        aria-label="Đóng"
                    >
                        ×
                    </button>

                </div>

            @endif

            <!-- THÔNG BÁO LỖI -->
            @if($errors->any())

                <div class="notice error">

                    <span>
                        {{ $errors->first() }}
                    </span>

                    <button
                        class="notice-close"
                        type="button"
                        data-dismiss-notice
                        aria-label="Đóng"
                    >
                        ×
                    </button>

                </div>

            @endif

        </div>

        @yield('content')

    </main>

    <!-- FOOTER -->
    <footer class="site-footer" id="our-story">

        <div class="container-wide footer-top">

            <!-- THƯƠNG HIỆU -->
            <div class="footer-brand-area">

                <a href="{{ route('shop.home') }}" class="footer-logo">
                    NEST<span>.</span>
                </a>

                <p>
                    Những món đồ nhỏ. Một không gian sống rất riêng.
                    Chúng tôi tin vào sự vừa đủ và những lựa chọn bền lâu.
                </p>

            </div>

            <!-- KHÁM PHÁ -->
            <div>

                <h4>Khám phá</h4>

                <a href="{{ route('shop.index') }}">
                    Tất cả sản phẩm
                </a>

                <a href="{{ route('shop.index', ['sort' => 'newest']) }}">
                    Hàng mới về
                </a>

                <a href="{{ route('shop.home') }}#our-story">
                    Câu chuyện NEST
                </a>

            </div>

            <!-- HỖ TRỢ -->
            <div>

                <h4>Hỗ trợ</h4>
<a href="{{ route('cart.index') }}">
                    Giỏ hàng
                </a>

                <a href="{{ auth()->check() && !auth()->user()->isAdmin() ? route('orders.index') : route('login') }}">
                    Theo dõi đơn hàng
                </a>

                <span>
                    Thanh toán khi nhận hàng (COD)
                </span>

            </div>

            <!-- KẾT NỐI -->
            <div>

                <h4>Kết nối</h4>

                <p>
                    Đồ án môn học — website demo.
                    <br>
                    Không thực hiện giao dịch thương mại thật.
                </p>

                <span class="footer-pill">
                    Made with care in Vietnam
                </span>

            </div>

        </div>

        <!-- FOOTER BOTTOM -->
        <div class="container-wide footer-bottom">

            <span>
                © {{ date('Y') }} NEST MARKET.
                All rights reserved.
            </span>

            <span>
                Thiết kế tối giản · Laravel MVC · Đồ án học tập
            </span>

        </div>

    </footer>

    <!-- TOAST NOTIFICATION -->
    <div
        id="toast"
        class="site-toast"
        role="status"
        aria-live="polite"
    ></div>

    <!-- JAVASCRIPT -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    @stack('scripts')

</body>
</html>
