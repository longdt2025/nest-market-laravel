@extends('layouts.admin')
@section('title','Tổng quan')
@section('content')
<section class="admin-panel report-toolbar">
    <div><h1>Tổng quan cửa hàng</h1></div>
    <form method="GET" class="report-filter" data-report-filter>
        <label>Chỉ số<select name="metric"><option value="sales" @selected($metric==='sales')>Doanh thu</option><option value="orders" @selected($metric==='orders')>Số đơn đặt</option></select></label>
        <label>Thời gian<select name="period" data-report-period>
            @foreach(['today'=>'Hôm nay','week'=>'7 ngày gần nhất','month'=>'Tháng này','year'=>'Năm nay','custom'=>'Tùy chọn'] as $key=>$name)<option value="{{ $key }}" @selected($period===$key)>{{ $name }}</option>@endforeach
        </select></label>
        <label>Từ ngày<input type="date" name="from" value="{{ $from }}" required data-report-date></label>
        <label>Đến ngày<input type="date" name="to" value="{{ $to }}" required data-report-date></label>
        <button class="admin-primary">Áp dụng</button>
    </form>
</section>
<section class="admin-panel report-panel">
    <div class="report-heading">
        <div><h2>{{ $metric==='sales'?'Biểu đồ doanh thu':'Biểu đồ số đơn đặt' }}</h2><p>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }} · Theo {{ $groupLabel }}</p></div>
        <span class="report-legend"><i></i>{{ $metric==='sales'?'Doanh thu đơn đã giao':'Tất cả đơn được đặt' }}</span>
    </div>
    <p class="report-description">{{ $metric==='sales'?'Doanh thu được nhóm theo ngày đặt, chỉ tính đơn đã giao thành công.':'Số đơn theo ngày đặt, gồm tất cả trạng thái.' }}</p>
    <div class="report-chart-scroll" data-report-chart data-unit="{{ $metric==='sales'?'đ':'đơn' }}" data-series-label="{{ $metric==='sales'?'Doanh thu':'Số đơn đặt' }}">
        <svg class="report-line-chart" viewBox="0 0 1000 370" role="img" aria-labelledby="report-chart-title report-chart-description">
            <title id="report-chart-title">{{ $metric==='sales'?'Doanh thu đơn đã giao':'Số đơn được đặt' }}</title>
            <desc id="report-chart-description">Biểu đồ theo {{ $groupLabel }} trong khoảng {{ $from }} đến {{ $to }}. Di chuyển tới từng điểm để xem số liệu.</desc>
            <defs><clipPath id="report-reveal"><rect class="report-reveal-mask" x="0" y="0" width="1000" height="370"/></clipPath><linearGradient id="report-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#c6683d" stop-opacity=".13"/><stop offset="100%" stop-color="#c6683d" stop-opacity=".01"/></linearGradient></defs>
            @for($tick=0;$tick<=4;$tick++)
                @php($value=$ceiling*$tick/4)
                @php($y=305-$tick*65)
                <line x1="90" x2="970" y1="{{ $y }}" y2="{{ $y }}" class="report-grid-line"/>
                <text x="76" y="{{ $y+4 }}" text-anchor="end" class="report-axis-label">{{ $metric==='orders'?number_format($value,0,',','.'):($value>=1000000?number_format($value/1000000,1,',','.').' tr':number_format($value/1000,0,',','.').' k') }}</text>
            @endfor
            @php($polyline=collect($chartPoints)->map(fn($p)=>$p['x'].','.$p['y'])->implode(' '))
            <g clip-path="url(#report-reveal)">
            <polygon points="{{ $chartPoints[0]['x'] }},305 {{ $polyline }} {{ $chartPoints[count($chartPoints)-1]['x'] }},305" fill="url(#report-area)"/>
            <polyline points="{{ $polyline }}" class="report-data-line"/>
            @foreach($chartPoints as $point)
                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" class="report-data-point" data-report-point data-x="{{ $point['x'] }}" data-y="{{ $point['y'] }}" data-label="{{ $point['tooltip_label'] ?? $point['label'] }}" data-value="{{ $point['value'] }}" tabindex="0" aria-label="{{ $point['label'] }}: {{ number_format($point['value'],0,',','.') }} {{ $metric==='sales'?'đồng':'đơn' }}"><title>{{ $point['label'] }}: {{ number_format($point['value'],0,',','.') }} {{ $metric==='sales'?'đ':'đơn' }}</title></circle>
                @if($monthly || $loop->first || $loop->last || $loop->index % max(1,(int)ceil(count($chartPoints)/12))===0)
                    <text x="{{ $point['x'] }}" y="335" text-anchor="middle" class="report-axis-label">{{ $point['label'] }}</text>
                @endif
            @endforeach
            </g>
            <g class="report-crosshair" hidden pointer-events="none">
                <line data-guide-x y1="45" y2="305"/>
                <line data-guide-y x1="90" x2="970"/>
                <circle data-guide-dot r="6"/>
            </g>
        </svg>
        <div class="report-tooltip" data-report-tooltip role="status" hidden><strong></strong><span></span></div>
    </div>
    @if(collect($chartPoints)->sum('value')===0)<p class="report-no-data">{{ $metric==='sales'?'Chưa có doanh thu từ đơn đã giao':'Chưa có đơn hàng' }} trong khoảng thời gian này.</p>@endif
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/report-chart.js') }}?v={{ filemtime(public_path('js/report-chart.js')) }}" defer></script>
@endpush