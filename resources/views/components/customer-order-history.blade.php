@props(['order'])
<div class="order-history-control">
<button type="button" class="admin-secondary" onclick="this.parentElement.querySelector('dialog').showModal()"><x-icon name="clock" size="18"/> Lịch sử thao tác <span>({{ $order->events->count() }})</span></button>
<dialog class="order-history-dialog" aria-label="Lịch sử thao tác">
<header><h2>Lịch sử thao tác</h2><button type="button" onclick="this.closest('dialog').close()" aria-label="Đóng lịch sử thao tác"><x-icon name="x" size="22"/></button></header>
<ol>
@forelse($order->events->reverse() as $event)
@php($title=match($event->type) { 'placed'=>'Tạo đơn hàng','delivery_updated'=>'Sửa thông tin nhận hàng','payment'=>'Thanh toán',default=>$event->title })
<li><span class="history-event-icon"><x-icon name="{{ $event->type==='payment'?'shopping-bag':($event->type==='delivery_updated'?'edit':'check') }}" size="17"/></span><div><h3>{{ $title }}</h3><strong class="history-event-meta">{{ $event->created_at->format('d/m/Y H:i:s') }} - {{ $event->actor?->name ?? $event->actor_label }}</strong><small>{{ $event->actor_label }}</small>
@if($event->note)<p>{{ $event->note }}</p>@endif
@if($event->type==='placed')<div class="history-event-items">@foreach($order->items as $item)<p>{{ $item->product_name }} - {{ number_format($item->unit_price,0,',','.') }}₫ × {{ $item->quantity }}</p>@endforeach</div>@endif
</div></li>
@empty<li>Chưa ghi nhận thao tác trên đơn hàng này.</li>@endforelse
</ol></dialog></div>
