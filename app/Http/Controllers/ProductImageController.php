<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function show(string $file)
    {
        $disk = Storage::disk('minio_media');
        $path = 'products/minio/'.$file;
        abort_unless($disk->exists($path), 404);
        $stream = $disk->readStream($path);
        abort_unless(is_resource($stream), 404);
        $type = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'jpg','jpeg' => 'image/jpeg','png' => 'image/png','webp' => 'image/webp','gif' => 'image/gif'
        };

        return response()->stream(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
