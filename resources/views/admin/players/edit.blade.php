@extends('layouts.admin')

@section('title', '選手編集')

@section('content')
    <h1 class="kfc-page-title">選手編集</h1>
    <form method="post" action="{{ route('admin.players.update', $player) }}" enctype="multipart/form-data" class="kfc-card mt-8 max-w-lg space-y-5">
        @csrf
        @method('put')
        <div>
            <label class="kfc-label">名前（本名・管理用）</label>
            <input type="text" name="name" value="{{ old('name', $player->name) }}" required class="kfc-input mt-2">
        </div>
        <div>
            <label class="kfc-label">表示名</label>
            <input type="text" name="display_name" value="{{ old('display_name', $player->display_name) }}" class="kfc-input mt-2">
        </div>
        <div>
            <label class="kfc-label">メールアドレス（任意・釣果投稿URLの案内用）</label>
            <input type="email" name="email" value="{{ old('email', $player->email) }}" class="kfc-input mt-2" autocomplete="email" placeholder="未登録の場合はメール送信できません">
        </div>
        <div>
            <label class="kfc-label">アイコン画像</label>
            @if ($player->icon)
                <p class="mt-2 text-xs text-zinc-500">現在: {{ $player->icon }}</p>
            @endif
            <input type="file" name="icon" accept="image/*" class="mt-2 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100">
        </div>
        <button type="submit" class="kfc-btn-primary">更新</button>
    </form>
@endsection
