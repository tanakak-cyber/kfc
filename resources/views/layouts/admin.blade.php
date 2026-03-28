<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '管理') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col md:flex-row">
        <aside class="border-b border-slate-200 bg-slate-900 text-slate-100 md:w-56 md:border-b-0 md:border-r">
            <div class="px-4 py-4 text-sm font-semibold">{{ config('app.name') }} 管理</div>
            <nav class="flex flex-col gap-1 px-2 pb-4 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="rounded px-2 py-2 hover:bg-slate-800">ダッシュボード</a>
                <a href="{{ route('admin.seasons.index') }}" class="rounded px-2 py-2 hover:bg-slate-800">シーズン</a>
                <a href="{{ route('admin.matches.index') }}" class="rounded px-2 py-2 hover:bg-slate-800">試合</a>
                <a href="{{ route('admin.players.index') }}" class="rounded px-2 py-2 hover:bg-slate-800">選手</a>
                <a href="{{ route('admin.catches.pending') }}" class="rounded px-2 py-2 hover:bg-slate-800">釣果承認</a>
                <a href="{{ route('home') }}" class="mt-4 rounded px-2 py-2 text-slate-300 hover:bg-slate-800">サイトを見る</a>
                <form method="post" action="{{ route('logout') }}" class="px-2">
                    @csrf
                    <button type="submit" class="w-full rounded py-2 text-left hover:bg-slate-800">ログアウト</button>
                </form>
            </nav>
        </aside>
        <div class="flex-1">
            <main class="mx-auto max-w-4xl px-4 py-8">
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
        </div>
    </div>
    <x-image-lightbox />
</body>
</html>
