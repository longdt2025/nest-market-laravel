@extends('layouts.store')
@section('title','Tất cả sản phẩm — NEST Market')
@section('content')
<div class="container-wide page-breadcrumb"><a href="{{ route('shop.home') }}">Trang chủ</a><x-icon name="chevron-right" size="14"/><span>Sản phẩm</span></div>
<section class="container-wide catalog-page">
    <header class="catalog-intro"><div><span class="eyebrow">THE SHOP / ALL OBJECTS</span><h1>Bộ sưu tập <em>NEST.</em></h1><p>Chọn lựa những món đồ hợp với nhịp sống của bạn.</p></div><span class="product-count">{{ $products->total() }} sản phẩm</span></header>
    <div class="catalog-layout">
        <aside class="catalog-sidebar"><div class="sidebar-heading"><h3>Danh mục</h3><x-icon name="filter" size="17"/></div><a class="filter-link {{ !request('category') ? 'current' : '' }}" href="{{ route('shop.index',array_filter(['q'=>request('q'),'sort'=>request('sort')])) }}">Tất cả <x-icon name="arrow-right" size="15"/></a>@foreach($categories as $category)<a class="filter-link {{ request('category')===$category->slug ? 'current' : '' }}" href="{{ route('shop.index',array_filter(['category'=>$category->slug,'q'=>request('q'),'sort'=>request('sort')])) }}">{{ $category->name }}<x-icon name="arrow-right" size="15"/></a>@endforeach
            <div class="sidebar-info"><span>NEED A LITTLE INSPIRATION?</span><strong>Chọn ít hơn,<br>yêu nhiều hơn.</strong><p>Đồ dùng thiết thực, thẩm mỹ và dễ phối cho ngôi nhà của bạn.</p></div>
        </aside>
        <div class="catalog-main"><form class="catalog-toolbar" action="{{ route('shop.index') }}" method="GET"><div class="catalog-search"><x-icon name="search" size="18"/><input name="q" value="{{ request('q') }}" placeholder="Bạn đang tìm gì?" aria-label="Tìm tên sản phẩm" /></div><input type="hidden" name="category" value="{{ request('category') }}"><div class="toolbar-right"><label for="sort">Sắp xếp:</label><select id="sort" name="sort" onchange="this.form.submit()"><option value="newest" @selected(request('sort','newest')==='newest')>Mới nhất</option><option value="price_asc" @selected(request('sort')==='price_asc')>Giá thấp đến cao</option><option value="price_desc" @selected(request('sort')==='price_desc')>Giá cao đến thấp</option><option value="name" @selected(request('sort')==='name')>Tên A–Z</option></select><button type="submit" class="search-submit">Tìm</button></div></form>
            @if($products->count())<div class="products-grid catalog-products">@foreach($products as $product)<x-product-card :product="$product"/>@endforeach</div><div class="pagination-wrap">{{ $products->links() }}</div>
            @else<div class="empty-state"><x-icon name="search" size="45"/><h3>Chưa tìm thấy sản phẩm</h3><p>Thử tìm với từ khóa khác hoặc xem tất cả sản phẩm của NEST nhé.</p><a class="btn-dark" href="{{ route('shop.index') }}">Xem tất cả sản phẩm</a></div>@endif
        </div>
    </div>
</section>
@endsection
