<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
class ShopController extends Controller
{
    public function home(): View
    {
        return view('shop.home', [
            'featured'=>Product::query()->with('category')->where('is_active',true)->where('is_featured',true)
                ->whereHas('category', fn($q) => $q->where('is_active',true))->latest()->limit(8)->get(),
            'categories'=>Category::query()->where('is_active',true)->withCount(['products' => fn($q) => $q->where('is_active',true)])->get(),
        ]);
    }
    public function index(Request $request): View
    {
        $input = $request->validate([
            'q'=>['nullable','string','max:100'], 'category'=>['nullable','string','max:120'],
            'sort'=>['nullable','in:newest,price_asc,price_desc,name'],
        ]);
        $products = Product::query()->with('category')->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active',true));
        if ($term = trim($input['q'] ?? '')) {
            $products->where(fn ($q) => $q->where('name','like','%'.$term.'%')->orWhere('description','like','%'.$term.'%'));
        }
        if (!empty($input['category'])) {
            $products->whereHas('category', fn($q) => $q->where('slug', $input['category']));
        }
        switch ($input['sort'] ?? 'newest') {
            case 'price_asc': $products->orderBy('price')->orderBy('id'); break;
            case 'price_desc': $products->orderByDesc('price')->orderBy('id'); break;
            case 'name': $products->orderBy('name')->orderBy('id'); break;
            default: $products->latest();
        }
        return view('shop.index', [
            'products'=>$products->paginate(12)->withQueryString(),
            'categories'=>Category::query()->where('is_active',true)->orderBy('name')->get(),
        ]);
    }
    public function show(Product $product): View
    {
        abort_unless($product->is_active && $product->category->is_active, 404);
        $related = Product::query()->where('is_active',true)->where('category_id',$product->category_id)
            ->where('id','!=',$product->id)->latest()->limit(4)->get();
        return view('shop.show', compact('product','related'));
    }
}
