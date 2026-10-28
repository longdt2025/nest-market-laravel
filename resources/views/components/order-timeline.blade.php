@props(['order'])
<section class="order-timeline" aria-label="Lịch sử đơn hàng">
    <div class="timeline-heading"><x-icon name="clock" size="21"/><div><h2>Lịch sử đơn hàng</h2><p>Cập nhật theo từng bước xử lý của cửa hàng.</p></div></div>
    <ol>
        @forelse($order->events as $event)
            <li class="timeline-event {{ $loop->last ? 'latest' : '' }} {{ $event->status === 'cancelled' ? 'cancelled' : '' }}">
                <span class="timeline-dot" aria-hidden="true"></span>
                <div><strong>{{ $event->title }}</strong><div class="timeline-meta"><time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('H:i · d/m/Y') }}</time><span>· {{ $event->actor_label }}</span></div>@if($event->note)<p>{{ $event->note }}</p>@endif</div>
            </li>
        @empty
            <li class="timeline-event latest"><span class="timeline-dot" aria-hidden="true"></span><div><strong>Đơn hàng đã được tiếp nhận</strong><p>Chưa có cập nhật trong nhật ký theo dõi.</p></div></li>
        @endforelse
    </ol>
</section>
