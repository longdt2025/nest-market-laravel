@extends('layouts.store')
@section('title','Đơn hàng của tôi — NEST Market')
@section('content')
<section class="container-wide interior-page"><div class="interior-heading"><h1>Đơn hàng <em>của tôi.</em></h1></div>
<x-account-nav />@if($orders->isEmpty())<div class="empty-state"><x-icon name="package" size="50"/><h3>Bạn chưa có đơn hàng</h3><p>Hãy chọn một món đồ thật ưng ý để bắt đầu nhé.</p><a href="{{ route('shop.index') }}" class="btn-dark">Khám phá sản phẩm</a></div>@else<div class="order-list">@foreach($orders as $order)<a href="{{ route('orders.show',$order) }}" class="user-order-card"><div><span class="eyebrow">{{ $order->created_at->format('d/m/Y · H:i') }}</span><h3>{{ $order->code }}</h3><small>{{ $order->items_count }} sản phẩm</small></div><div class="user-order-summary"><span class="status status-{{ $order->status }}">{{ $order->status_label }}</span><strong>{{ number_format($order->total,0,',','.') }}₫</strong></div><x-icon name="chevron-right" size="21"/></a>@endforeach</div><div class="pagination-wrap">{{ $orders->links() }}</div>@endif</section>
@endsection
