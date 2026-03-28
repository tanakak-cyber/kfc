@extends('layouts.admin')

@section('title', 'サイト設定')

@section('content')
    <h1 class="kfc-page-title">サイト設定</h1>
    <p class="kfc-muted mt-2">トップページの見出し・紹介文、およびヘッダー・フッター・タイトルに使うチーム名（サイト名）を設定します。</p>

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
        <div>
            <label class="kfc-label" for="home_tagline">トップページの紹介文</label>
            <textarea
                id="home_tagline"
                name="home_tagline"
                rows="3"
                maxlength="500"
                class="kfc-input mt-2 min-h-[5.5rem] resize-y"
                placeholder="{{ $homeTaglineDefault }}"
            >{{ old('home_tagline', $homeTagline) }}</textarea>
            <p class="mt-2 text-xs text-zinc-500">ヒーロー見出し直下の説明文です。空欄で保存すると初期の定型文に戻ります（最大500文字）。</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4">
            <label class="flex cursor-pointer items-start gap-3">
                <input type="hidden" name="site_noindex" value="0">
                <input
                    type="checkbox"
                    name="site_noindex"
                    value="1"
                    class="mt-1 h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                    @checked(old('site_noindex', $siteNoindexEnabled ? '1' : '0') === '1')
                >
                <span>
                    <span class="kfc-label !mb-0">全ページに noindex を付与する</span>
                    <span class="mt-1 block text-xs font-normal text-zinc-600">
                        オンにすると、公開ページ・管理画面・ログイン画面のすべての HTML に <code class="rounded bg-white px-1 font-mono text-[0.7rem] text-zinc-800">&lt;meta name=&quot;robots&quot; content=&quot;noindex, nofollow&quot;&gt;</code> を出力し、検索エンジンのインデックスを避けやすくします。
                    </span>
                </span>
            </label>
        </div>
        <button type="submit" class="kfc-btn-primary">保存</button>
    </form>
@endsection
