<!DOCTYPE html>
<html lang="ja" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '管理') — {{ $siteTeamName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased">
    <div class="flex min-h-screen flex-col md:flex-row">
        <aside class="border-b border-zinc-800/50 bg-gradient-to-b from-zinc-950 via-zinc-900 to-zinc-950 text-zinc-100 md:w-60 md:shrink-0 md:border-b-0 md:border-r md:border-zinc-800/50">
            <div class="flex items-center gap-2 border-b border-white/5 px-4 py-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/20 text-sm font-bold text-emerald-300 ring-1 ring-emerald-400/30">A</span>
                <div class="min-w-0">
                    <p class="truncate text-xs font-medium uppercase tracking-wider text-zinc-500">Admin</p>
                    <p class="truncate text-sm font-semibold text-white">{{ $siteTeamName }}</p>
                </div>
            </div>
            <nav class="flex flex-col gap-0.5 p-3 pb-6 text-sm">
                @php
                    $link = function (bool $active) {
                        return $active
                            ? 'flex items-center rounded-xl bg-emerald-500/15 px-3 py-2.5 font-semibold text-emerald-100 ring-1 ring-emerald-400/25'
                            : 'flex items-center rounded-xl px-3 py-2.5 font-medium text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100';
                    };
                @endphp
                <a href="{{ route('admin.dashboard') }}" class="{{ $link(request()->routeIs('admin.dashboard')) }}">ダッシュボード</a>
                <a href="{{ route('admin.settings.edit') }}" class="{{ $link(request()->routeIs('admin.settings.*')) }}">ロゴ・サイト名</a>
                <a href="{{ route('admin.site.edit') }}" class="{{ $link(request()->routeIs('admin.site.*')) }}">サイト設定</a>
                <a href="{{ route('admin.users.index') }}" class="{{ $link(request()->routeIs('admin.users.*')) }}">管理者アカウント</a>
                <a href="{{ route('admin.seasons.index') }}" class="{{ $link(request()->routeIs('admin.seasons.*')) }}">シーズン</a>
                <a href="{{ route('admin.match-surveys.index') }}" class="{{ $link(request()->routeIs('admin.match-surveys.*')) }}">出欠アンケート</a>
                <a href="{{ route('admin.matches.index') }}" class="{{ $link(request()->routeIs('admin.matches.*')) }}">試合</a>
                <a href="{{ route('admin.players.index') }}" class="{{ $link(request()->routeIs('admin.players.*')) }}">選手</a>
                <a href="{{ route('admin.catches.pending') }}" class="{{ $link(request()->routeIs('admin.catches.*')) }}">釣果承認</a>
                <div class="my-3 border-t border-white/5"></div>
                <a href="{{ route('home') }}" class="flex items-center rounded-xl px-3 py-2.5 text-zinc-500 transition hover:bg-white/5 hover:text-zinc-200">サイトを見る</a>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="mt-1 w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium text-zinc-500 transition hover:bg-white/5 hover:text-zinc-200">ログアウト</button>
                </form>
            </nav>
        </aside>
        <div class="min-w-0 flex-1">
            <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
                @if (session('status'))
                    <div class="kfc-alert-success" role="status">
                        {{ session('status') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="kfc-alert-error" role="alert">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    <x-image-lightbox />
    @stack('scripts')
</body>
</html>
