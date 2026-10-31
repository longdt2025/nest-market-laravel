@extends('layouts.admin')
@section('title','Hóa đơn / Đơn hàng')
@section('content')
@php($columns=['code'=>'Thông tin đơn hàng','created_at'=>'Thời gian tạo','status'=>'Trạng thái','recipient_name'=>'Khách hàng','total'=>'Tổng tiền','shipping_fee'=>'Phí giao hàng','payment'=>'Thanh toán','address'=>'Địa chỉ giao hàng'])
<section data-order-workspace>
<div class="admin-page-header"><div><h1>Hóa đơn / Đơn hàng</h1></div></div>
<div class="product-toolbar order-toolbar">
<div class="catalog-search product-toolbar-search"><x-icon name="search" size="18"/><input form="order-filters" type="search" name="q" value="{{ request('q') }}" placeholder="Mã đơn, tên khách, điện thoại, email..." aria-label="Tìm đơn hàng"><button form="order-filters" aria-label="Tìm kiếm đơn hàng"><x-icon name="search" size="17"/></button></div>
<button class="admin-secondary" type="button" data-order-export><x-icon name="download" size="17"/> Xuất file</button>
<a class="admin-secondary icon-only" href="{{ request()->fullUrl() }}" aria-label="Làm mới danh sách">↻</a>
<details class="product-toolbar-menu"><summary class="admin-secondary icon-only" aria-label="Tùy chọn cột đơn hàng"><x-icon name="columns" size="18"/></summary><div class="product-column-options">@foreach($columns as $key=>$label)<label><input type="checkbox" data-order-column="{{ $key }}" @checked(!in_array($key,['address','shipping_fee']))> {{ $label }}</label>@endforeach</div></details>
</div>
<div class="product-management-layout order-management-layout">
<aside class="product-filter-panel"><details open><summary><x-icon name="filter" size="17"/> Bộ lọc đơn hàng</summary>
<form id="order-filters" method="GET" action="{{ route('manager.orders.index') }}" class="product-filter-form">
<fieldset class="order-filter-group"><legend>Thời gian tạo</legend><label>Từ ngày<input type="date" name="from" value="{{ request('from') }}"></label><label>Đến ngày<input type="date" name="to" value="{{ request('to') }}"></label></fieldset>
<fieldset class="order-filter-group"><legend>Trạng thái</legend><label class="order-status-option"><input type="radio" name="status" value="" @checked(!request('status'))> Tất cả <span>{{ $statusCounts->sum() }}</span></label>@foreach(\App\Models\Order::LABELS as $key=>$label)<label class="order-status-option"><input type="radio" name="status" value="{{ $key }}" @checked(request('status')===$key)> {{ $label }} <span>{{ $statusCounts[$key] ?? 0 }}</span></label>@endforeach</fieldset>
<fieldset class="order-filter-group"><legend>Giá trị đơn (₫)</legend><label>Từ<input type="number" min="0" name="min_total" value="{{ request('min_total') }}" placeholder="0"></label><label>Đến<input type="number" min="0" name="max_total" value="{{ request('max_total') }}" placeholder="Không giới hạn"></label></fieldset>
<input type="hidden" name="sort" value="{{ request('sort','created_at') }}"><input type="hidden" name="direction" value="{{ request('direction','desc') }}">
<button class="admin-primary">Áp dụng bộ lọc</button><a class="product-filter-reset" href="{{ route('manager.orders.index') }}">Đặt lại bộ lọc</a>
</form></details></aside>
<div class="product-list-area">
<div class="order-summary-grid">
<div><span>Giá trị đơn theo bộ lọc</span><strong>{{ number_format($summary->total_value,0,',','.') }}₫</strong><small>{{ $summary->orders_count }} đơn hàng · gồm đơn đã hủy</small></div>
<div><span>Doanh thu đã giao</span><strong class="order-positive">{{ number_format($summary->delivered_value,0,',','.') }}₫</strong><small>Đơn hàng đã giao thành công</small></div>
<div><span>Giá trị đang xử lý</span><strong class="order-processing">{{ number_format($summary->processing_value,0,',','.') }}₫</strong><small>Chờ xác nhận / xác nhận / đang giao</small></div>
</div>
<div class="product-list-meta"><span><strong>{{ $orders->total() }}</strong> đơn hàng <span data-order-selected-count></span></span><label>Hiển thị<select form="order-filters" name="per_page" data-order-page-size>@foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',25)===$size)>{{ $size }}</option>@endforeach</select> dòng</label></div>
<div class="admin-panel product-table-panel"><div class="table-scroll"><table class="admin-table product-data-table order-data-table"><thead><tr><th><input type="checkbox" data-order-all aria-label="Chọn tất cả đơn trên trang"></th>
@foreach($columns as $key=>$label)
<th data-order-col="{{ $key }}" @if(in_array($key,\App\Services\OrderCatalogQuery::SORTS)) aria-sort="{{ request('sort','created_at')===$key?(request('direction','desc')==='asc'?'ascending':'descending'):'none' }}" @endif>
@if(in_array($key,\App\Services\OrderCatalogQuery::SORTS))
@php($active=request('sort','created_at')===$key)
@php($next=$active&&request('direction','desc')==='asc'?'desc':'asc')
<a class="product-sort-link" href="{{ route('manager.orders.index',array_merge(request()->except(['page','sort','direction']),['sort'=>$key,'direction'=>$next])) }}" aria-label="Sắp xếp {{ $label }} {{ $next==='asc'?'tăng dần':'giảm dần' }}">{{ $label }}<svg class="product-sort-arrows" width="10" height="16" viewBox="0 0 10 16" aria-hidden="true"><path d="M5 2 9 7H1Z" class="{{ $active&&request('direction','desc')==='asc'?'is-active':'' }}"/><path d="m5 14 4-5H1Z" class="{{ $active&&request('direction','desc')==='desc'?'is-active':'' }}"/></svg></a>
@else {{ $label }} @endif</th>@endforeach<th></th></tr></thead><tbody>
@forelse($orders as $order)<tr>
<td><input type="checkbox" data-order-select value="{{ $order->id }}" aria-label="Chọn đơn {{ $order->code }}"></td>
<td data-order-col="code"><a class="table-strong order-code-link" data-order-detail href="{{ route('manager.orders.show',$order) }}">{{ $order->code }}</a><small class="order-cell-sub">Website · {{ $order->items_count }} mặt hàng</small></td>
<td data-order-col="created_at">{{ $order->created_at->format('d/m/Y') }}<small class="order-cell-sub">{{ $order->created_at->format('H:i') }}</small></td>
<td data-order-col="status"><span class="status status-{{ $order->status }}">{{ $order->status_label }}</span></td>
<td data-order-col="recipient_name"><strong>{{ $order->recipient_name }}</strong><small class="order-cell-sub">{{ $order->phone }}</small></td>
<td data-order-col="total" class="table-strong product-number">{{ number_format($order->total,0,',','.') }}₫</td><td data-order-col="shipping_fee" class="product-number">{{ number_format($order->shipping_fee,0,',','.') }}₫</td>
<td data-order-col="payment">{{ $order->payment_method_label }}<small class="order-cell-sub">{{ $order->payment_label }}</small></td><td data-order-col="address" class="order-address-cell">{{ $order->shipping_address }}</td>
<td><a class="table-detail-link" data-order-detail href="{{ route('manager.orders.show',$order) }}" aria-label="Xem đơn {{ $order->code }}"><x-icon name="arrow-right" size="17"/></a></td>
</tr>@empty<tr><td colspan="10"><div class="order-empty"><x-icon name="package" size="32"/><strong>Không tìm thấy đơn hàng</strong><span>Thử thay đổi từ khóa hoặc khoảng thời gian.</span></div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $orders->links() }}</div></div>
</div></div>
<dialog class="product-dialog product-export-dialog" data-order-export-dialog aria-labelledby="order-export-title"><div class="product-dialog-heading"><h2 id="order-export-title">Tùy chọn xuất dữ liệu</h2><button type="button" data-order-close aria-label="Đóng xuất đơn"><x-icon name="x" size="20"/></button></div>
<form action="{{ route('manager.orders.export') }}" method="POST" data-order-export-form>@csrf
@foreach($filters as $key=>$value)@if($value!==null)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
<div class="product-export-options"><span>Đối tượng xuất</span><label><input type="radio" name="scope" value="filtered" checked> Theo bộ lọc</label><label><input type="radio" name="scope" value="selected" data-order-selected-scope> Theo đơn đã chọn <small data-order-export-count></small></label></div><p class="product-import-help">Xuất Excel tối đa 5.000 đơn theo bộ lọc hoặc 500 đơn được chọn trên trang.</p><div data-order-export-ids></div><div class="product-dialog-actions"><button class="admin-secondary" type="button" data-order-close>Đóng</button><button class="admin-primary">Xuất Excel</button></div></form></dialog>
<dialog class="order-detail-drawer" data-order-drawer aria-label="Chi tiết đơn hàng"><div class="order-drawer-bar"><span>CHI TIẾT ĐƠN HÀNG</span><button type="button" data-order-close aria-label="Đóng chi tiết đơn"><x-icon name="x" size="21"/></button></div><div data-order-content aria-live="polite"></div></dialog>
</section>
@endsection
@push('scripts')<script src="{{ asset('js/orders-workspace.js') }}?v={{ filemtime(public_path('js/orders-workspace.js')) }}" defer></script>@endpush
