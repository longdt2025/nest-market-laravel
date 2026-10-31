@props(['order','manager'=>false])
<section class="bank-payment-panel" aria-label="Thông tin thanh toán"><h3>Thông tin thanh toán</h3><p>{{ $order->payment_method_label }}</p><p><span class="status {{ $order->payment_status==='paid'?'status-delivered':($order->payment_status==='refunded'?'status-cancelled':'status-pending') }}">{{ $order->payment_label }}</span></p>
@if($order->paid_at)<p class="muted-cell">Xác nhận nhận tiền: {{ $order->paid_at->format('H:i d/m/Y') }}</p>@endif
@if($order->payment_method==='bank_transfer')
    @if($order->status!=='cancelled' && !in_array($order->payment_status,['paid','refunded']))
    @php($vietQrUrl=app(\App\Services\VietQr::class)->imageUrl($order))
    @php($fallback=$order->payment_qr_path ? Storage::disk('public')->url($order->payment_qr_path) : null)
    @php($transferReference=$vietQrUrl ? app(\App\Services\VietQr::class)->reference($order) : $order->code)
    @if($vietQrUrl||$fallback)<a href="{{ $vietQrUrl ?? $fallback }}" target="_blank" rel="noopener noreferrer" aria-label="Mở ảnh QR thanh toán"><img class="bank-payment-qr" src="{{ $vietQrUrl ?? $fallback }}" referrerpolicy="no-referrer" @if($vietQrUrl&&$fallback) data-fallback="{{ $fallback }}" onerror="this.onerror=null;this.src=this.dataset.fallback;this.closest('a').href=this.dataset.fallback;this.closest('section').querySelector('[data-qr-fallback-note]').hidden=false" @endif alt="QR chuyển khoản cho đơn {{ $order->code }}"></a>@endif
    <p class="bank-payment-help" data-qr-fallback-note hidden>Không tải được VietQR tự động. Đang hiển thị QR nhận tiền của cửa hàng; vui lòng nhập số tiền và nội dung bên dưới.</p>
    <div class="bank-payment-details"><p><span>Ngân hàng</span><strong>{{ $order->payment_details['bank_name'] ?? '' }}</strong></p><p><span>Số tài khoản</span><strong>{{ $order->payment_details['account_number'] ?? '' }}</strong></p><p><span>Chủ tài khoản</span><strong>{{ $order->payment_details['account_holder'] ?? '' }}</strong></p><p><span>Số tiền</span><strong>{{ number_format($order->total,0,',','.') }}₫</strong></p><p><span>Nội dung chuyển khoản</span><strong>{{ $transferReference }}</strong></p></div>
    <p class="bank-payment-help">{{ $vietQrUrl ? 'Quét VietQR bằng ứng dụng ngân hàng: số tiền và nội dung đã điền sẵn. Kiểm tra người nhận và số tiền trước khi chuyển.' : 'Quét QR bằng ứng dụng ngân hàng. Nhập đúng số tiền và nội dung ở trên trước khi chuyển.' }}</p>
    @if(!$manager && $order->payment_status==='unpaid')<form method="POST" action="{{ route('orders.payment.report',$order) }}">@csrf<button class="btn-dark submit-wide" data-submit-once>Tôi đã chuyển khoản</button></form>@elseif(!$manager)<p class="bank-payment-help">Cửa hàng đang kiểm tra tiền nhận được. Bạn không cần chuyển khoản lại.</p>@endif
    @elseif($order->status==='cancelled' && in_array($order->payment_status,['refund_required','pending_review']))<p class="bank-payment-help">Đơn đã hủy. Cửa hàng cần đối soát khoản chuyển và hoàn lại tiền đã nhận.</p>@endif
@endif
@if($manager)
    @php($canConfirm=$order->status!=='cancelled' && in_array($order->payment_status,['unpaid','pending_review']) && ($order->payment_method==='bank_transfer'||$order->status==='delivered'))
    @php($canRefund=$order->status==='cancelled' && in_array($order->payment_status,['refund_required','pending_review']))
    <div class="order-quick-actions">
    @if($canConfirm)
        <form method="POST" action="{{ route('manager.orders.payment.verify',$order) }}">@csrf<input type="hidden" name="action" value="confirm"><button class="admin-primary" data-submit-once>Xác nhận đã nhận {{ number_format($order->total,0,',','.') }}₫</button></form>
        @if($order->payment_method==='bank_transfer' && $order->payment_status==='pending_review')<form method="POST" action="{{ route('manager.orders.payment.verify',$order) }}">@csrf<input type="hidden" name="action" value="reject"><button class="admin-secondary" data-submit-once>Chưa nhận được tiền</button></form>@endif
    @elseif($canRefund)
        <form method="POST" action="{{ route('manager.orders.payment.verify',$order) }}" data-confirm="Xác nhận bạn đã đối soát và hoàn lại khoản tiền thực nhận của đơn {{ $order->code }}?">@csrf<input type="hidden" name="action" value="refunded"><button class="admin-primary" data-submit-once>Xác nhận đã hoàn tiền</button></form>
    @endif
    </div>
    @if($canConfirm||$canRefund)<small class="muted-cell">Chỉ bấm sau khi đối soát giao dịch thực tế. Hệ thống lưu người xác nhận và thời gian; không tự chuyển hay hoàn tiền.</small>@endif
@endif
</section>
