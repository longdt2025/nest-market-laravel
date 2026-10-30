<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', ['categories' => Category::withCount('products')->latest()->paginate(10)]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Category::create(['name' => $data['name'], 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'description' => $data['description'] ?? null, 'is_active' => $request->boolean('is_active')]);

        return redirect()->route('manager.categories.index')->with('success', 'Đã thêm danh mục.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $category->update(['name' => $data['name'], 'description' => $data['description'] ?? null, 'is_active' => $request->boolean('is_active')]);

        return redirect()->route('manager.categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['delete' => 'Danh mục đang có sản phẩm. Hãy chuyển sản phẩm sang danh mục khác trước khi xóa.']);
        }
        $category->delete();

        return back()->with('success', 'Đã xóa danh mục.');
    }
}
