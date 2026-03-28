@extends('layouts.admin')

@section('title', 'ロゴ・メインビジュアル')

@section('content')
    <h1 class="kfc-page-title">ロゴ・メインビジュアル</h1>
    <p class="kfc-muted mt-2">ヘッダーのロゴ・トップのメインビジュアル・サイト名（任意）を設定します。画像は <code class="rounded bg-zinc-100 px-1 font-mono text-[0.7rem]">storage/app/public/site</code> に保存されます。</p>
    <p class="mt-2 text-xs text-zinc-500">文言のチーム名・紹介文は <a href="{{ route('admin.site.edit') }}" class="kfc-link">サイト設定（旧）</a> から変更できます。ここでサイト名を空にすると、そちらのチーム名がヘッダー表記に使われます。</p>

    <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="kfc-card mt-8 max-w-xl space-y-5">
        @csrf
        @method('put')
        <div>
            <label class="kfc-label" for="site_name">サイト名（ヘッダー表記・任意）</label>
            <input
                type="text"
                id="site_name"
                name="site_name"
                value="{{ old('site_name', $siteSetting->site_name) }}"
                maxlength="120"
                class="kfc-input mt-2"
                placeholder="未入力時は「サイト設定」のチーム名を使用"
            >
        </div>
        <div>
            <span class="kfc-label">現在のロゴ</span>
            <div class="mt-3 flex items-center gap-4">
                <img
                    src="{{ $siteSetting->logoPublicUrl() ?? '/images/logo-default.svg' }}"
                    alt=""
                    class="h-14 w-auto max-w-[200px] rounded-lg border border-zinc-200 bg-white object-contain p-1"
                >
            </div>
        </div>
        <div>
            <label class="kfc-label" for="logo">ロゴ画像をアップロード</label>
            <input type="file" id="logo" name="logo" accept="image/*" class="mt-2 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100">
            <p class="mt-2 text-xs text-zinc-500">JPEG / PNG / WebP など（最大5MB）。未設定時はデフォルトのマークが表示されます。</p>
        </div>
        <div class="border-t border-zinc-100 pt-6">
            <span class="kfc-label">トップページ メインビジュアル</span>
            <p class="mt-1 text-xs text-zinc-500">ヒーロー領域の背景として表示されます（横長の写真向き）。未設定のときは従来のグラデーションのみです。</p>
            @if (filled($siteSetting->home_hero_image_path))
                <div class="mt-3 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-900/5">
                    <img
                        src="{{ $siteSetting->homeHeroPublicUrl() }}"
                        alt=""
                        class="max-h-48 w-full object-cover object-center"
                    >
                </div>
            @endif
            <label class="kfc-label mt-4" for="home_hero">画像をアップロード</label>
            <input type="file" id="home_hero" name="home_hero" accept="image/*" class="mt-2 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100">
            <p class="mt-2 text-xs text-zinc-500">最大10MB。アップロードで差し替えられます。</p>
            @if (filled($siteSetting->home_hero_image_path))
                <label class="mt-4 flex cursor-pointer items-center gap-2 text-sm text-zinc-700">
                    <input type="checkbox" name="remove_home_hero" value="1" class="h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                    メインビジュアルを削除する
                </label>
            @endif
        </div>
        <button type="submit" class="kfc-btn-primary">保存</button>
    </form>
@endsection
