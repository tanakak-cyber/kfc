<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManageController extends Controller
{
    public function index(): View
    {
        $users = User::query()->orderBy('email')->paginate(30);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', '管理者アカウントを作成しました。');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'アカウントを更新しました。');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (User::query()->count() <= 1) {
            return back()->withErrors(['user' => '最後の管理者アカウントは削除できません。']);
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'ログイン中の自分自身は削除できません。']);
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'アカウントを削除しました。');
    }
}
