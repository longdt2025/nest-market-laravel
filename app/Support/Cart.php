<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class Cart
{
    public const MAX_QUANTITY = 100;

    /** @return Collection<int, array{product:Product,quantity:int,line_total:int}> */
    public static function lines(): Collection
    {
        $quantities = collect(Session::get('cart', []));
        if ($quantities->isEmpty()) {
            return collect();
        }
        $products = Product::query()->with('category')->whereIn('id', $quantities->keys()->all())->where('is_active', true)->whereHas('category', fn ($q) => $q->where('is_active', true))->get()->keyBy('id');
        $lines = $quantities->map(function ($quantity, $id) use ($products) {
            $product = $products->get((int) $id);
            if (! $product || $product->stock < 1) {
                return null;
            }
            $quantity = min(max((int) $quantity, 1), $product->stock, self::MAX_QUANTITY);

            return ['product' => $product, 'quantity' => $quantity, 'line_total' => $quantity * $product->price];
        })->filter()->values();
        $cart = $lines->mapWithKeys(fn ($line) => [$line['product']->id => $line['quantity']])->all();
        if ($cart !== $quantities->all()) {
            Session::put('cart', $cart);
            Session::flash('cart_notice', 'Giỏ hàng đã được cập nhật theo tồn kho và tình trạng sản phẩm hiện tại. Vui lòng kiểm tra trước khi đặt hàng.');
        }

        return $lines;
    }

    public static function count(): int
    {
        return (int) self::lines()->sum('quantity');
    }

    public static function subtotal(): int
    {
        return (int) self::lines()->sum('line_total');
    }
}
