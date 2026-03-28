@extends('layouts.admin')

@section('title', 'ロゴ・サイト名')

@section('content')
    <h1 class="kfc-page-title">ロゴ・サイト名</h1>
    <p class="kfc-muted mt-2">ヘッダーに表示するロゴ画像とサイト名（任意）を設定します。画像は <code class="rounded bg-zinc-100 px-1 font-mono text-[0.7rem]">storage/app/public/site</code> に保存されます。</p>
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
        <button type="submit" class="kfc-btn-primary">保存</button>
    </form>
@endsection
