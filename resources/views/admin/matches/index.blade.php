@extends('layouts.admin')

@section('title', '試合')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold">試合</h1>
        <div class="flex flex-wrap gap-2">
            <form method="get" class="flex items-center gap-2 text-sm">
                <select name="season_id" class="rounded-lg border border-slate-300 px-2 py-1" onchange="this.form.submit()">
                    <option value="">全シーズン</option>
                    @foreach ($seasons as $s)
                        <option value="{{ $s->id }}" @selected($seasonId === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('admin.matches.create', ['season_id' => $seasonId]) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">新規試合</a>
        </div>
    </div>
    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-2">試合</th>
                    <th class="px-4 py-2">日時</th>
                    <th class="px-4 py-2">シーズン</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($matches as $m)
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-2 font-medium">{{ $m->title }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $m->held_at->format('Y/m/d H:i') }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $m->season->name }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.matches.teams.index', $m) }}" class="text-sky-700 hover:underline">チーム</a>
                            <span class="text-slate-300">|</span>
                            <a href="{{ route('admin.matches.edit', $m) }}" class="text-sky-700 hover:underline">編集</a>
                            <span class="text-slate-300">|</span>
                            <a href="{{ route('matches.show', $m) }}" class="text-slate-600 hover:underline" target="_blank">公開</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $matches->links() }}</div>
@endsection
