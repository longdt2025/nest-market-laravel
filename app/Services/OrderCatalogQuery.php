<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderCatalogQuery
{
    public const SORTS = ['code', 'created_at', 'status', 'recipient_name', 'total', 'shipping_fee'];

    public function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(Order::LABELS))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'min_total' => ['nullable', 'integer', 'min:0', 'max:1000000000000'],
            'max_total' => ['nullable', 'integer', 'min:0', 'max:1000000000000', ...($request->filled('min_total') ? ['gte:min_total'] : [])],
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in([10, 25, 50, 100])],
        ]);
    }

    public function query(array $filters, bool $status = true): Builder
    {
        $query = Order::query()->with('user');
        if ($status && ! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (filled($filters['q'] ?? null)) {
            $query->where(function ($query) use ($filters) {
                $term = '%'.$filters['q'].'%';
                $query->where('code', 'like', $term)->orWhere('recipient_name', 'like', $term)
                    ->orWhere('phone', 'like', $term)->orWhereHas('user', fn ($q) => $q->where('email', 'like', $term));
            });
        }
        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from'].' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<', Carbon::parse($filters['to'])->addDay()->startOfDay());
        }
        foreach (['min_total' => '>=', 'max_total' => '<='] as $key => $operator) {
            if (filled($filters[$key] ?? null)) {
                $query->where('total', $operator, $filters[$key]);
            }
        }

        return $query;
    }

    public function sorted(array $filters): Builder
    {
        return $this->query($filters)->withCount('items')->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')->orderByDesc('id');
    }
}
