<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters=$request->validate(['q'=>['nullable','string','max:100'],'category'=>['nullable','integer','exists:categories,id']]);
        $products=Product::with('category')->when($filters['q'] ?? null, fn($q,$v) => $q->where('name','like','%'.$v.'%'))
            ->when($filters['category'] ?? null, fn($q,$v) => $q->where('category_id',$v))->latest()->paginate(10)->withQueryString();
        return view('admin.products.index',['products'=>$products,'categories'=>Category::orderBy('name')->get()]);
    }
    public function create(): View { return view('admin.products.form',['product'=>new Product(),'categories'=>Category::orderBy('name')->get()]); }
    public function store(ProductRequest $request): RedirectResponse
    {
        $data=$request->validated(); unset($data['image'],$data['is_active'],$data['is_featured']);
        $data['slug']=Str::slug($data['name']).'-'.Str::lower(Str::random(6));
        $data['is_active']=$request->boolean('is_active'); $data['is_featured']=$request->boolean('is_featured');
        if ($request->hasFile('image')) $data['image_path']=$request->file('image')->store('products','public');
        Product::create($data);
        return redirect()->route('admin.products.index')->with('success','Đã thêm sản phẩm.');
    }
    public function edit(Product $product): View { return view('admin.products.form',['product'=>$product,'categories'=>Category::orderBy('name')->get()]); }
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data=$request->validated(); unset($data['image'],$data['is_active'],$data['is_featured']);
        $data['is_active']=$request->boolean('is_active'); $data['is_featured']=$request->boolean('is_featured');
        if ($request->hasFile('image')) {
            $path=$request->file('image')->store('products','public');
            if ($product->image_path && !str_starts_with($product->image_path, 'https://')) Storage::disk('public')->delete($product->image_path);
            $data['image_path']=$path;
        }
        $product->update($data);
        return redirect()->route('admin.products.index')->with('success','Đã cập nhật sản phẩm.');
    }
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            $product->update(['is_active'=>false]);
            return back()->with('success','Sản phẩm đã có lịch sử mua hàng nên được ẩn thay vì xóa để bảo toàn dữ liệu đơn hàng.');
        }
        if ($product->image_path && !str_starts_with($product->image_path,'https://')) Storage::disk('public')->delete($product->image_path);
        $product->delete();
        return back()->with('success','Đã xóa sản phẩm.');
    }
}
