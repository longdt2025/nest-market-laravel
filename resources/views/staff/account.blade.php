@extends(auth()->user()->role === 'customer' ? 'layouts.store' : 'layouts.admin')
@section('title','Thông tin tài khoản')
@section('content')
<section class="{{ auth()->user()->role === 'customer'?'container-wide interior-page':'' }}">
<div class="admin-page-header"><div><h1>Tài khoản của tôi</h1><p>Quản lý thông tin cá nhân và bảo mật đăng nhập.</p></div></div>
<div class="admin-form-grid">
    <div class="admin-panel form-panel">
        <h2>Cập nhật thông tin</h2><p class="muted-cell">{{ $account->role_label }} · {{ $account->email }}</p>
        <form method="POST" action="{{ route('staff.account.update') }}" class="form-stack mt-4">@csrf @method('PATCH')
            <label>Họ tên<input name="name" value="{{ old('name',$account->name) }}" required maxlength="120"></label>
            <label>Số điện thoại<input name="phone" value="{{ old('phone',$account->phone) }}" type="tel"></label>
            <button class="admin-primary">Lưu thông tin</button>
        </form>
    </div>
    <div class="admin-panel form-panel" id="password">
        <h2>Đổi mật khẩu</h2><p class="muted-cell">Sau khi đổi mật khẩu, bạn cần đăng nhập lại.</p>
        <form method="POST" action="{{ route('staff.password') }}" class="form-stack mt-4">@csrf
            <label>Mật khẩu hiện tại<input name="current_password" type="password" autocomplete="current-password" required></label>
            <label>Mật khẩu mới<input name="password" type="password" autocomplete="new-password" minlength="8" required></label>
            <label>Xác nhận mật khẩu mới<input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></label>
            <small>Tối thiểu 8 ký tự, gồm chữ và số.</small>
            <button class="admin-primary">Đổi mật khẩu</button>
        </form>
    </div>
</div></section>
@endsection
