<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\User;
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
        if ($lines->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Giỏ hàng đang trống.']);
        }
        $subtotal = (int) $lines->sum('line_total');
        $shipping = self::shipping($subtotal);
        $checkoutToken = session('checkout_token');
        if (! $checkoutToken) {
            $checkoutToken = (string) Str::uuid();
            session()->put('checkout_token', $checkoutToken);
        }

        return view('checkout.create', compact('lines', 'subtotal', 'shipping', 'checkoutToken'));
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        $data = $request->validated();
        [$order, $created] = DB::transaction(function () use ($cart, $data, $request) {
            // Serialize this customer's checkout, including simultaneous retries.
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $existing = Order::where('user_id', $request->user()->id)
                ->where('checkout_token', $data['checkout_token'])->first();
            if ($existing) {
                return [$existing, false];
            }
            if (! hash_equals((string) $request->session()->get('checkout_token', ''), $data['checkout_token'])) {
                throw ValidationException::withMessages(['checkout_token' => 'Phiên đặt hàng đã thay đổi. Vui lòng tải lại trang thanh toán.']);
            }
            if (empty($cart)) {
                throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
            }
            // Sắp xếp ID trước khi lock để giảm deadlock khi nhiều khách đặt cùng lúc.
            $ids = array_map('intval', array_keys($cart));
            sort($ids);
            $products = Product::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $lines = [];
            $subtotal = 0;
            foreach ($ids as $id) {
                $product = $products->get($id);
                $quantity = (int) $cart[$id];
                if (! $product || ! $product->is_active || ! $product->category?->is_active || $quantity < 1 || $quantity > Cart::MAX_QUANTITY || $product->stock < $quantity) {
                    throw ValidationException::withMessages(['cart' => 'Giỏ hàng có sản phẩm hết hàng hoặc số lượng không hợp lệ. Vui lòng kiểm tra lại.']);
                }
                $line = $quantity * $product->price;
                $lines[] = ['product' => $product, 'quantity' => $quantity, 'price' => $product->price, 'line' => $line];
                $subtotal += $line;
            }
            $shipping = self::shipping($subtotal);
            $paymentMethod = $data['payment_method'] ?? 'cod';
            $bank = $paymentMethod === 'bank_transfer' ? PaymentSetting::active() : null;
            if ($paymentMethod === 'bank_transfer' && ! $bank) {
                throw ValidationException::withMessages(['payment_method' => 'Chuyển khoản hiện chưa khả dụng. Vui lòng chọn COD.']);
            }
            $order = Order::create([
                'code' => 'NS-'.now()->format('ymd').'-'.strtoupper(Str::random(8)),
                'checkout_token' => $data['checkout_token'],
                'user_id' => $request->user()->id, 'recipient_name' => $data['recipient_name'], 'phone' => $data['phone'],
                'shipping_address' => $data['shipping_address'], 'note' => $data['note'] ?? null,
                'subtotal' => $subtotal, 'shipping_fee' => $shipping, 'total' => $subtotal + $shipping,
                'payment_method' => $paymentMethod, 'status' => 'pending',
                'payment_status' => 'unpaid', 'payment_qr_path' => $bank?->qr_path,
                'payment_details' => $bank ? ['bank_name' => $bank->bank_name, 'bank_bin' => $bank->bank_bin, 'account_number' => $bank->account_number, 'account_holder' => $bank->account_holder] : null,
            ]);
            foreach ($lines as $line) {
                $product = $line['product'];
                $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name,
                    'quantity' => $line['quantity'], 'unit_price' => $line['price'], 'line_total' => $line['line']]);
                $product->decrement('stock', $line['quantity']);
            }

            $order->events()->create([
                'actor_id' => $request->user()->id, 'actor_role' => 'customer',
                'type' => 'placed', 'status' => 'pending', 'note' => 'Đơn hàng đã được tiếp nhận, đang chờ cửa hàng xác nhận.',
            ]);
            if ($request->boolean('save_address')) {
                $request->user()->update([
                    'delivery_name' => $data['recipient_name'], 'phone' => $data['phone'],
                    'shipping_address' => $data['shipping_address'],
                ]);
            }

            return [$order, true];
        }, 3);
        if ($created || $request->session()->get('checkout_token') === $data['checkout_token']) {
            $request->session()->forget(['cart', 'checkout_token']);
        }

        return redirect()->route('orders.show', $order)->with('success', $created
            ? 'Đặt hàng thành công! Đơn hàng của bạn đang chờ xác nhận.'
            : 'Đơn hàng này đã được tiếp nhận trước đó. Bạn có thể xem tình trạng hiện tại bên dưới.');
    }

    public static function shipping(int $subtotal): int
    {
        return $subtotal >= 800000 ? 0 : 30000;
    }
}
