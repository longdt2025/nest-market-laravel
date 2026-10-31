<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductCatalogQuery
{
    public function filters(Request $request): array
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'in:active,hidden'], 'inventory' => ['nullable', 'in:in_stock,low_stock,out_stock'],
            'room' => ['nullable', Rule::in(array_keys(Product::ROOMS))],
            'min_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'max_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'sort' => ['nullable', 'in:id,code,name,barcode,unit,category,room,cost_price,vat_rate,price,stock,is_active,created_at'], 'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
        ]);
        if (isset($data['min_price'],$data['max_price']) && $data['min_price'] > $data['max_price']) {
            throw ValidationException::withMessages(['max_price' => 'Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.']);
        }

        return $data;
    }

    public function query(array $filters)
    {
        $query = Product::with('category');
        if ($q = trim($filters['q'] ?? '')) {
            $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%$q%")->orWhere('sku', 'like', "%$q%")->orWhere('barcode', 'like', "%$q%");
                if (preg_match('/^SP0*(\d+)$/i', $q, $m)) {
                    $query->orWhere('id', (int) $m[1]);
                }
            });
        }
        if (! empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }
        if (! empty($filters['room'])) {
            $query->where('room', $filters['room']);
        }
        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        if (($filters['inventory'] ?? '') === 'in_stock') {
            $query->where('stock', '>', 0);
        }
        if (($filters['inventory'] ?? '') === 'low_stock') {
            $query->whereBetween('stock', [1, 5]);
        }
        if (($filters['inventory'] ?? '') === 'out_stock') {
            $query->where('stock', 0);
        }
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }
        $sort = $filters['sort'] ?? 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        if ($sort === 'code') {
            $fallback = DB::getDriverName() === 'sqlite' ? "printf('SP%06d',products.id)" : "CONCAT('SP', LPAD(products.id,GREATEST(6,CHAR_LENGTH(products.id)),'0'))";
            $query->orderByRaw("COALESCE(NULLIF(products.sku,''), $fallback) $direction");
        } elseif ($sort === 'category') {
            $query->orderBy(Category::select('name')->whereColumn('categories.id', 'products.category_id')->limit(1), $direction);
        } elseif ($sort === 'room') {
            $sql = 'CASE products.room ';
            $bindings = [];
            foreach (Product::ROOMS as $key => $label) {
                $sql .= 'WHEN ? THEN ? ';
                $bindings[] = $key;
                $bindings[] = $label;
            }
            $sql .= 'ELSE (SELECT name FROM categories WHERE categories.id=products.category_id) END';
            $query->orderByRaw($sql.' '.$direction, $bindings);
        } else {
            $query->orderBy($sort, $direction);
        }
        if ($sort !== 'id') {
            $query->orderByDesc('id');
        }

        return $query;
    }
}
