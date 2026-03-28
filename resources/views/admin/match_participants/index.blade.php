@extends('layouts.admin')

@section('title', '試合参加者')

@section('content')
    <p class="kfc-muted"><a href="{{ route('admin.matches.index', ['season_id' => $gameMatch->season_id]) }}" class="kfc-link">試合一覧</a></p>
    <h1 class="kfc-page-title mt-1">{{ $gameMatch->title }} — 参加者</h1>
    <p class="mt-1 text-sm text-zinc-600">{{ $gameMatch->season->name }} · {{ $gameMatch->match_type->label() }}</p>
    <p class="kfc-muted mt-2">
        <a href="{{ route('admin.matches.edit', $gameMatch) }}" class="kfc-link">試合編集に戻る</a>
    </p>

    <section class="kfc-card mt-8">
        <h2 class="kfc-section-title">登録参加者・投稿URL</h2>
        <ul class="mt-6 space-y-4 text-sm">
            @forelse ($gameMatch->matchParticipants as $participant)
                <li class="kfc-card-nested !p-5">
                    <p class="font-semibold text-zinc-900">{{ $participant->player->displayLabel() }}</p>
                    <p class="mt-2 flex flex-wrap items-center gap-2">
                        @if ($participant->is_present)
                            <span class="kfc-badge-success">出席</span>
                        @else
                            <span class="kfc-badge-warn">欠席（0pt・投稿不可）</span>
                        @endif
                    </p>
                    @if ($participant->is_present)
                        <p class="mt-3 break-all text-xs text-zinc-500">
                            投稿URL:
                            <a href="{{ route('entry.show', $participant->entry_token) }}" class="kfc-link" target="_blank" rel="noopener noreferrer">{{ url('/entry/'.$participant->entry_token) }}</a>
                        </p>
                        @include('admin.partials.entry_share_copy_button', [
                            'gameMatch' => $gameMatch,
                            'entryUrl' => url('/entry/'.$participant->entry_token),
                        ])
                    @endif
                    @if (! $gameMatch->is_finalized)
                        <div class="mt-4 flex flex-wrap gap-3">
                            <form method="post" action="{{ route('admin.matches.participants.presence', [$gameMatch, $participant]) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <label class="text-xs text-zinc-600">出席状態</label>
                                <select name="is_present" class="kfc-select py-1.5 text-sm" onchange="this.form.submit()">
                                    <option value="1" @selected($participant->is_present)>出席</option>
                                    <option value="0" @selected(! $participant->is_present)>欠席</option>
                                </select>
                            </form>
                            <form method="post" action="{{ route('admin.matches.participants.destroy', [$gameMatch, $participant]) }}" onsubmit="return confirm('この参加者を削除しますか？');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:underline">削除</button>
                            </form>
                        </div>
                    @endif
                </li>
            @empty
                <li class="kfc-muted">参加者がまだいません。</li>
            @endforelse
        </ul>
    </section>

    @if (! $gameMatch->is_finalized)
        <section class="kfc-card mt-8">
            <h2 class="kfc-section-title">参加者を追加</h2>
            <form method="post" action="{{ route('admin.matches.participants.store', $gameMatch) }}" class="mt-6 max-w-lg space-y-5">
                @csrf
                <div>
                    <label class="kfc-label">選手</label>
                    <select name="player_id" class="kfc-select mt-2" required>
                        <option value="">選択</option>
                        @foreach ($players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="kfc-btn-primary">追加</button>
            </form>
        </section>
    @endif
@endsection
