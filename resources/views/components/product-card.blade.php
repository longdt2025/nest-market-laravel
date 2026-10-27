@props(['product'])
<article class="product-card">
    <a class="product-image" href="{{ route('shop.show',$product) }}" aria-label="Xem {{ $product->name }}">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-fallback.svg') }}'">
        @if($product->compare_at_price && $product->compare_at_price > $product->price)<span class="product-tag">Ưu đãi</span>@elseif($product->created_at?->gt(now()->subDays(14)))<span class="product-tag muted">Mới</span>@endif
        @if($product->stock === 0)<span class="soldout-overlay">Tạm hết hàng</span>@endif
    </a>
    <div class="product-details"><span class="product-category">{{ $product->category->name }}</span><a class="product-name" href="{{ route('shop.show',$product) }}">{{ $product->name }}</a><div class="product-price"><strong>{{ number_format($product->price,0,',','.') }}₫</strong>@if($product->compare_at_price)<del>{{ number_format($product->compare_at_price,0,',','.') }}₫</del>@endif</div></div>
    <form action="{{ route('cart.store',$product) }}" method="POST" class="quick-add" data-ajax-cart>
        @csrf <input type="hidden" name="quantity" value="1"><button type="submit" {{ $product->stock < 1 ? 'disabled' : '' }} aria-label="Thêm {{ $product->name }} vào giỏ"><x-icon name="plus" size="19"/></button>
    </form>
</article>
