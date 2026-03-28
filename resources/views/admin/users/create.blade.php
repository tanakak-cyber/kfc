@extends('layouts.admin')

@section('title', '管理者を新規発行')

@section('content')
    <h1 class="kfc-page-title">管理者を新規発行</h1>
    <p class="kfc-muted mt-2">発行後、このメールアドレスとパスワードで管理画面にログインできます。</p>

    <form method="post" action="{{ route('admin.users.store') }}" class="kfc-card mt-8 max-w-lg space-y-5">
        @csrf
        <div>
            <label class="kfc-label" for="name">表示名</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required class="kfc-input mt-2" autocomplete="name">
        </div>
        <div>
            <label class="kfc-label" for="email">ログインID（メール）</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required class="kfc-input mt-2" autocomplete="username">
        </div>
        <div>
            <label class="kfc-label" for="password">パスワード</label>
            <input type="password" id="password" name="password" required class="kfc-input mt-2" autocomplete="new-password" minlength="8">
            <p class="mt-1 text-xs text-zinc-500">8文字以上</p>
        </div>
        <div>
            <label class="kfc-label" for="password_confirmation">パスワード（確認）</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required class="kfc-input mt-2" autocomplete="new-password" minlength="8">
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="kfc-btn-primary">作成</button>
            <a href="{{ route('admin.users.index') }}" class="kfc-link-subtle inline-flex items-center py-2.5 text-sm">一覧へ戻る</a>
        </div>
    </form>
@endsection
