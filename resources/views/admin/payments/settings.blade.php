@extends('layouts.admin')
@section('title','Cài đặt thanh toán')
@section('content')
<div class="admin-page-header"><div><h1>Thanh toán chuyển khoản</h1></div></div>
<div class="admin-form-grid"><div class="admin-panel form-panel"><h2>Thông tin nhận tiền</h2><form action="{{ route('manager.payments.settings.update') }}" method="POST" enctype="multipart/form-data" class="form-stack mt-3">@csrf @method('PUT')
<label>Ngân hàng<input name="bank_name" value="{{ old('bank_name',$setting?->bank_name) }}" maxlength="120" required placeholder="Ví dụ: Vietcombank"></label>
<label>Mã BIN ngân hàng (VietQR)<input name="bank_bin" value="{{ old('bank_bin',$setting?->bank_bin) }}" pattern="[0-9]{6}" maxlength="6" inputmode="numeric" placeholder="BIDV: 970418"></label><small class="muted-cell">Điền mã BIN để tạo VietQR riêng theo số tiền và mã đơn. BIDV: 970418. Để trống nếu chỉ dùng ảnh QR tải lên.</small>
<label>Số tài khoản<input name="account_number" value="{{ old('account_number',$setting?->account_number) }}" maxlength="50" required inputmode="text"></label>
<label>Tên chủ tài khoản<input name="account_holder" value="{{ old('account_holder',$setting?->account_holder) }}" maxlength="120" required></label>
<label>Ảnh QR nhận tiền<input type="file" name="qr" accept="image/png,image/jpeg,image/webp" @required(!$setting)></label><small class="muted-cell">PNG, JPG hoặc WebP · Tối đa 2 MB · Tối đa 4.000 × 4.000 px. Dùng QR nhận tiền không cố định số tiền và nội dung; khách sẽ nhập theo từng đơn.</small>
<input type="hidden" name="enabled" value="0"><label class="checkbox-inline"><input type="checkbox" name="enabled" value="1" @checked(old('enabled',$setting?->enabled))> Cho phép khách chọn chuyển khoản</label>
<button class="admin-primary" data-submit-once>Lưu thiết lập</button></form></div>
<div class="admin-panel form-panel"><h2>QR hiện tại</h2>@if($setting)<img class="bank-payment-qr" style="display:block;width:100%;max-width:300px;margin:24px auto" src="{{ Storage::disk('public')->url($setting->qr_path) }}" alt="QR nhận tiền của cửa hàng"><p><strong>{{ $setting->bank_name }}</strong><br>{{ $setting->account_number }}<br>{{ $setting->account_holder }}</p><span class="status {{ $setting->enabled?'status-confirmed':'status-cancelled' }}">{{ $setting->enabled?'Đang bật chuyển khoản':'Đang tắt chuyển khoản' }}</span>@else<p class="muted-cell mt-3">Chưa tải QR. Khách hiện vẫn đặt hàng bằng COD.</p>@endif<p class="muted-cell mt-3">Sau khi khách báo chuyển khoản, kiểm tra tiền thực nhận trong ứng dụng ngân hàng rồi xác nhận tại chi tiết đơn hàng. Đổi QR chỉ áp dụng cho các đơn mới.</p></div></div>
@endsection
