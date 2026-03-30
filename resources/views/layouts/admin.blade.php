<!DOCTYPE html>
<html lang="ja" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if (! empty($siteNoindex))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <title>@yield('title', '管理') — {{ $siteTeamName }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased">
    <div class="flex min-h-screen flex-col md:flex-row">
        <header class="sticky top-0 z-[60] flex h-14 shrink-0 items-center justify-between gap-3 border-b border-zinc-800/50 bg-zinc-950 px-3 text-zinc-100 md:hidden">
            <button
                type="button"
                id="kfc-admin-menu-toggle"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-white/15 text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
                aria-controls="kfc-admin-sidebar"
                aria-expanded="false"
                aria-label="管理メニューを開く"
            >
                <svg data-kfc-admin-icon-menu class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg data-kfc-admin-icon-close class="hidden h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <p class="min-w-0 flex-1 truncate text-center text-sm font-semibold text-white">{{ $siteTeamName }}</p>
            <span class="w-10 shrink-0" aria-hidden="true"></span>
        </header>

        <div
            id="kfc-admin-overlay"
            class="fixed bottom-0 left-0 right-0 top-14 z-40 bg-zinc-950/60 opacity-0 pointer-events-none transition-opacity duration-200 md:hidden"
            aria-hidden="true"
        ></div>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col md:flex-row">
        <aside
            id="kfc-admin-sidebar"
            class="fixed left-0 top-14 z-50 flex h-[calc(100dvh-3.5rem)] w-64 max-w-[min(100vw,16rem)] flex-col border-r border-zinc-800/50 bg-gradient-to-b from-zinc-950 via-zinc-900 to-zinc-950 text-zinc-100 md:static md:top-auto md:z-auto md:flex md:h-auto md:min-h-screen md:w-60 md:max-w-none md:shrink-0 md:border-b-0 md:border-r"
        >
            <div class="flex items-center gap-2 border-b border-white/5 px-4 py-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/20 text-sm font-bold text-emerald-300 ring-1 ring-emerald-400/30">A</span>
                <div class="min-w-0">
                    <p class="truncate text-xs font-medium uppercase tracking-wider text-zinc-500">Admin</p>
                    <p class="truncate text-sm font-semibold text-white">{{ $siteTeamName }}</p>
                </div>
            </div>
            <nav class="flex flex-col gap-0.5 overflow-y-auto p-3 pb-6 text-sm">
                @php
                    $link = function (bool $active) {
                        return $active
                            ? 'flex items-center rounded-xl bg-emerald-500/15 px-3 py-2.5 font-semibold text-emerald-100 ring-1 ring-emerald-400/25'
                            : 'flex items-center rounded-xl px-3 py-2.5 font-medium text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100';
                    };
                @endphp
                <a href="{{ route('admin.dashboard') }}" class="{{ $link(request()->routeIs('admin.dashboard')) }}">ダッシュボード</a>
                <a href="{{ route('admin.settings.edit') }}" class="{{ $link(request()->routeIs('admin.settings.*')) }}">ロゴ・メイン画像</a>
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
        <div class="min-w-0 flex-1 md:min-h-screen">
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
    </div>
    <x-image-lightbox />
    @stack('scripts')
</body>
</html>
