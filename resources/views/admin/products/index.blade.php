@extends('layouts.admin')
@section('title','Sản phẩm')
@section('content')
@php($sortFields=['code'=>'code','name'=>'name','barcode'=>'barcode','unit'=>'unit','category'=>'category','room'=>'room','cost'=>'cost_price','vat'=>'vat_rate','price'=>'price','stock'=>'stock','status'=>'is_active'])
@php($columns=['code'=>'Mã hàng','name'=>'Tên hàng','barcode'=>'Barcode','unit'=>'ĐVT','category'=>'Nhóm hàng','room'=>'Loại hàng','cost'=>'Giá vốn','vat'=>'VAT','price'=>'Giá bán','stock'=>'Tồn kho','status'=>'Trạng thái'])
<section data-product-workspace>
<div class="admin-page-header"><div><h1>Sản phẩm</h1><p>Danh sách hàng hóa và tồn kho của cửa hàng.</p></div></div>
<div class="product-management-layout">
<aside class="product-filter-panel">
    <details open><summary><x-icon name="filter" size="17"/> Bộ lọc sản phẩm</summary>
    <form id="product-filters" action="{{ route('manager.products.index') }}" method="GET" class="product-filter-form">
        <label>Nhóm hàng<select name="category"><option value="">Tất cả nhóm hàng</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category')==$category->id)>{{ $category->name }}</option>@endforeach</select></label>
        <label>Không gian<select name="room"><option value="">Tất cả không gian</option>@foreach(\App\Models\Product::ROOMS as $key=>$label)<option value="{{ $key }}" @selected(request('room')===$key)>{{ $label }}</option>@endforeach</select></label>
        <label>Trạng thái<select name="status"><option value="">Tất cả trạng thái</option><option value="active" @selected(request('status')==='active')>Đang bán</option><option value="hidden" @selected(request('status')==='hidden')>Đã ẩn</option></select></label>
        <label>Tồn kho<select name="inventory"><option value="">Tất cả tồn kho</option><option value="in_stock" @selected(request('inventory')==='in_stock')>Còn hàng</option><option value="low_stock" @selected(request('inventory')==='low_stock')>Sắp hết (1–5)</option><option value="out_stock" @selected(request('inventory')==='out_stock')>Hết hàng</option></select></label>
        <div class="product-price-range"><label>Giá từ<input type="number" min="0" name="min_price" value="{{ request('min_price') }}" placeholder="0"></label><label>Đến<input type="number" min="0" name="max_price" value="{{ request('max_price') }}" placeholder="Không giới hạn"></label></div>
        <button class="admin-primary" type="submit">Áp dụng bộ lọc</button><a class="product-filter-reset" href="{{ route('manager.products.index') }}">Đặt lại bộ lọc</a>
    </form></details>
</aside>
<div class="product-list-area">
    <div class="product-toolbar">
        <div class="catalog-search product-toolbar-search"><x-icon name="search" size="18"/><input form="product-filters" type="search" name="q" value="{{ request('q') }}" placeholder="Tìm theo tên, mã hàng, barcode..." aria-label="Tìm sản phẩm quản lý"><button form="product-filters" type="submit" aria-label="Tìm kiếm sản phẩm"><x-icon name="search" size="17"/></button></div>
        <a class="admin-primary" href="{{ route('manager.products.create') }}"><x-icon name="plus" size="16"/> Thêm mới</a>
        <button class="admin-secondary" type="button" data-open-import><x-icon name="upload" size="16"/> Nhập file</button>
        <button class="admin-secondary" type="button" data-open-export><x-icon name="download" size="16"/> Xuất file</button>
        <details class="product-toolbar-menu"><summary class="admin-secondary"><x-icon name="list" size="16"/> Thao tác</summary><div class="product-menu-items"><button type="button" data-bulk="show" disabled>Cho phép bán sản phẩm đã chọn</button><button type="button" data-bulk="hide" disabled>Ẩn sản phẩm đã chọn</button></div></details>
        <details class="product-toolbar-menu product-column-menu"><summary class="admin-secondary icon-only" aria-label="Tùy chọn cột hiển thị"><x-icon name="columns" size="18"/></summary><div class="product-column-options">@foreach($columns as $key=>$label)<label><input type="checkbox" data-column-toggle="{{ $key }}" @checked(!in_array($key,['category','barcode','cost']))> {{ $label }}</label>@endforeach</div></details>
        <details class="product-toolbar-menu"><summary class="admin-secondary icon-only" aria-label="Sắp xếp sản phẩm"><x-icon name="filter" size="18"/></summary><div class="product-sort-options"><label>Sắp xếp theo<select name="sort" form="product-filters">@foreach(['created_at'=>'Ngày tạo','code'=>'Mã hàng','name'=>'Tên hàng','barcode'=>'Barcode','unit'=>'ĐVT','category'=>'Nhóm hàng','room'=>'Loại hàng','cost_price'=>'Giá vốn','vat_rate'=>'VAT','price'=>'Giá bán','stock'=>'Tồn kho','is_active'=>'Trạng thái'] as $key=>$label)<option value="{{ $key }}" @selected(request('sort','created_at')===$key)>{{ $label }}</option>@endforeach</select></label><label>Thứ tự<select name="direction" form="product-filters"><option value="asc" @selected(request('direction')==='asc')>Tăng dần</option><option value="desc" @selected(request('direction','desc')==='desc')>Giảm dần</option></select></label><button form="product-filters" class="admin-primary">Áp dụng</button></div></details>
    </div>
    <div class="product-list-meta"><span><strong>{{ $products->total() }}</strong> sản phẩm <span data-selected-count></span></span><label>Hiển thị<select name="per_page" form="product-filters" data-page-size>@foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',25)===$size)>{{ $size }}</option>@endforeach</select> dòng</label></div>
    <div class="admin-panel product-table-panel"><div class="table-scroll"><table class="admin-table product-data-table"><thead><tr><th class="product-select-cell"><input type="checkbox" data-select-all aria-label="Chọn tất cả sản phẩm trên trang"></th>
    @foreach($columns as $key=>$label)
        @php($sortField=$sortFields[$key])
        @php($activeSort=request('sort','created_at')===$sortField)
        @php($direction=$activeSort?request('direction','desc'):null)
        @php($nextDirection=$activeSort&&$direction==='asc'?'desc':'asc')
        <th data-col="{{ $key }}" aria-sort="{{ $activeSort?($direction==='asc'?'ascending':'descending'):'none' }}">
            <a class="product-sort-link" href="{{ route('manager.products.index',array_merge(request()->except(['page','sort','direction']),['sort'=>$sortField,'direction'=>$nextDirection])) }}" aria-label="Sắp xếp {{ $label }} {{ $nextDirection==='asc'?'tăng dần':'giảm dần' }}">
                {{ $label }}<svg class="product-sort-arrows" viewBox="0 0 10 16" width="10" height="16" aria-hidden="true"><path d="M5 2 9 7H1Z" class="{{ $activeSort&&$direction==='asc'?'is-active':'' }}"/><path d="m5 14 4-5H1Z" class="{{ $activeSort&&$direction==='desc'?'is-active':'' }}"/></svg>
            </a>
        </th>
    @endforeach<th>Thao tác</th></tr></thead><tbody>
    @forelse($products as $product)<tr data-product-row>
        <td><input type="checkbox" data-product-select value="{{ $product->id }}" aria-label="Chọn {{ $product->name }}"></td>
        <td data-col="code"><a class="table-strong" href="{{ route('manager.products.edit',$product) }}">{{ $product->code }}</a></td>
        <td data-col="name"><div class="table-product"><img src="{{ $product->image_url }}" alt="" onerror="this.onerror=null;this.src='{{ asset('images/product-fallback.svg') }}'"><a href="{{ route('manager.products.edit',$product) }}">{{ $product->name }}</a></div></td>
        <td data-col="barcode">{{ $product->barcode ?? '—' }}</td><td data-col="unit">{{ $product->unit }}</td><td data-col="category">{{ $product->category->name }}</td><td data-col="room">{{ $product->room_label ?: '—' }}</td>
        <td data-col="cost" class="product-number">{{ number_format($product->cost_price,0,',','.') }}₫</td><td data-col="vat">{{ (float)$product->vat_rate }}%</td><td data-col="price" class="table-strong product-number">{{ number_format($product->price,0,',','.') }}₫</td><td data-col="stock" class="product-number {{ $product->stock<=5?'text-danger':'' }}">{{ $product->stock }}</td>
        <td data-col="status"><span class="status {{ $product->is_active?'status-confirmed':'status-cancelled' }}">{{ $product->is_active?'Đang bán':'Đã ẩn' }}</span></td>
        <td><div class="table-actions"><a href="{{ route('manager.products.edit',$product) }}" title="Sửa"><x-icon name="edit" size="17"/></a><form action="{{ route('manager.products.destroy',$product) }}" method="POST" data-confirm="Xóa sản phẩm? Sản phẩm có lịch sử đơn hàng sẽ được ẩn.">@csrf @method('DELETE')<button type="submit" title="Xóa hoặc ẩn"><x-icon name="trash" size="17"/></button></form></div></td>
    </tr>@empty<tr><td colspan="13">Không có sản phẩm phù hợp. Thử điều chỉnh bộ lọc.</td></tr>@endforelse
    </tbody></table></div><div class="admin-pagination">{{ $products->links() }}</div></div>
</div></div>
<form action="{{ route('manager.products.bulk') }}" method="POST" data-bulk-form data-confirm="Cập nhật trạng thái các sản phẩm đã chọn?">@csrf<input name="action" type="hidden"><div data-bulk-ids></div></form>
<dialog class="product-dialog" data-import-dialog aria-labelledby="import-title">
    <div class="product-dialog-heading"><h2 id="import-title">Nhập file Excel</h2><button type="button" data-close-dialog aria-label="Đóng nhập file"><x-icon name="x" size="20"/></button></div>
    <div class="product-import-drop" data-dropzone><x-icon name="upload" size="48"/><p>Kéo thả file Excel vào đây hoặc <label for="product-excel-file">chọn file từ hệ thống</label></p><small data-file-name>Định dạng .xlsx · Tối đa 5 MB / 2.000 dòng</small><input id="product-excel-file" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" data-excel-file></div>
    <div class="product-sheet-field"><label for="product-sheet">Tên sheet dữ liệu <span>*</span></label><select id="product-sheet" data-import-sheet disabled><option value="">--Chọn sheet--</option></select></div>
    <p class="product-import-help">Dùng mã danh mục trong sheet “Danh muc” của file mẫu. Giá bán là giá hiển thị cuối; VAT là thông tin quản lý.</p>
    <section class="product-import-preview" data-import-preview hidden><p data-preview-summary></p><div class="table-scroll"><table class="admin-table"><thead><tr><th>Dòng</th><th>Mã hàng</th><th>Tên hàng</th><th>Giá bán</th><th>Tồn kho</th><th>Thao tác</th><th>Kiểm tra</th></tr></thead><tbody data-preview-rows></tbody></table></div><small>Hiển thị tối đa 100 dòng xem trước. Nếu có dòng lỗi, hệ thống không nhập bất kỳ dòng nào.</small></section>
    <div class="product-dialog-actions"><button class="admin-secondary" type="button" data-close-dialog>Đóng</button><a class="admin-secondary template-download" href="{{ route('manager.products.template') }}">Tải mẫu Excel Import</a><button class="admin-primary" type="button" data-check-import disabled>Kiểm tra dữ liệu</button><button class="admin-primary" type="button" data-commit-import hidden>Nhập dữ liệu</button></div>
</dialog>
<dialog class="product-dialog product-export-dialog" data-export-dialog aria-labelledby="export-title">
<div class="product-dialog-heading"><h2 id="export-title">Tùy chọn xuất dữ liệu</h2><button type="button" data-close-dialog aria-label="Đóng xuất file"><x-icon name="x" size="20"/></button></div>
<form action="{{ route('manager.products.export') }}" method="POST" data-export-form>@csrf
@foreach($filters as $key=>$value)@if($value!==null)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
<div class="product-export-options"><span>Đối tượng xuất</span><label><input type="radio" name="scope" value="filtered" checked> Theo bộ lọc</label><label><input type="radio" name="scope" value="selected" data-export-selected> Theo đối tượng đã chọn <small data-export-count></small></label></div><p class="product-import-help">Xuất tất cả sản phẩm khớp bộ lọc, bao gồm các trang khác. Mỗi lần tối đa 5.000 sản phẩm.</p><div data-export-ids></div>
<div class="product-dialog-actions"><button class="admin-secondary" type="button" data-close-dialog>Đóng</button><button class="admin-primary" type="submit">Xuất</button></div>
</form></dialog>
<div data-transfer-config data-scan="{{ route('manager.products.import.scan') }}" data-preview="{{ route('manager.products.import.preview') }}" data-commit="{{ route('manager.products.import.commit') }}"></div>
</section>
@endsection
@push('scripts')
<script src="{{ asset('js/products-workspace.js') }}?v={{ filemtime(public_path('js/products-workspace.js')) }}" defer></script>
@endpush
