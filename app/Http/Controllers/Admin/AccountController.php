<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'role' => ['nullable', Rule::in(array_keys(User::ROLES))], 'active' => ['nullable', Rule::in(['0', '1'])]]);
        $accounts = User::query()->when($input['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%$v%")->orWhere('email', 'like', "%$v%")->orWhere('username', 'like', "%$v%")))
            ->when($input['role'] ?? null, fn ($q, $v) => $q->where('role', $v));
        if (isset($input['active'])) {
            $accounts->where('is_active', $input['active']);
        }

        return view('system.accounts.index', ['accounts' => $accounts->latest()->paginate(12)->withQueryString()]);
    }

    public function create()
    {
        return view('system.accounts.form', ['account' => new User(['is_active' => true, 'role' => 'customer'])]);
    }

    public function edit(User $account)
    {
        return view('system.accounts.form', compact('account'));
    }

    private function data(Request $request, ?User $account = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($account?->id)],
            'username' => ['nullable', 'regex:/^[a-zA-Z0-9_.-]{3,60}$/', Rule::unique('users')->ignore($account?->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => ['required', 'boolean'],
            'password' => [$account ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $user = new User;
        $user->forceFill($data)->save();

        return redirect()->route('system.accounts.index')->with('success', 'Đã tạo tài khoản.');
    }

    public function update(Request $request, User $account)
    {
        $data = $this->data($request, $account);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        DB::transaction(function () use ($account, $data, $request) {
            $admins = User::where('role', 'admin')->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $locked = User::lockForUpdate()->findOrFail($account->id);
            if ($locked->id === $request->user()->id && ($data['role'] !== 'admin' || ! $data['is_active'])) {
                throw ValidationException::withMessages(['role' => 'Không thể tự khóa hoặc gỡ quyền Admin của tài khoản đang đăng nhập.']);
            }
            if ($locked->isAdmin() && $locked->is_active && ($data['role'] !== 'admin' || ! $data['is_active']) && $admins->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Phải giữ ít nhất một Admin đang hoạt động.']);
            }
            $locked->forceFill($data + ['remember_token' => null])->save();
        });

        return redirect()->route('system.accounts.index')->with('success','Đã cập nhật tài khoản và phân quyền.');
    }
}
