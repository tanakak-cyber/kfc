@extends('layouts.admin')

@section('title', '管理者を編集')

@section('content')
    <h1 class="kfc-page-title">管理者を編集</h1>
    <p class="kfc-muted mt-2">{{ $user->email }}</p>

    <form method="post" action="{{ route('admin.users.update', $user) }}" class="kfc-card mt-8 max-w-lg space-y-5">
        @csrf
        @method('put')
        <div>
            <label class="kfc-label" for="name">表示名</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="kfc-input mt-2" autocomplete="name">
        </div>
        <div>
            <label class="kfc-label" for="email">ログインID（メール）</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="kfc-input mt-2" autocomplete="username">
        </div>
        <div>
            <label class="kfc-label" for="password">新しいパスワード</label>
            <input type="password" id="password" name="password" class="kfc-input mt-2" autocomplete="new-password" minlength="8">
            <p class="mt-1 text-xs text-zinc-500">変更しない場合は空のままにしてください。</p>
        </div>
        <div>
            <label class="kfc-label" for="password_confirmation">新しいパスワード（確認）</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="kfc-input mt-2" autocomplete="new-password" minlength="8">
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="kfc-btn-primary">更新</button>
            <a href="{{ route('admin.users.index') }}" class="kfc-link-subtle inline-flex items-center py-2.5 text-sm">一覧へ戻る</a>
        </div>
    </form>
@endsection
