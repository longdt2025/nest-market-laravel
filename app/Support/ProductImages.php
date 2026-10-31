<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ProductImages
{
    public static function url(string $path): string
    {
        return str_starts_with($path, 'products/minio/')
            ? route('products.image', ['file' => basename($path)])
            : Storage::disk('public')->url($path);
    }

    public static function disk(string $path)
    {
        return Storage::disk(str_starts_with($path, 'products/minio/') ? 'minio_media' : 'public');
    }
}
