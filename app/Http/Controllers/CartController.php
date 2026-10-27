<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
class CartController extends Controller
{
    public function index(): View
    {
        $lines = Cart::lines(); $subtotal = (int)$lines->sum('line_total');
        return view('cart.index', compact('lines','subtotal'));
    }
    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['quantity'=>['required','integer','min:1','max:100']]);
        if (!$product->is_active || !$product->category->is_active || $product->stock < 1) {
            return $this->fail($request, 'Sản phẩm hiện không thể mua.', 422);
        }
        $cart = $request->session()->get('cart', []);
        $current = (int)($cart[$product->id] ?? 0);
        if ($current + (int)$data['quantity'] > $product->stock) {
            return $this->fail($request, 'Số lượng vượt quá tồn kho hiện tại.', 422);
        }
        $cart[$product->id] = $current + (int)$data['quantity'];
        $request->session()->put('cart',$cart);
        if ($request->expectsJson()) return response()->json(['message'=>'Đã thêm vào giỏ hàng.','cart_count'=>Cart::count()]);
        return back()->with('success','Đã thêm sản phẩm vào giỏ hàng.');
    }
    public function update(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['quantity'=>['required','integer','min:1','max:100']]);
        $cart = $request->session()->get('cart', []);
        abort_unless(array_key_exists($product->id,$cart),404);
        if (!$product->is_active || $product->stock < (int)$data['quantity']) return $this->fail($request,'Số lượng vượt quá tồn kho.',422);
        $cart[$product->id] = (int)$data['quantity']; $request->session()->put('cart',$cart);
        if ($request->expectsJson()) {
            $subtotal = Cart::subtotal();
            $shipping = \App\Http\Controllers\CheckoutController::shipping($subtotal);
            return response()->json(['message'=>'Đã cập nhật giỏ hàng.','cart_count'=>Cart::count(),'subtotal'=>$subtotal,
                'line_total'=>$product->price * (int)$data['quantity'], 'product_id'=>$product->id, 'shipping'=>$shipping, 'total'=>$subtotal+$shipping]);
        }
        return back()->with('success','Đã cập nhật giỏ hàng.');
    }
    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $cart = $request->session()->get('cart', []); unset($cart[$product->id]);
        $request->session()->put('cart', $cart);
        return back()->with('success','Đã xóa sản phẩm khỏi giỏ hàng.');
    }
    private function fail(Request $request, string $message, int $code): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) return response()->json(['message'=>$message],$code);
        return back()->withErrors(['cart'=>$message]);
    }
}
