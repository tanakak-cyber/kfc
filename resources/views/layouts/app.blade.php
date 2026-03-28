<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-4">
            <a href="{{ route('home') }}" class="text-lg font-semibold text-slate-800">{{ config('app.name') }}</a>
            <nav class="flex flex-wrap items-center gap-3 text-sm font-medium text-slate-600">
                <a href="{{ route('seasons.index') }}" class="hover:text-slate-900">シーズン</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-900">管理</a>
                    <form method="post" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="hover:text-slate-900">ログアウト</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-slate-900">管理者ログイン</a>
                @endauth
            </nav>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </footer>
    <x-image-lightbox />
</body>
</html>
