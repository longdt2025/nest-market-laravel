@extends('layouts.store')
@section('title', $newArrivals ? 'Hàng mới về — NEST Market' : ($onSale ? 'Ưu đãi nội thất — NEST Market' : 'Tất cả sản phẩm — NEST Market'))
@section('content')
@php($heading=$newArrivals ? 'Hàng mới về' : ($onSale ? 'Ưu đãi cho tổ ấm' : (\App\Models\Product::ROOMS[$filters['room'] ?? ''] ?? 'Bộ sưu tập NEST')))
<div class="container-wide page-breadcrumb"><a href="{{ route('shop.home') }}">Trang chủ</a><x-icon name="chevron-right" size="14"/><span>{{ $heading }}</span></div>
<section class="container-wide nest-catalog">
    <header class="nest-catalog-heading"><div><span class="nest-kicker">{{ $newArrivals ? 'NEW ARRIVALS' : ($onSale ? 'NEST SPECIAL OFFERS' : 'FURNITURE & EVERYDAY LIVING') }}</span><h1>{{ $heading }}</h1><p>{{ $newArrivals ? 'Những sản phẩm được thêm trong '.\App\Models\Product::NEW_ARRIVAL_DAYS.' ngày gần đây.' : ($onSale ? 'Các sản phẩm đang có giá ưu đãi, cập nhật trực tiếp từ cửa hàng.' : 'Tìm món nội thất phù hợp với không gian, chất liệu và ngân sách của bạn.') }}</p></div><span>{{ $products->total() }} {{ $newArrivals ? 'sản phẩm mới' : 'sản phẩm' }}</span></header>
    <div class="nest-catalog-layout">
        <aside class="nest-filters"><details open><summary><span><x-icon name="filter" size="18"/> Bộ lọc sản phẩm</span><x-icon name="chevron-right" size="17"/></summary>
            <form action="{{ route($catalogRoute) }}" method="GET" class="nest-filter-form">
                @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                <label>Không gian<select name="room"><option value="">Tất cả không gian</option>@foreach(\App\Models\Product::ROOMS as $key=>$label)<option value="{{ $key }}" @selected(request('room')===$key)>{{ $label }}</option>@endforeach</select></label>
                <label>Danh mục<select name="category"><option value="">Tất cả danh mục</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('category')===$category->slug)>{{ $category->name }}</option>@endforeach</select></label>
                <fieldset><legend>Khoảng giá (₫)</legend><div class="nest-price-fields"><input name="min_price" type="number" min="0" max="1000000000" step="1000" value="{{ request('min_price') }}" placeholder="Từ" aria-label="Giá tối thiểu"><span>—</span><input name="max_price" type="number" min="0" max="1000000000" step="1000" value="{{ request('max_price') }}" placeholder="Đến" aria-label="Giá tối đa"></div></fieldset>
                <label>Chất liệu<select name="material"><option value="">Tất cả chất liệu</option>@foreach($materials as $material)<option value="{{ $material }}" @selected(request('material')===$material)>{{ $material }}</option>@endforeach</select></label>
                <label>Màu sắc<select name="color"><option value="">Tất cả màu sắc</option>@foreach($colors as $color)<option value="{{ $color }}" @selected(request('color')===$color)>{{ $color }}</option>@endforeach</select></label>
                <label class="nest-filter-check"><input type="checkbox" name="in_stock" value="1" @checked(request()->boolean('in_stock'))> Chỉ sản phẩm còn hàng</label>
                @unless($onSale)<label class="nest-filter-check"><input type="checkbox" name="on_sale" value="1" @checked(request()->boolean('on_sale'))> Đang có ưu đãi</label>@endunless
                <button type="submit" class="btn-dark">Áp dụng bộ lọc</button><a class="nest-clear-filter" href="{{ route($catalogRoute) }}">Xóa bộ lọc</a>
            </form>
        </details></aside>
        <div class="nest-catalog-results">
            <form class="nest-catalog-toolbar" action="{{ route($catalogRoute) }}" method="GET">
                @foreach($filters as $key=>$value)@unless(in_array($key,['q','sort']))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endunless @endforeach
                <div class="nest-catalog-search"><x-icon name="search" size="18"/><input name="q" value="{{ request('q') }}" placeholder="Tìm trong bộ sưu tập…" aria-label="Tìm tên sản phẩm"><button type="submit">Tìm</button></div>
                <label>Sắp xếp<select name="sort" aria-label="Sắp xếp sản phẩm" onchange="this.form.requestSubmit()"><option value="newest" @selected(request('sort','newest')==='newest')>Mới nhất</option><option value="price_asc" @selected(request('sort')==='price_asc')>Giá thấp đến cao</option><option value="price_desc" @selected(request('sort')==='price_desc')>Giá cao đến thấp</option><option value="name" @selected(request('sort')==='name')>Tên A–Z</option></select></label>
            </form>
            @if(request('q'))<p class="nest-search-result">Kết quả cho “<strong>{{ request('q') }}</strong>”</p>@endif
            @if($products->count())<div class="products-grid nest-product-grid nest-catalog-grid">@foreach($products as $product)<x-product-card :product="$product"/>@endforeach</div><div class="pagination-wrap">{{ $products->links() }}</div>
            @else<div class="empty-state"><x-icon name="search" size="45"/><h3>{{ $newArrivals && count($filters)===0 ? 'Chưa có hàng mới' : 'Chưa tìm thấy sản phẩm' }}</h3><p>Thử thay đổi khoảng giá, chất liệu hoặc từ khóa để tìm lựa chọn phù hợp.</p><a class="btn-dark" href="{{ route($catalogRoute) }}">Xóa bộ lọc</a><a class="nest-clear-filter" href="{{ route('shop.index') }}">Xem tất cả sản phẩm</a></div>@endif
        </div>
    </div>
</section>
@endsection