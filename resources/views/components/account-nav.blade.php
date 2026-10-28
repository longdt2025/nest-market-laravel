<nav class="account-nav" aria-label="Tài khoản của tôi">
    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'current' : '' }}"><x-icon name="user" size="18"/> Hồ sơ & địa chỉ</a>
    <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'current' : '' }}"><x-icon name="package" size="18"/> Đơn hàng của tôi</a>
</nav>
