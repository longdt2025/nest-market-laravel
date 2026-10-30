@extends('layouts.admin')
@section('title','Quản lý tài khoản')
@section('content')
<div class="admin-page-header"><div><h1>Quản lý tài khoản</h1><p>Kiểm soát vai trò và trạng thái truy cập.</p></div><a href="{{ route('system.accounts.create') }}" class="admin-primary"><x-icon name="plus" size="18"/> Tạo tài khoản</a></div>
<div class="admin-panel">
<form class="admin-filters" method="GET">
    <div class="catalog-search"><x-icon name="search" size="18"/><input name="q" value="{{ request('q') }}" placeholder="Tên, email hoặc tài khoản"></div>
    <select name="role" aria-label="Vai trò"><option value="">Tất cả vai trò</option>@foreach(\App\Models\User::ROLES as $key=>$name)<option value="{{ $key }}" @selected(request('role')===$key)>{{ $name }}</option>@endforeach</select>
    <select name="active" aria-label="Trạng thái"><option value="">Tất cả trạng thái</option><option value="1" @selected(request('active')==='1')>Đang hoạt động</option><option value="0" @selected(request('active')==='0')>Đã khóa</option></select>
    <button class="admin-primary">Lọc tài khoản</button>
</form>
<div class="table-scroll"><table class="admin-table"><thead><tr><th>TÀI KHOẢN</th><th>ĐĂNG NHẬP</th><th>VAI TRÒ</th><th>TRẠNG THÁI</th><th>THAO TÁC</th></tr></thead><tbody>
@forelse($accounts as $account)<tr><td><strong>{{ $account->name }}</strong><br><small>#{{ $account->id }}</small></td><td>{{ $account->email }}<br><small>{{ $account->username }}</small></td><td><span class="workspace-role">{{ $account->role_label }}</span></td><td><span class="status {{ $account->is_active?'status-confirmed':'status-cancelled' }}">{{ $account->is_active?'Hoạt động':'Đã khóa' }}</span></td><td><a class="table-detail-link" href="{{ route('system.accounts.edit',$account) }}">Chỉnh sửa <x-icon name="edit" size="16"/></a></td></tr>
@empty<tr><td colspan="5">Không có tài khoản phù hợp.</td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $accounts->links() }}</div></div>
@endsection
