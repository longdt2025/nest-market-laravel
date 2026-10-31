<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BackupService
{
    public const TABLES = ['users', 'categories', 'products', 'payment_settings', 'orders', 'order_items', 'order_events'];

    private function schema(): array
    {
        return DB::table('migrations')->orderBy('migration')->pluck('migration')->all();
    }

    public function create(User $actor, string $label): Backup
    {
        $snapshot = DB::transaction(function () {
            $tables = [];
            foreach (self::TABLES as $table) {
                $tables[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
            }

            return $tables;
        });
        $files = [];
        $bytes = 0;
        foreach ($snapshot['products'] as $product) {
            $gallery = is_string($product['gallery'] ?? null) ? json_decode($product['gallery'], true) : ($product['gallery'] ?? []);
            foreach (array_filter([$product['image_path'], ...($gallery ?? [])]) as $path) {
                if (! str_starts_with($path, 'products/') || isset($files[$path])) {
                    continue;
                }
                if (str_contains($path, '..') || ! Storage::disk('public')->exists($path)) {
                    throw ValidationException::withMessages(['backup' => 'Ảnh upload bị thiếu hoặc không hợp lệ. Kiểm tra sản phẩm trước khi sao lưu.']);
                }
                $bytes += Storage::disk('public')->size($path);
                if ($bytes > config('backups.max_bytes')) {
                    throw ValidationException::withMessages(['backup' => 'Bản sao lưu vượt giới hạn 16 MB ảnh.']);
                }
                $files[$path] = base64_encode(Storage::disk('public')->get($path));
            }
        }
        $qrPaths = array_merge(array_column($snapshot['payment_settings'], 'qr_path'), array_column($snapshot['orders'], 'payment_qr_path'));
        foreach (array_unique(array_filter($qrPaths)) as $path) {
            if (isset($files[$path])) {
                continue;
            }
            if (! preg_match('#^products/[a-zA-Z0-9_./-]+$#', $path) || str_contains($path, '..') || ! Storage::disk('public')->exists($path)) {
                throw ValidationException::withMessages(['backup' => 'Ảnh QR thanh toán bị thiếu hoặc không hợp lệ.']);
            }
            $bytes += Storage::disk('public')->size($path);
            if ($bytes > config('backups.max_bytes')) {
                throw ValidationException::withMessages(['backup' => 'Bản sao lưu vượt giới hạn ảnh.']);
            }
            $files[$path] = base64_encode(Storage::disk('public')->get($path));
        }
        $payload = ['format' => 'nest-backup-v1', 'schema' => $this->schema(), 'created_at' => now()->toIso8601String(), 'tables' => $snapshot, 'files' => $files];
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        if (strlen($json) > config('backups.max_bytes') * 2) {
            throw ValidationException::withMessages(['backup' => 'Dữ liệu quá lớn cho sao lưu trong giao diện.']);
        }
        $archive = Crypt::encryptString(base64_encode(gzencode($json, 6)));
        $disk = config('backups.disk');
        $path = 'backups/'.now()->format('Y/m/').Str::uuid().'.nestbackup';
        $storage = Storage::disk($disk);
        try {
            if (! $storage->put($path, $archive, ['visibility' => 'private'])) {
                throw new \RuntimeException('Cannot save backup.');
            }
            if (! hash_equals(hash('sha256', $archive), hash('sha256', $storage->get($path)))) {
                throw new \RuntimeException('Backup verification failed.');
            }

            return Backup::create(['label' => $label, 'path' => $path, 'disk' => $disk, 'sha256' => hash('sha256', $archive), 'size' => strlen($archive), 'counts' => array_map('count', $snapshot), 'created_by' => $actor->id]);
        } catch (\Throwable $e) {
            try {
                $storage->delete($path);
            } catch (\Throwable) {
            }
            throw $e;
        }
    }

    public function read(Backup $backup): array
    {
        $archive = Storage::disk($backup->disk)->get($backup->path);
        if (! is_string($archive) || ! hash_equals($backup->sha256, hash('sha256', $archive))) {
            throw ValidationException::withMessages(['backup' => 'Bản sao lưu bị thay đổi hoặc không đầy đủ.']);
        }
        try {
            $compressed = base64_decode(Crypt::decryptString($archive), true);
            $json = $compressed === false ? false : gzdecode($compressed, config('backups.max_bytes') * 2);
            $data = json_decode($json ?: '', true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['backup' => 'Không giải mã được bản sao lưu. Cần đúng APP_KEY của ứng dụng.']);
        }
        if (($data['format'] ?? '') !== 'nest-backup-v1' || ($data['schema'] ?? []) !== $this->schema() || array_keys($data['tables'] ?? []) !== self::TABLES) {
            throw ValidationException::withMessages(['backup' => 'Phiên bản cấu trúc dữ liệu không khớp; không thể phục hồi.']);
        }
        $admin = false;
        foreach (self::TABLES as $table) {
            $columns = Schema::getColumnListing($table);
            foreach ($data['tables'][$table] as $row) {
                if (! is_array($row) || array_diff(array_keys($row), $columns)) {
                    throw ValidationException::withMessages(['backup' => 'Cấu trúc bản ghi không hợp lệ.']);
                }
                if ($table === 'users' && ($row['role'] ?? '') === 'admin' && ($row['is_active'] ?? false)) {
                    $admin = true;
                }
            }
        }
        if (! $admin) {
            throw ValidationException::withMessages(['backup' => 'Bản sao lưu phải có ít nhất một Admin đang hoạt động.']);
        }
        foreach ($data['files'] ?? [] as $path => $contents) {
            if (! preg_match('#^products/[a-zA-Z0-9_./-]+$#', $path) || str_contains($path, '..') || base64_decode($contents, true) === false) {
                throw ValidationException::withMessages(['backup' => 'Đường dẫn hoặc nội dung ảnh không hợp lệ.']);
            }
        }

        return $data;
    }

    public function restore(Backup $backup, User $actor): void
    {
        $data = $this->read($backup);
        // Always retain a recovery point before replacing application data.
        $this->create($actor, 'Tự động trước phục hồi #'.$backup->id);
        $newFiles = [];
        $map = [];
        try {
            foreach ($data['files'] ?? [] as $path => $content) {
                $new = 'products/restored/'.Str::uuid().'/'.basename($path);
                if (! Storage::disk('public')->put($new, base64_decode($content, true))) {
                    throw new \RuntimeException('Cannot restore uploaded image.');
                }
                $map[$path] = $new;
                $newFiles[] = $new;
            }
            DB::transaction(function () use ($data, $map, $backup) {
                // Lock existing rows; HTTP mutations also share SerializeDataWrites.
                foreach (self::TABLES as $table) {
                    DB::table($table)->orderBy('id')->lockForUpdate()->get();
                }
                foreach (array_reverse(self::TABLES) as $table) {
                    DB::table($table)->delete();
                }
                foreach (self::TABLES as $table) {
                    foreach ($data['tables'][$table] as $row) {
                        if ($table === 'users') {
                            $row['remember_token'] = null;
                        }
                        if ($table === 'products') {
                            $row['image_path'] = $map[$row['image_path']] ?? $row['image_path'];
                            $gallery = is_string($row['gallery'] ?? null) ? json_decode($row['gallery'], true) : ($row['gallery'] ?? null);
                            if (is_array($gallery)) {
                                $row['gallery'] = json_encode(array_map(fn ($p) => $map[$p] ?? $p, $gallery));
                            }
                        }
                        if ($table === 'payment_settings') {
                            $row['qr_path'] = $map[$row['qr_path']] ?? $row['qr_path'];
                        }
                        if ($table === 'orders' && ! empty($row['payment_qr_path'])) {
                            $row['payment_qr_path'] = $map[$row['payment_qr_path']] ?? $row['payment_qr_path'];
                        }
                        DB::table($table)->insert($row);
                    }
                }
                DB::table('password_reset_tokens')->delete();
                Cache::forever('auth_epoch', (string) Str::uuid());
                $backup->update(['restored_at' => now()]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($newFiles);
            throw $e;
        }
    }
}
