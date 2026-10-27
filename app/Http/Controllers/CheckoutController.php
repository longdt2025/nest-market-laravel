<?php
namespace App\Http\Controllers;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
class CheckoutController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $lines = Cart::lines();
        if ($lines->isEmpty()) return redirect()->route('cart.index')->withErrors(['cart'=>'Giỏ hàng đang trống.']);
        $subtotal = (int)$lines->sum('line_total');
        $shipping = self::shipping($subtotal);
        return view('checkout.create', compact('lines','subtotal','shipping'));
    }
    public function store(CheckoutRequest $request): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        if (empty($cart)) return redirect()->route('cart.index')->withErrors(['cart'=>'Giỏ hàng đang trống.']);
        $data = $request->validated();
        $order = DB::transaction(function () use ($cart,$data,$request) {
            // Sắp xếp ID trước khi lock để giảm deadlock khi nhiều khách đặt cùng lúc.
            $ids = array_map('intval', array_keys($cart)); sort($ids);
            $products = Product::query()->whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $lines = []; $subtotal = 0;
            foreach ($ids as $id) {
                $product = $products->get($id);
                $quantity = (int)$cart[$id];
                if (!$product || !$product->is_active || !$product->category->is_active || $quantity < 1 || $quantity > 100 || $product->stock < $quantity) {
                    throw ValidationException::withMessages(['cart'=>'Giỏ hàng có sản phẩm hết hàng hoặc số lượng không hợp lệ. Vui lòng kiểm tra lại.']);
                }
                $line = $quantity * $product->price;
                $lines[] = ['product'=>$product, 'quantity'=>$quantity,'price'=>$product->price,'line'=>$line];
                $subtotal += $line;
            }
            $shipping = self::shipping($subtotal);
            $order = Order::create([
                'code'=>'NS-'.now()->format('ymd').'-'.strtoupper(Str::random(8)),
                'user_id'=>$request->user()->id,'recipient_name'=>$data['recipient_name'],'phone'=>$data['phone'],
                'shipping_address'=>$data['shipping_address'],'note'=>$data['note'] ?? null,
                'subtotal'=>$subtotal,'shipping_fee'=>$shipping,'total'=>$subtotal+$shipping,
                'payment_method'=>'cod','status'=>'pending',
            ]);
            foreach ($lines as $line) {
                $product = $line['product'];
                $order->items()->create(['product_id'=>$product->id,'product_name'=>$product->name,
                    'quantity'=>$line['quantity'],'unit_price'=>$line['price'],'line_total'=>$line['line']]);
                $product->decrement('stock',$line['quantity']);
            }
            return $order;
        }, 3);
        $request->session()->forget('cart');
        return redirect()->route('orders.show',$order)->with('success','Đặt hàng thành công! Đơn hàng của bạn đang chờ xác nhận.');
    }
    public static function shipping(int $subtotal): int { return $subtotal >= 800000 ? 0 : 30000; }
}
