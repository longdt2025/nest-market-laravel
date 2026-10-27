<?php
namespace App\Support;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class Cart
{
    /** @return Collection<int, array{product:Product,quantity:int,line_total:int}> */
    public static function lines(): Collection
    {
        $quantities = collect(Session::get('cart', []));
        if ($quantities->isEmpty()) return collect();
        $products = Product::query()->whereIn('id', $quantities->keys()->all())->where('is_active', true)->whereHas('category', fn ($q) => $q->where('is_active', true))->get()->keyBy('id');
        return $quantities->map(function ($quantity, $id) use ($products) {
            $product = $products->get((int) $id);
            if (!$product || $product->stock < 1) return null;
            $quantity = min(max((int) $quantity, 1), $product->stock);
            return ['product'=>$product,'quantity'=>$quantity,'line_total'=>$quantity * $product->price];
        })->filter()->values();
    }
    public static function count(): int { return array_sum(array_map('intval', Session::get('cart', []))); }
    public static function subtotal(): int { return (int) self::lines()->sum('line_total'); }
}
