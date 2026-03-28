@extends('layouts.admin')

@section('title', '試合チーム')

@section('content')
    <p class="kfc-muted"><a href="{{ route('admin.matches.index', ['season_id' => $gameMatch->season_id]) }}" class="kfc-link">試合一覧</a></p>
    <h1 class="kfc-page-title mt-1">{{ $gameMatch->title }} — チーム</h1>
    <p class="mt-1 text-sm text-zinc-600">{{ $gameMatch->season->name }}</p>

    <section class="kfc-card mt-8">
        <h2 class="kfc-section-title">登録チーム・投稿URL</h2>
        <ul class="mt-6 space-y-4 text-sm">
            @forelse ($gameMatch->teams as $team)
                <li class="kfc-card-nested !p-5">
                    <p class="font-semibold text-zinc-900">{{ $team->name }}</p>
                    <p class="mt-1 text-zinc-600">{{ $team->players->map(fn ($p) => $p->displayLabel())->implode(' / ') }}</p>
                    <p class="mt-3 break-all text-xs text-zinc-500">
                        投稿URL:
                        <a href="{{ route('entry.show', $team->entry_token) }}" class="kfc-link" target="_blank" rel="noopener noreferrer">{{ url('/entry/'.$team->entry_token) }}</a>
                    </p>
                    @if (! $gameMatch->is_finalized)
                        <form method="post" action="{{ route('admin.matches.teams.destroy', [$gameMatch, $team]) }}" class="mt-3" onsubmit="return confirm('削除しますか？');">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-sm font-medium text-red-600 hover:underline">チーム削除</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="kfc-muted">チームがまだありません。</li>
            @endforelse
        </ul>
    </section>

    @if (! $gameMatch->is_finalized)
        <section class="kfc-card mt-8">
            <h2 class="kfc-section-title">チームを追加</h2>
            <form method="post" action="{{ route('admin.matches.teams.store', $gameMatch) }}" class="mt-6 max-w-lg space-y-5">
                @csrf
                <div>
                    <label class="kfc-label">チーム名</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="kfc-input mt-2">
                </div>
                <div>
                    <label class="kfc-label">選手1</label>
                    <select name="player_a_id" class="kfc-select mt-2" required>
                        <option value="">選択</option>
                        @foreach ($players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_a_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="kfc-label">選手2</label>
                    <select name="player_b_id" class="kfc-select mt-2" required>
                        <option value="">選択</option>
                        @foreach ($players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_b_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="kfc-btn-primary">追加</button>
            </form>
        </section>
    @endif
@endsection
