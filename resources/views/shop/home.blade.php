@extends('layouts.store')
@section('title','NEST Market — Những điều giản dị làm nên tổ ấm')
@section('content')
<section class="hero-wrap container-wide">
    <div class="hero-copy">
        <span class="eyebrow"><span class="line-accent"></span> THE EVERYDAY EDIT / 2026</span>
        <h1>Đẹp từ<br>những điều<br><em>giản dị.</em></h1>
        <p>Đồ dùng được chọn lọc cho từng góc nhà, để mỗi ngày trở nên nhẹ nhàng và đáng yêu hơn một chút.</p>
        <a class="btn-dark btn-arrow" href="{{ route('shop.index') }}">Khám phá bộ sưu tập <x-icon name="arrow-right" size="19"/></a>
        <div class="hero-caption"><span>01 / 03</span><div class="hero-progress"><b></b></div><span>EVERYDAY OBJECTS</span></div>
    </div>
    <div class="hero-visual"><img src="https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1500&q=85" alt="Không gian sống tối giản" onerror="this.onerror=null;this.src='{{ asset('images/product-fallback.svg') }}'"><div class="hero-visual-note"><span>SELECTED FOR LIVING</span><strong>Carefully chosen.<br>Made for you.</strong></div></div>
</section>
<section class="assurance-strip container-wide"><div><x-icon name="truck" size="23"/><span><b>Giao hàng toàn quốc</b><small>Giao tận cửa, dễ dàng nhận hàng</small></span></div><div><x-icon name="shield" size="23"/><span><b>Chọn lọc chất lượng</b><small>Ưu tiên thiết kế có giá trị sử dụng</small></span></div><div><x-icon name="package" size="23"/><span><b>Đóng gói cẩn thận</b><small>Mỗi đơn hàng đều được chăm chút</small></span></div></section>
<section class="section-space container-wide" id="categories"><div class="section-heading"><div><span class="eyebrow">TÌM MỘT ĐIỀU BẠN THÍCH</span><h2>Mua sắm theo danh mục</h2></div><a href="{{ route('shop.index') }}" class="text-link">Xem tất cả <x-icon name="arrow-right" size="18" /></a></div>
    <div class="category-grid">
        @php($categoryPhotos = ['trang-tri'=>'photo-1603006905003-be475563bc59','ban-lam-viec'=>'photo-1497366754035-f200968a6e72','nha-bep'=>'photo-1514228742587-6b1558fcca3d','phu-kien'=>'photo-1544816155-12df9643f363'])
        @foreach($categories as $category)
            <a class="category-tile" href="{{ route('shop.index',['category'=>$category->slug]) }}"><img src="https://images.unsplash.com/{{ $categoryPhotos[$category->slug] ?? 'photo-1494438639946-1ebd1d20bf85' }}?auto=format&fit=crop&w=800&q=80" alt="{{ $category->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-fallback.svg') }}'"><div class="category-overlay"><div><span>{{ str_pad((string)$loop->iteration,2,'0',STR_PAD_LEFT) }} / {{ $category->products_count }} sản phẩm</span><h3>{{ $category->name }}</h3></div><span class="circle-arrow"><x-icon name="arrow-right" size="20"/></span></div></a>
        @endforeach
    </div>
</section>
<section class="section-space container-wide"><div class="section-heading"><div><span class="eyebrow">OUR CURRENT FAVORITES</span><h2>Được yêu thích tại NEST</h2><p>Những lựa chọn mang lại niềm vui nho nhỏ mỗi ngày.</p></div><a class="text-link" href="{{ route('shop.index') }}">Xem bộ sưu tập <x-icon name="arrow-right" size="18"/></a></div>
    <div class="products-grid">@foreach($featured as $product)<x-product-card :product="$product"/>@endforeach</div>
</section>
<section class="container-wide editorial-banner"><div class="editorial-photo"><img src="https://images.unsplash.com/photo-1494438639946-1ebd1d20bf85?auto=format&fit=crop&w=1250&q=85" alt="Góc nhà ấm áp" loading="lazy"></div><div class="editorial-copy"><span class="eyebrow">LESS, BUT BETTER</span><h2>Không cần nhiều.<br>Chỉ cần <em>đúng.</em></h2><p>Mỗi món đồ đều có một chỗ đứng, mỗi góc nhỏ đều có câu chuyện của riêng mình. NEST dành cho những người yêu không gian sống có chủ đích.</p><a class="btn-outline-dark btn-arrow" href="{{ route('shop.index') }}">Chọn món đồ của bạn <x-icon name="arrow-right" size="18"/></a></div></section>
<section class="last-call container-wide"><span>STAY INSPIRED</span><h2>Không gian của bạn.<br><em>Phong cách của bạn.</em></h2><a href="{{ route('shop.index') }}" class="text-link">Bắt đầu khám phá <x-icon name="arrow-right" size="19"/></a></section>
@endsection
