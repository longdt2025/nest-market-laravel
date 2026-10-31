<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/**
 * @property string|null $image_path
 */
class Product extends Model
{
    public const NEW_ARRIVAL_DAYS = 14;

    public const ROOMS = ['living' => 'Phòng khách', 'bedroom' => 'Phòng ngủ', 'dining' => 'Phòng ăn', 'office' => 'Phòng làm việc', 'decor' => 'Đồ trang trí'];

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereHas('category', fn ($q) => $q->where('is_active', true));
    }

    public function scopeNewArrivals(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(self::NEW_ARRIVAL_DAYS));
    }

    public function getIsNewArrivalAttribute(): bool
    {
        return $this->created_at?->gte(now()->subDays(self::NEW_ARRIVAL_DAYS)) ?? false;
    }

    protected $fillable = [
        'sku', 'barcode', 'unit', 'cost_price', 'vat_rate',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'compare_at_price',
        'stock',
        'image_path',
        'is_active',
        'is_featured',
        'room', 'material', 'dimensions', 'color', 'warranty_months', 'care_instructions', 'gallery',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'price' => 'integer', 'cost_price' => 'integer', 'vat_rate' => 'decimal:2',
            'compare_at_price' => 'integer',
            'stock' => 'integer',
            'gallery' => 'array', 'warranty_months' => 'integer',
        ];
    }

    public function getCodeAttribute(): string
    {
        return ($this->sku !== null && $this->sku !== '') ? $this->sku : 'SP'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getImageUrlAttribute(): string
    {
        return $this->imageUrl($this->image_path);
    }

    public function getGalleryUrlsAttribute(): array
    {
        return array_values(array_unique(array_filter([
            $this->image_url,
            ...array_map(fn ($path) => $this->imageUrl($path), $this->gallery ?? []),
        ])));
    }

    public function getDiscountPercentAttribute(): int
    {
        return $this->compare_at_price > $this->price ? (int) round(100 * ($this->compare_at_price - $this->price) / $this->compare_at_price) : 0;
    }

    public function getRoomLabelAttribute(): string
    {
        return self::ROOMS[$this->room] ?? $this->category?->name ?? 'Nội thất';
    }

    private function imageUrl(?string $path): string
    {

        if (! $path) {
            return asset('images/product-fallback.svg');
        }

        if (str_starts_with($path, 'catalog/') && ! str_contains($path, '..')) {
            return is_file(public_path('images/'.$path)) ? asset('images/'.$path) : asset('images/product-fallback.svg');
        }

        if (str_starts_with($path, 'https://images.unsplash.com/')) {
            $photo = basename(parse_url($path, PHP_URL_PATH));
            if (preg_match('/^photo-[a-zA-Z0-9-]+$/', $photo) && is_file(public_path('images/demo/'.$photo.'.jpg'))) {
                return asset('images/demo/'.$photo.'.jpg');
            }

            return $path;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($path);
    }
}
