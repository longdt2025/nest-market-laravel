@extends('layouts.admin')
@section('title','Tổng quan hệ thống')
@section('content')
<div class="admin-page-header"><div><h1>Quản trị hệ thống</h1></div><a class="admin-primary" href="{{ route('system.accounts.create') }}"><x-icon name="plus" size="18"/> Tạo tài khoản</a></div>
<div class="stats-grid">
    <div class="stat-card"><span>Tổng tài khoản</span><strong>{{ $accountCount }}</strong><small>Đã đăng ký trong hệ thống</small></div>
    <div class="stat-card"><span>Quản lý cửa hàng</span><strong>{{ $managerCount }}</strong><small>Phụ trách hoạt động kinh doanh</small></div>
    <div class="stat-card"><span>Tài khoản bị khóa</span><strong>{{ $lockedCount }}</strong><small>Không được phép đăng nhập</small></div>
    <div class="stat-card"><span>Sao lưu gần nhất</span><strong class="workspace-date">{{ $latestBackup?->created_at->format('d/m H:i') ?? 'Chưa có' }}</strong><small>Lưu trữ riêng tư trên MinIO</small></div>
</div>
<div class="workspace-action-grid">
    <a href="{{ route('system.accounts.index') }}" class="admin-panel workspace-action"><x-icon name="user" size="30"/><h2>Quản lý tài khoản</h2><p>Tạo tài khoản, cập nhật vai trò và khóa/mở quyền truy cập.</p><span>Mở danh sách <x-icon name="arrow-right" size="16"/></span></a>
    <a href="{{ route('system.backups.index') }}" class="admin-panel workspace-action"><x-icon name="shield" size="30"/><h2>Sao lưu & phục hồi</h2><p>Tạo bản sao dữ liệu và ảnh upload, kiểm tra trước khi phục hồi.</p><span>Quản lý bản sao lưu <x-icon name="arrow-right" size="16"/></span></a>
</div>
<div class="workspace-note"><x-icon name="lock" size="20"/><div><strong>Phân tách trách nhiệm</strong><p>Admin quản trị tài khoản và sao lưu. Quản lý cửa hàng phụ trách sản phẩm, danh mục, đơn hàng và báo cáo kinh doanh.</p></div></div>
@endsection
