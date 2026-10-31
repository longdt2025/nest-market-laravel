<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentSettingController extends Controller
{
    public function edit()
    {
        return view('admin.payments.settings', ['setting' => PaymentSetting::find(1)]);
    }

    public function update(Request $request)
    {
        $setting = PaymentSetting::find(1);
        $data = $request->validate([
            'bank_bin' => ['nullable', 'regex:/^[0-9]{6}$/D'],
            'enabled' => ['nullable', 'boolean'], 'bank_name' => ['required', 'string', 'max:120'],
            'account_number' => ['required', 'regex:/^[0-9A-Za-z -]{3,50}$/'],
            'account_holder' => ['required', 'string', 'max:120'],
            'qr' => [$setting ? 'nullable' : 'required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ]);
        if (! empty($data['bank_bin']) && ! preg_match('/^[0-9]{6,19}$/D', $data['account_number'])) {
            throw ValidationException::withMessages(['account_number' => 'VietQR yêu cầu số tài khoản từ 6 đến 19 chữ số.']);
        }
        $path = null;
        try {
            if ($request->hasFile('qr')) {
                $path = $request->file('qr')->store('products/payment-qr', 'public');
                if (! $path) {
                    throw new \RuntimeException('Không lưu được ảnh QR.');
                }
            }
            PaymentSetting::updateOrCreate(['id' => 1], [
                'enabled' => $request->boolean('enabled'), 'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'], 'account_holder' => $data['account_holder'],
                'bank_bin' => $data['bank_bin'] ?? null,
                'qr_path' => $path ?? $setting->qr_path,
            ]);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }

        // Keep earlier QR images: existing orders retain their original payment instructions.
        return back()->with('success', 'Đã lưu thông tin thanh toán chuyển khoản.');
    }
}
