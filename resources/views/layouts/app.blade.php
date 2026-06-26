<!DOCTYPE html>
<html lang="ja" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if (! empty($siteNoindex))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <title>@yield('title', $headerSiteTitle)</title>
    @include('partials.favicon')
    @hasSection('hero')
        {{-- 水中背景・バス透かしを先読みして描画の遅延を防ぐ --}}
        <link rel="preload" as="image" href="{{ asset('images/water-bg.webp') }}?v=9" media="(min-width: 769px)">
        <link rel="preload" as="image" href="{{ asset('images/water-bg-mobile.webp') }}?v=9" media="(max-width: 768px)">
        <link rel="preload" as="image" href="{{ asset('images/fish-watermark.webp') }}?v=4">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- 順位表: 未ビルドの古い app.css に残る overflow 対策（public/css はそのまま配信） --}}
    <link rel="stylesheet" href="{{ asset('css/kfc-tables.css') }}?v=21" />
</head>
<body class="@hasSection('hero') kfc-has-hero @endif min-h-screen kfc-app-bg text-zinc-900 antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        @hasSection('hero')
            <div class="kfc-side-vignette"></div>
        @endif
        <div class="absolute -left-32 top-0 h-96 w-96 rounded-full bg-emerald-700/10 blur-3xl"></div>
        <div class="absolute -right-24 top-48 h-80 w-80 rounded-full bg-teal-700/10 blur-3xl"></div>
    </div>
    <header class="kfc-header sticky top-0 z-40 border-b-[3px] border-amber-400/85 shadow-lg shadow-emerald-950/30 backdrop-blur-md">
        <div class="mx-auto flex max-w-[1120px] items-center justify-between gap-4 px-4 py-3.5 sm:px-6">
            <a href="{{ route('home') }}" class="group flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white shadow-md shadow-black/20 ring-1 ring-amber-300/50">
                    <img src="{{ $siteLogoUrl }}" alt="" class="h-full w-full object-contain p-1">
                </span>
                <span class="text-xl font-black tracking-wide text-amber-300 drop-shadow-[0_1px_2px_rgba(0,0,0,0.5)] sm:text-2xl">{{ $headerSiteTitle }}</span>
            </a>
            {{-- PC: 横並びナビ --}}
            <nav class="hidden items-center gap-1 sm:flex sm:gap-2">
                <a href="{{ route('seasons.index') }}" class="kfc-nav-pill">シーズン</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="kfc-nav-pill">管理</a>
                    <form method="post" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="kfc-nav-pill">ログアウト</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="kfc-nav-pill-active">管理者ログイン</a>
                @endauth
            </nav>
            {{-- SP: ハンバーガーボタン --}}
            <button id="kfc-nav-toggle" type="button" class="kfc-nav-burger sm:hidden" aria-expanded="false" aria-controls="kfc-nav-menu" aria-label="メニューを開く">
                <svg data-kfc-nav-icon-menu class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
                <svg data-kfc-nav-icon-close class="hidden h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                </svg>
            </button>
        </div>
        {{-- SP: 開閉メニュー --}}
        <div id="kfc-nav-menu" class="kfc-nav-menu hidden sm:hidden">
            <nav class="mx-auto flex max-w-[1120px] flex-col gap-1.5 px-4 pb-4 pt-1">
                <a href="{{ route('seasons.index') }}" class="kfc-nav-mobile-item">シーズン</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="kfc-nav-mobile-item">管理</a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="kfc-nav-mobile-item w-full text-left">ログアウト</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="kfc-nav-mobile-item kfc-nav-mobile-item--primary">管理者ログイン</a>
                @endauth
            </nav>
        </div>
    </header>
    @hasSection('hero')
        @yield('hero')
    @endif
    <main class="@hasSection('hero') kfc-main--overlap @else mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:py-12 @endif">
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
    <footer class="kfc-footer mt-16 border-t-4 border-amber-400/85 px-6 py-12 text-center">
        <p class="text-2xl font-black tracking-wide text-amber-300 drop-shadow-[0_1px_2px_rgba(0,0,0,0.5)] sm:text-3xl">{{ $headerSiteTitle }}</p>
        <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-200/70">Bass Fishing Tournament</p>
        <p class="mt-4 text-xs text-emerald-100/70">&copy; {{ date('Y') }} {{ $headerSiteTitle }}</p>
    </footer>
    <x-image-lightbox />
    @stack('scripts')
</body>
</html>
