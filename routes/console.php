<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('NEST Market - Good things for everyday life.');
})->purpose('Display a short message');

// Tùy chọn: lưu ảnh minh họa demo từ Unsplash vào ổ đĩa để trình diễn offline.
Artisan::command('nest:localize-images', function () {
    $products = \App\Models\Product::query()->where('image_path', 'like', 'https://images.unsplash.com/%')->get();
    $saved = 0; $failed = 0;
    foreach ($products as $product) {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->get($product->image_path);
            $response->throw();
            $mime = $response->header('Content-Type', '');
            if (!str_contains($mime, 'image/jpeg') && !str_contains($mime, 'image/webp') && !str_contains($mime, 'image/png')) {
                throw new \RuntimeException('Phản hồi không phải ảnh.');
            }
            $bytes = $response->body();
            if (strlen($bytes) > 5 * 1024 * 1024) throw new \RuntimeException('Ảnh vượt giới hạn 5MB.');
            $extension = str_contains($mime, 'webp') ? 'webp' : (str_contains($mime, 'png') ? 'png' : 'jpg');
            $path = 'products/demo-'.$product->id.'.'.$extension;
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $bytes);
            $product->update(['image_path'=>$path]);
            $this->line('✓ '.$product->name); $saved++;
        } catch (\Throwable $e) {
            $this->warn('Bỏ qua '.$product->name.': '.$e->getMessage()); $failed++;
        }
    }
    $this->info("Đã lưu $saved ảnh. Bỏ qua $failed ảnh. Hãy chạy php artisan storage:link.");
})->purpose('Tải ảnh demo Unsplash về local để mở web không cần mạng sau khi setup');
