<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\View\View;
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'productCount'=>Product::count(), 'categoryCount'=>Category::count(),
            'customerCount'=>User::where('role','customer')->count(),
            'pendingCount'=>Order::where('status','pending')->count(),
            'sales'=>Order::where('status','delivered')->sum('total'),
            'recentOrders'=>Order::with('user')->latest()->limit(7)->get(),
            'lowStock'=>Product::with('category')->where('stock','<=',5)->orderBy('stock')->limit(5)->get(),
        ]);
    }
}
