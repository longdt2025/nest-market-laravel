<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['customer' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'delivery_name' => ['nullable', 'string', 'min:2', 'max:120'],
            'phone' => ['nullable', 'regex:/^(0[0-9]{9}|\+84[0-9]{9})$/'],
            'shipping_address' => ['nullable', 'string', 'min:10', 'max:500'],
        ], ['phone.regex' => 'Số điện thoại cần có 10 số (bắt đầu bằng 0) hoặc +84 và 9 số.']);
        $request->user()->update($data);

        return redirect()->route('profile.edit')->with('success', 'Đã lưu hồ sơ và thông tin nhận hàng mặc định.');
    }
}
