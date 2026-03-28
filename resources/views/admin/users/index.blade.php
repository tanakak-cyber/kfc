@extends('layouts.admin')

@section('title', '管理者アカウント')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="kfc-page-title">管理者アカウント</h1>
            <p class="kfc-muted mt-1">ログインはメールアドレスとパスワードです。新規アカウントを発行できます。</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="kfc-btn-primary shrink-0">新規発行</a>
    </div>

    @error('user')
        <p class="kfc-alert-error mt-6">{{ $message }}</p>
    @enderror

    <div class="kfc-table-shell mt-8 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="kfc-thead">
                <tr>
                    <th class="px-4 py-3">表示名</th>
                    <th class="px-4 py-3">ログインID（メール）</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="kfc-trow">
                        <td class="px-4 py-3 font-medium text-zinc-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('admin.users.edit', $user) }}" class="kfc-link">編集</a>
                            @if ($user->id !== auth()->id())
                                <span class="mx-1.5 text-zinc-300">·</span>
                                <form method="post" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('このアカウントを削除しますか？');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="font-medium text-red-600 hover:underline">削除</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
@endsection
