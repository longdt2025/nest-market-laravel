@props(['type' => 'success', 'messages' => [], 'shown' => true])
@php
    $titles = ['success' => 'Thành công', 'error' => 'Vui lòng kiểm tra lại', 'info' => 'Thông báo'];
    $icons = ['success' => '✓', 'error' => '!', 'info' => 'i'];
@endphp
<div class="toast-card toast-{{ $type }}{{ $shown ? ' show' : '' }}" data-toast data-type="{{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}" aria-atomic="true">
    <span class="toast-icon" data-toast-icon aria-hidden="true">{{ $icons[$type] }}</span>
    <div class="toast-content">
        <strong class="toast-title" data-toast-title>{{ $titles[$type] }}</strong>
        <div class="toast-message" data-toast-message>
            @if(count($messages) > 1)
                <ul>@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul>
            @else
                {{ $messages[0] ?? '' }}
            @endif
        </div>
    </div>
    <button class="toast-close" type="button" data-toast-close aria-label="Đóng thông báo"><x-icon name="x" size="17" /></button>
    <span class="toast-progress" aria-hidden="true"></span>
</div>
