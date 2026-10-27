<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $input=$request->validate(['status'=>['nullable','in:pending,confirmed,shipping,delivered,cancelled'],'q'=>['nullable','string','max:100']]);
        $orders=Order::with('user')->when($input['status'] ?? null,fn($q,$v)=>$q->where('status',$v))
            ->when($input['q'] ?? null,fn($q,$v)=>$q->where('code','like','%'.$v.'%'))
            ->latest()->paginate(12)->withQueryString();
        return view('admin.orders.index',compact('orders'));
    }
    public function show(Order $order): View { $order->load(['items.product','user']); return view('admin.orders.show',compact('order')); }
    public function update(Request $request, Order $order): RedirectResponse
    {
        $data=$request->validate(['status'=>['required','in:confirmed,shipping,delivered,cancelled']]);
        DB::transaction(function () use ($order,$data) {
            $locked=Order::query()->lockForUpdate()->findOrFail($order->id);
            if (!in_array($data['status'], Order::NEXT[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages(['status'=>'Chuyển trạng thái không hợp lệ.']);
            }
            if ($data['status']==='cancelled') {
                $items=$locked->items()->orderBy('product_id')->get();
                foreach ($items as $item) Product::whereKey($item->product_id)->increment('stock',$item->quantity);
            }
            $locked->update(['status'=>$data['status']]);
        },3);
        return back()->with('success','Đã cập nhật trạng thái đơn hàng.');
    }
}
