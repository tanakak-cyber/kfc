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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- 順位表: 未ビルドの古い app.css に残る overflow 対策（public/css はそのまま配信） --}}
    <link rel="stylesheet" href="{{ asset('css/kfc-tables.css') }}?v=19" />
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-32 top-0 h-96 w-96 rounded-full bg-emerald-400/15 blur-3xl"></div>
        <div class="absolute -right-24 top-48 h-80 w-80 rounded-full bg-teal-400/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-zinc-300/20 blur-3xl"></div>
    </div>
    <header class="sticky top-0 z-40 border-b border-zinc-200/80 bg-white/80 backdrop-blur-md backdrop-saturate-150">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="group flex items-center gap-2">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white shadow-md shadow-zinc-900/10 ring-1 ring-zinc-200/80">
                    <img src="{{ $siteLogoUrl }}" alt="" class="h-full w-full object-contain p-1">
                </span>
                <span class="text-base font-bold tracking-tight text-zinc-900">{{ $headerSiteTitle }}</span>
            </a>
            <nav class="flex flex-wrap items-center gap-1 sm:gap-2">
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
        </div>
    </header>
    <main class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
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
    <footer class="border-t border-zinc-200/80 bg-white/60 py-8 text-center text-xs text-zinc-500 backdrop-blur-sm">
        <p>&copy; {{ date('Y') }} {{ $headerSiteTitle }}</p>
    </footer>
    <x-image-lightbox />
    @stack('scripts')
</body>
</html>
