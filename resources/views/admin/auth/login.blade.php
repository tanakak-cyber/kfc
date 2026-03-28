<!DOCTYPE html>
<html lang="ja" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if (! empty($siteNoindex))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <title>管理者ログイン — {{ $siteTeamName }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-zinc-100 px-4">
    <div class="pointer-events-none fixed inset-0 -z-10">
        <div class="absolute left-1/4 top-0 h-96 w-96 -translate-x-1/2 rounded-full bg-emerald-400/20 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-teal-400/15 blur-3xl"></div>
    </div>
    <div class="w-full max-w-md rounded-3xl border border-zinc-200/80 bg-white/90 p-8 shadow-2xl shadow-zinc-950/10 ring-1 ring-zinc-950/[0.04] backdrop-blur-sm">
        <div class="mx-auto mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-lg font-bold text-white shadow-lg">A</div>
        <h1 class="text-center text-xl font-bold tracking-tight text-zinc-900">管理者ログイン</h1>
        @if ($errors->any())
            <div class="kfc-alert-error mt-5">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="post" action="{{ url('/admin/login') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label class="kfc-label">ログインID（メール）</label>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="kfc-input mt-2">
            </div>
            <div>
                <label class="kfc-label">パスワード</label>
                <input type="password" name="password" required autocomplete="current-password" class="kfc-input mt-2">
            </div>
            <label class="flex items-center gap-2 text-sm text-zinc-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/30">
                ログイン状態を保持
            </label>
            <button type="submit" class="kfc-btn-primary w-full">ログイン</button>
        </form>
        <p class="mt-8 text-center text-xs text-zinc-500">
            <a href="{{ route('home') }}" class="kfc-link">サイトへ戻る</a>
        </p>
    </div>
</body>
</html>
