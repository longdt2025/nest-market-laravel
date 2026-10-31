<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Str;

class VietQr
{
    /** Quick Link image API; values come only from the saved order, never query parameters. */
    public function imageUrl(Order $order): ?string
    {
        $details = $order->payment_details ?? [];
        $bin = $details['bank_bin'] ?? '';
        $account = $details['account_number'] ?? '';
        if ($order->payment_method !== 'bank_transfer' || ! preg_match('/^\d{6}$/D', $bin) || ! preg_match('/^\d{6,19}$/D', $account) || $order->total < 1 || $order->total > 9999999999) {
            return null;
        }
        $reference = $this->reference($order);
        if ($reference === '') {
            return null;
        }

        return 'https://img.vietqr.io/image/'.$bin.'-'.$account.'-compact2.svg?'.http_build_query([
            'amount' => (int) $order->total,
            'addInfo' => $reference,
            'accountName' => Str::upper(Str::ascii($details['account_holder'] ?? '')),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function reference(Order $order): string
    {
        return substr(preg_replace('/[^A-Za-z0-9]/', '', $order->code), 0, 25);
    }
}
