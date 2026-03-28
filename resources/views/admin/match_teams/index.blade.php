@extends('layouts.admin')

@section('title', '試合チーム')

@section('content')
    <p class="text-sm text-slate-500"><a href="{{ route('admin.matches.index', ['season_id' => $gameMatch->season_id]) }}" class="text-sky-700 hover:underline">試合一覧</a></p>
    <h1 class="text-2xl font-bold">{{ $gameMatch->title }} — チーム</h1>
    <p class="text-sm text-slate-600">{{ $gameMatch->season->name }}</p>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">登録チーム・投稿URL</h2>
        <ul class="mt-4 space-y-4 text-sm">
            @forelse ($gameMatch->teams as $team)
                <li class="rounded-lg border border-slate-100 p-4">
                    <p class="font-semibold">{{ $team->name }}</p>
                    <p class="text-slate-600">{{ $team->players->map(fn ($p) => $p->displayLabel())->implode(' / ') }}</p>
                    <p class="mt-2 break-all text-xs text-slate-500">
                        投稿URL:
                        <a href="{{ route('entry.show', $team->entry_token) }}" class="text-sky-700 hover:underline" target="_blank">{{ url('/entry/'.$team->entry_token) }}</a>
                    </p>
                    @if (! $gameMatch->is_finalized)
                        <form method="post" action="{{ route('admin.matches.teams.destroy', [$gameMatch, $team]) }}" class="mt-2" onsubmit="return confirm('削除しますか？');">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-red-600 hover:underline">チーム削除</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="text-slate-500">チームがまだありません。</li>
            @endforelse
        </ul>
    </section>

    @if (! $gameMatch->is_finalized)
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">チームを追加</h2>
            <form method="post" action="{{ route('admin.matches.teams.store', $gameMatch) }}" class="mt-4 space-y-4 max-w-lg">
                @csrf
                <div>
                    <label class="block text-sm font-medium">チーム名</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">選手1</label>
                    <select name="player_a_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                        <option value="">選択</option>
                        @foreach ($players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_a_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">選手2</label>
                    <select name="player_b_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                        <option value="">選択</option>
                        @foreach ($players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_b_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">追加</button>
            </form>
        </section>
    @endif
@endsection
