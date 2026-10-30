@extends('layouts.admin')
@section('title',$account->exists?'Chỉnh sửa tài khoản':'Tạo tài khoản')
@section('content')
<div class="admin-page-header"><div><h1>{{ $account->exists?'Chỉnh sửa tài khoản':'Tạo tài khoản' }}</h1><p>Cấp quyền theo trách nhiệm của từng người sử dụng.</p></div><a href="{{ route('system.accounts.index') }}" class="admin-secondary">Quay lại</a></div>
<div class="admin-panel form-panel workspace-form">
<form method="POST" action="{{ $account->exists?route('system.accounts.update',$account):route('system.accounts.store') }}" class="form-stack">@csrf @if($account->exists) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-6"><label>Họ tên<input name="name" value="{{ old('name',$account->name) }}" required maxlength="120"></label></div>
<div class="col-md-6"><label>Email<input name="email" type="email" value="{{ old('email',$account->email) }}" required></label></div>
<div class="col-md-6"><label>Tên đăng nhập (tùy chọn)<input name="username" value="{{ old('username',$account->username) }}" maxlength="60" placeholder="Chữ, số, dấu chấm, gạch dưới"></label></div>
<div class="col-md-6"><label>Vai trò<select name="role">@foreach(\App\Models\User::ROLES as $key=>$name)<option value="{{ $key }}" @selected(old('role',$account->role)===$key)>{{ $name }}</option>@endforeach</select></label></div>
<div class="col-md-6"><label>Mật khẩu {{ $account->exists?'mới (để trống nếu giữ nguyên)':'' }}<input type="password" name="password" autocomplete="new-password" minlength="8" @required(!$account->exists)></label></div>
<div class="col-md-6"><label>Xác nhận mật khẩu<input type="password" name="password_confirmation" autocomplete="new-password" @required(!$account->exists)></label></div>
</div>
<label>Trạng thái<select name="is_active"><option value="1" @selected(old('is_active',$account->is_active)==1)>Đang hoạt động</option><option value="0" @selected(old('is_active',$account->is_active)==0)>Khóa tài khoản</option></select></label>
<p class="muted-cell">Không thể tự khóa hoặc gỡ quyền Admin của tài khoản đang đăng nhập. Mật khẩu mới cần ít nhất 8 ký tự gồm chữ và số.</p>
<button class="admin-primary" data-submit-once>Lưu tài khoản</button>
</form></div>
@endsection
