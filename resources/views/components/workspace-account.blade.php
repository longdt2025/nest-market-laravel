<details class="workspace-account" data-account-menu>
    <summary><span class="workspace-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</span><span>{{ auth()->user()->name }}</span><x-icon name="chevron-right" size="14"/></summary>
    <div class="workspace-account-menu">
        <div class="account-menu-heading"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role_label }}</small></div>
        <a href="{{ route('staff.account') }}#password"><x-icon name="lock" size="17"/> Đổi mật khẩu</a>
        <a href="{{ auth()->user()->role === 'customer' ? route('profile.edit') : route('staff.account') }}"><x-icon name="user" size="17"/> Cập nhật thông tin</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-icon name="logout" size="17"/> Đăng xuất</button></form>
    </div>
</details>
