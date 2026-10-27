<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
class AuthController extends Controller
{
    public function loginForm(): View { return view('auth.login'); }
    public function registerForm(): View { return view('auth.register'); }
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email'=>['required','email'],'password'=>['required','string']]);
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email'=>'Email hoặc mật khẩu không chính xác.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return auth()->user()->isAdmin() ? redirect()->route('admin.dashboard') : redirect()->intended(route('shop.home'));
    }
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'=>['required','string','min:2','max:120'], 'email'=>['required','email','max:255','unique:users,email'],
            'password'=>['required','confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $user = User::create(['name'=>$data['name'],'email'=>$data['email'],'password'=>$data['password']]);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('shop.home')->with('success','Tài khoản đã được tạo. Chào mừng đến NEST!');
    }
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('shop.home')->with('success','Bạn đã đăng xuất thành công.');
    }
}
