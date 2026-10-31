<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LoginPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'string', 'max:255'], 'password' => ['required', 'string']]);
        $login = trim($credentials['email']);
        unset($credentials['email']);
        $phone = LoginPhone::normalize($login);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : (preg_match('/^0[0-9]{9}$/D', $phone) ? 'login_phone' : 'username');
        $credentials[$field] = $field === 'login_phone' ? $phone : $login;
        $credentials['is_active'] = true;
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Tài khoản hoặc mật khẩu không chính xác.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $request->session()->put('auth_stamp', ['id' => auth()->id(), 'hash' => auth()->user()->password, 'epoch' => Cache::get('auth_epoch', 'initial')]);
        if (auth()->user()->isAdmin()) {
            return redirect()->route('system.dashboard');
        }
        if (auth()->user()->isManager()) {
            return redirect()->route('manager.dashboard');
        }

        return redirect()->intended(route('shop.home'));
    }

    public function register(Request $request): RedirectResponse
    {
        if (is_string($request->input('phone'))) {
            $request->merge(['phone' => LoginPhone::normalize($request->input('phone'))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'], 'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'regex:/^0[0-9]{9}$/D', 'unique:users,login_phone'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $user = new User(['name' => $data['name'], 'email' => $data['email'] ?? null, 'phone' => $data['phone'], 'password' => $data['password']]);
        $user->login_phone = $data['phone'];
        $user->save();
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth_stamp', ['id' => auth()->id(), 'hash' => auth()->user()->password, 'epoch' => Cache::get('auth_epoch', 'initial')]);

        return redirect()->route('shop.home')->with('success', 'Tài khoản đã được tạo. Chào mừng đến NEST!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('shop.home')->with('success', 'Bạn đã đăng xuất thành công.');
    }
}
