<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StaffAccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('staff.account', ['account' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:120'], 'phone' => ['nullable', 'regex:/^(0[0-9]{9}|\+84[0-9]{9})$/']]);
        $request->user()->update($data);

        return back()->with('success', 'Đã cập nhật thông tin tài khoản.');
    }

    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()]]);
        $request->user()->forceFill(['password' => Hash::make($data['password']), 'remember_token' => null])->save();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Đã đổi mật khẩu. Vui lòng đăng nhập lại.');
    }
}
