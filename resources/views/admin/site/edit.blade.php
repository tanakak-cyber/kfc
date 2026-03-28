@extends('layouts.admin')

@section('title', 'サイト設定')

@section('content')
    <h1 class="kfc-page-title">サイト設定</h1>
    <p class="kfc-muted mt-2">トップページの見出し・ヘッダー・フッター・タイトルに表示されるチーム名（サイト名）を設定します。</p>

    <form method="post" action="{{ route('admin.site.update') }}" class="kfc-card mt-8 max-w-xl space-y-5">
        @csrf
        @method('put')
        <div>
            <label class="kfc-label" for="team_name">チーム名</label>
            <input
                type="text"
                id="team_name"
                name="team_name"
                value="{{ old('team_name', $teamName) }}"
                required
                maxlength="120"
                class="kfc-input mt-2"
                placeholder="例: ○○バスクラブ"
            >
            <p class="mt-2 text-xs text-zinc-500">未登録時は <code class="rounded bg-zinc-100 px-1 font-mono text-[0.7rem]">config/app.php</code> のアプリ名（環境変数 APP_NAME）が使われます。</p>
        </div>
        <button type="submit" class="kfc-btn-primary">保存</button>
    </form>
@endsection
