<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>管理者ログイン — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
    <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-center text-lg font-semibold text-slate-900">管理者ログイン</h1>
        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="post" action="{{ url('/admin/login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">メール</label>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">パスワード</label>
                <input type="password" name="password" required autocomplete="current-password"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                ログイン状態を保持
            </label>
            <button type="submit" class="w-full rounded-lg bg-slate-900 py-2 text-sm font-medium text-white hover:bg-slate-800">ログイン</button>
        </form>
        <p class="mt-6 text-center text-xs text-slate-500">
            <a href="{{ route('home') }}" class="text-sky-700 hover:underline">サイトへ戻る</a>
        </p>
    </div>
</body>
</html>
