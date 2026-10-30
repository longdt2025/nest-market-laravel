@extends('layouts.admin')
@section('title','Sao lưu và phục hồi')
@section('content')
<div class="admin-page-header"><div><h1>Sao lưu & phục hồi</h1><p>Lưu bản sao riêng tư trên MinIO, bảo vệ dữ liệu cửa hàng.</p></div></div>
<div class="admin-panel form-panel mb-4">
<form method="POST" action="{{ route('system.backups.store') }}" class="workspace-backup-form">@csrf<label>Tên bản sao lưu<input name="label" maxlength="120" value="{{ old('label','Sao lưu '.now()->format('d/m/Y H:i')) }}" required></label><button class="admin-primary" data-submit-once><x-icon name="shield" size="18"/> Tạo bản sao lưu</button></form>
<p class="muted-cell mt-3 mb-0">Bao gồm 6 bảng nghiệp vụ và ảnh upload đang được sản phẩm tham chiếu. File mã hóa bằng APP_KEY; giữ khóa này để phục hồi. Không chứa .env, mã nguồn, cache hoặc session.</p>
</div>
<div class="admin-panel"><div class="panel-head"><h2>Danh sách bản sao lưu</h2><span class="workspace-role">MinIO · Riêng tư</span></div><div class="table-scroll"><table class="admin-table"><thead><tr><th>BẢN SAO LƯU</th><th>THỜI GIAN</th><th>DUNG LƯỢNG</th><th>ĐÃ PHỤC HỒI</th><th>THAO TÁC</th></tr></thead><tbody>
@forelse($backups as $backup)<tr><td><strong>{{ $backup->label }}</strong><br><small>#{{ $backup->id }} · {{ $backup->counts['products']??0 }} sản phẩm / {{ $backup->counts['orders']??0 }} đơn</small></td><td>{{ $backup->created_at->format('d/m/Y H:i') }}</td><td>{{ number_format($backup->size/1024,1) }} KB</td><td>{{ $backup->restored_at?->format('d/m/Y H:i') ?? '—' }}</td><td><a class="table-detail-link" href="{{ route('system.backups.show',$backup) }}">Kiểm tra / Phục hồi <x-icon name="arrow-right" size="16"/></a></td></tr>
@empty<tr><td colspan="5">Chưa có bản sao lưu. Tạo bản đầu tiên để bảo vệ dữ liệu.</td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $backups->links() }}</div></div>
@endsection
