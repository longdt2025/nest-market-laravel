@extends('layouts.admin')
@section('title','Kiểm tra bản sao lưu')
@section('content')
<div class="admin-page-header"><div><h1>{{ $backup->label }}</h1><p>Bản #{{ $backup->id }} · {{ $backup->created_at->format('d/m/Y H:i') }}</p></div><a class="admin-secondary" href="{{ route('system.backups.index') }}">Danh sách</a></div>
<div class="admin-form-grid"><div class="admin-panel form-panel"><h2>Nội dung đã kiểm tra</h2><span class="status status-confirmed">Checksum và cấu trúc hợp lệ</span><dl class="workspace-counts mt-4">@foreach($counts as $table=>$count)<div><dt>{{ $table }}</dt><dd>{{ $count }} bản ghi</dd></div>@endforeach<div><dt>Ảnh upload</dt><dd>{{ $fileCount }} file</dd></div></dl><a class="admin-secondary mt-3" href="{{ route('system.backups.download',$backup) }}">Tải file mã hóa</a></div>
<div class="admin-panel form-panel"><h2>Phục hồi dữ liệu</h2><p>Thao tác thay thế dữ liệu nghiệp vụ hiện tại bằng bản sao này, gồm cả tài khoản và mật khẩu tại thời điểm sao lưu. Hệ thống tạo bản dự phòng trước khi phục hồi.</p><p class="muted-cell">Ảnh catalog đi kèm source, cấu hình môi trường và danh sách backup được giữ riêng. Bạn sẽ cần đăng nhập lại.</p>
<form method="POST" action="{{ route('system.backups.restore',$backup) }}" class="form-stack mt-4" data-confirm="Phục hồi bản #{{ $backup->id }} và thay thế dữ liệu hiện tại?">@csrf
<label>Mật khẩu Admin hiện tại<input type="password" name="current_password" required autocomplete="current-password"></label>
<label>Nhập PHUC HOI {{ $backup->id }} để xác nhận<input name="confirmation" required autocomplete="off" placeholder="PHUC HOI {{ $backup->id }}"></label>
<button class="admin-primary" data-submit-once>Phục hồi bản sao lưu</button>
</form></div></div>
@endsection
