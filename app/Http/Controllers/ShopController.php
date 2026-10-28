<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function home(): View
    {
        $visible = Product::visible()->with('category');
        $counts = Product::visible()->selectRaw('room, count(*) as total')->groupBy('room')->pluck('total', 'room');
        $representatives = (clone $visible)->whereIn('slug', ['sofa-2-cho-oslo', 'giuong-go-sora', 'ban-an-kita-4-cho', 'ban-lam-viec-desk-form', 'binh-gom-kanso'])->get()->keyBy('room');
        $rooms = collect(Product::ROOMS)->map(fn ($name, $key) => ['key' => $key, 'name' => $name, 'count' => $counts[$key] ?? 0, 'image' => $representatives->get($key)?->image_url ?? asset('images/product-fallback.svg')]);

        return view('shop.home', [
            'featured' => (clone $visible)->where('is_featured', true)->latest()->orderByDesc('id')->limit(8)->get(),
            'offers' => (clone $visible)->whereColumn('compare_at_price', '>', 'price')->latest()->orderByDesc('id')->limit(4)->get(),
            'rooms' => $rooms,
            'categories' => Category::query()->where('is_active', true)->withCount(['products' => fn ($q) => $q->where('is_active', true)])->get(),
        ]);
    }

    public function index(Request $request): View
    {
        return $this->catalog($request);
    }

    public function newArrivals(Request $request): View
    {
        return $this->catalog($request, true);
    }

    public function offers(Request $request): View
    {
        return $this->catalog($request, false, true);
    }

    private function catalog(Request $request, bool $newArrivals = false, bool $onSale = false): View
    {
        $input = $request->validate([
            'q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc,name'],
            'room' => ['nullable', 'in:'.implode(',', array_keys(Product::ROOMS))],
            'material' => ['nullable', 'string', 'max:180'], 'color' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'], 'max_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'in_stock' => ['nullable', 'boolean'], 'on_sale' => ['nullable', 'boolean'],
        ]);
        if (isset($input['min_price'],$input['max_price']) && $input['min_price'] > $input['max_price']) {
            throw ValidationException::withMessages(['max_price' => 'Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.']);
        }
        $products = Product::visible()->with('category');
        if ($newArrivals) {
            $products->newArrivals();
        }
        if ($onSale || $request->boolean('on_sale')) {
            $products->whereColumn('compare_at_price', '>', 'price');
        }
        foreach (['room', 'material', 'color'] as $field) {
            if (! empty($input[$field])) {
                $products->where($field, $input[$field]);
            }
        }
        if (isset($input['min_price'])) {
            $products->where('price', '>=', $input['min_price']);
        }
        if (isset($input['max_price'])) {
            $products->where('price', '<=', $input['max_price']);
        }
        if ($request->boolean('in_stock')) {
            $products->where('stock', '>', 0);
        }
        if ($term = trim($input['q'] ?? '')) {
            $products->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('description', 'like', '%'.$term.'%'));
        }
        if (! empty($input['category'])) {
            $products->whereHas('category', fn ($q) => $q->where('slug', $input['category']));
        }
        switch ($input['sort'] ?? 'newest') {
            case 'price_asc': $products->orderBy('price')->orderBy('id');
                break;
            case 'price_desc': $products->orderByDesc('price')->orderBy('id');
                break;
            case 'name': $products->orderBy('name')->orderBy('id');
                break;
            default: $products->latest()->orderByDesc('id');
        }

        return view('shop.index', [
            'products' => $products->paginate(12)->withQueryString(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'newArrivals' => $newArrivals,
            'onSale' => $onSale,
            'catalogRoute' => $newArrivals ? 'shop.new' : ($onSale ? 'shop.offers' : 'shop.index'),
            'filters' => array_filter($input, fn ($v) => $v !== null && $v !== ''),
            'materials' => Product::visible()->whereNotNull('material')->distinct()->orderBy('material')->pluck('material'),
            'colors' => Product::visible()->whereNotNull('color')->distinct()->orderBy('color')->pluck('color'),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active && $product->category->is_active, 404);
        $related = Product::visible()->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)->latest()->limit(4)->get();

        return view('shop.show', compact('product', 'related'));
    }
}
