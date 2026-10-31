<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Tổng quan') — NEST Workspace</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fonts/inter/inter.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/workspace.css') }}?v={{ filemtime(public_path('css/workspace.css')) }}">
</head>
<body class="admin-body workspace-body">
@php($system=auth()->user()->isAdmin())
<div class="admin-shell">
    <button type="button" class="admin-backdrop" data-admin-backdrop hidden aria-label="Đóng menu quản trị"></button>
    <aside id="admin-navigation" class="admin-sidebar" data-admin-sidebar>
        <button type="button" class="admin-menu-close" data-admin-close aria-label="Đóng menu"><x-icon name="x"/></button>
        <a class="workspace-brand" href="{{ route($system?'system.dashboard':'manager.dashboard') }}">nest<span>.</span><small>{{ $system?'HỆ THỐNG':'QUẢN LÝ' }}</small></a>
        <nav class="admin-nav">
            @if($system)
                <a href="{{ route('system.dashboard') }}" @class(['active'=>request()->routeIs('system.dashboard')])><x-icon name="grid" size="23"/><span>Tổng quan</span></a>
                <a href="{{ route('system.accounts.index') }}" @class(['active'=>request()->routeIs('system.accounts.*')])><x-icon name="user" size="23"/><span>Tài khoản</span></a>
                <a href="{{ route('system.backups.index') }}" @class(['active'=>request()->routeIs('system.backups.*')])><x-icon name="shield" size="23"/><span>Sao lưu / Phục hồi</span></a>
            @else
                <a href="{{ route('manager.dashboard') }}" @class(['active'=>request()->routeIs('manager.dashboard')])><x-icon name="grid" size="23"/><span>Tổng quan</span></a>
                <a href="{{ route('manager.orders.index') }}" @class(['active'=>request()->routeIs('manager.orders.*')])><x-icon name="shopping-bag" size="23"/><span>Đơn hàng</span></a>
                <a href="{{ route('manager.products.index') }}" @class(['active'=>request()->routeIs('manager.products.*')])><x-icon name="package" size="23"/><span>Sản phẩm</span></a>
                <a href="{{ route('manager.categories.index') }}" @class(['active'=>request()->routeIs('manager.categories.*')])><x-icon name="list" size="23"/><span>Danh mục</span></a>
            @endif
        </nav>
        <div class="side-bottom"><a href="{{ route('shop.home') }}"><x-icon name="arrow-right" size="20"/><span>Xem cửa hàng</span></a></div>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="icon-button admin-mobile-toggle" type="button" data-admin-toggle aria-controls="admin-navigation" aria-expanded="false" aria-label="Mở menu"><x-icon name="menu"/></button>
            <div class="workspace-context"><strong>NEST Market</strong><span>{{ $system?'Quản trị hệ thống':'Quản lý cửa hàng' }}</span></div>
            <div class="workspace-header-right"><span class="workspace-role">{{ auth()->user()->role_label }}</span><x-workspace-account/></div>
        </header>
        <div class="admin-page"><div class="workspace-breadcrumb">{{ $system?'Hệ thống':'Cửa hàng' }} <span>/</span> @yield('title','Tổng quan')</div><main class="admin-content">@yield('content')</main></div>
    </div>
</div>
<x-notifications/><x-confirm-dialog/>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
<script src="{{ asset('js/workspace.js') }}?v={{ filemtime(public_path('js/workspace.js')) }}" defer></script>
@stack('scripts')
</body></html>
