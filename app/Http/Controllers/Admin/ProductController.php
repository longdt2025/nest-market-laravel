<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'integer', 'exists:categories,id']]);
        $products = Product::with('category')->when($filters['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', '%'.$v.'%'))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category_id', $v))->latest()->orderByDesc('id')->paginate(10)->withQueryString();

        return view('admin.products.index', ['products' => $products, 'categories' => Category::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product, 'categories' => Category::orderBy('name')->get()]);
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', ['product' => $product, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        [$data,$uploads] = $this->dataWithUploads($request);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(6));
        try {
            DB::transaction(fn () => Product::create($data));
        } catch (\Throwable $exception) {
            $this->deleteImages($uploads);
            throw $exception;
        }

        return redirect()->route('manager.products.index')->with('success', 'Đã thêm sản phẩm.');
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        [$data,$uploads] = $this->dataWithUploads($request);
        try {
            $obsolete = DB::transaction(function () use ($product, $data) {
                $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                $old = array_filter([$locked->image_path, ...($locked->gallery ?? [])]);
                $locked->update($data);

                return array_diff($old, array_filter([$locked->image_path, ...($locked->gallery ?? [])]));
            });
        } catch (\Throwable $exception) {
            $this->deleteImages($uploads);
            throw $exception;
        }
        $this->deleteImages($obsolete);

        return redirect()->route('manager.products.index')->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            $product->update(['is_active' => false]);

            return back()->with('success', 'Sản phẩm đã có lịch sử mua hàng nên được ẩn thay vì xóa để bảo toàn dữ liệu đơn hàng.');
        }
        $images = array_filter([$product->image_path, ...($product->gallery ?? [])]);
        $product->delete();
        $this->deleteImages($images);

        return back()->with('success', 'Đã xóa sản phẩm.');
    }

    private function dataWithUploads(ProductRequest $request): array
    {
        $data = $request->validated();
        unset($data['image'],$data['gallery_images'],$data['remove_gallery'],$data['is_active'],$data['is_featured']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $uploads = [];
        try {
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                if (! $path) {
                    throw ValidationException::withMessages(['image' => 'Không lưu được ảnh. Vui lòng thử lại.']);
                }
                $uploads[] = $data['image_path'] = $path;
            }
            if ($request->hasFile('gallery_images')) {
                $data['gallery'] = [];
                foreach ($request->file('gallery_images') as $file) {
                    $path = $file->store('products', 'public');
                    if (! $path) {
                        throw ValidationException::withMessages(['gallery_images' => 'Không lưu được bộ ảnh. Vui lòng thử lại.']);
                    }
                    $uploads[] = $path;
                    $data['gallery'][] = $path;
                }
            } elseif ($request->boolean('remove_gallery')) {
                $data['gallery'] = [];
            }
        } catch (\Throwable $exception) {
            $this->deleteImages($uploads);
            throw $exception;
        }

        return [$data, $uploads];
    }

    private function deleteImages(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            // Bundled catalog pictures are shared assets, not uploaded files.
            if (is_string($path) && str_starts_with($path, 'products/') && ! str_contains($path, '..')) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
