<?php
namespace App\Http\Controllers;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()->withCount('items')->latest()->paginate(10);
        return view('orders.index', compact('orders'));
    }
    public function show(Request $request, Order $order): View
    {
        abort_unless((int)$order->user_id === (int)$request->user()->id,403);
        $order->load('items.product');
        return view('orders.show',compact('order'));
    }
}
