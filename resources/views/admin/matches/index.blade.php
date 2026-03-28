@extends('layouts.admin')

@section('title', '試合')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="kfc-page-title">試合</h1>
            <p class="kfc-muted mt-1">シーズンで絞り込み、編集やチーム登録ができます。</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="get" class="flex items-center gap-2 text-sm">
                <select name="season_id" class="kfc-select min-w-[10rem] py-2 text-sm" onchange="this.form.submit()">
                    <option value="">全シーズン</option>
                    @foreach ($seasons as $s)
                        <option value="{{ $s->id }}" @selected($seasonId === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('admin.matches.create', ['season_id' => $seasonId]) }}" class="kfc-btn-primary">新規試合</a>
        </div>
    </div>
    <div class="kfc-table-shell mt-8 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="kfc-thead">
                <tr>
                    <th class="px-4 py-3">試合</th>
                    <th class="px-4 py-3">形式</th>
                    <th class="px-4 py-3">日時</th>
                    <th class="px-4 py-3">シーズン</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($matches as $m)
                    <tr class="kfc-trow">
                        <td class="px-4 py-3 font-semibold text-zinc-900">{{ $m->title }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $m->match_type->label() }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $m->held_at->format('Y/m/d H:i') }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $m->season->name }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            @if ($m->isTeamMatch())
                                <a href="{{ route('admin.matches.teams.index', $m) }}" class="kfc-link">チーム</a>
                            @else
                                <a href="{{ route('admin.matches.participants.index', $m) }}" class="kfc-link">参加者</a>
                            @endif
                            <span class="mx-1.5 text-zinc-300">·</span>
                            <a href="{{ route('admin.matches.edit', $m) }}" class="kfc-link">編集</a>
                            <span class="mx-1.5 text-zinc-300">·</span>
                            <a href="{{ route('matches.show', $m) }}" class="kfc-link-subtle" target="_blank" rel="noopener noreferrer">公開</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $matches->links() }}</div>
@endsection
