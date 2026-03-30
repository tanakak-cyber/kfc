@extends('layouts.admin')

@section('title', '試合チーム')

@section('content')
    <p class="kfc-muted"><a href="{{ route('admin.matches.index', ['season_id' => $gameMatch->season_id]) }}" class="kfc-link">試合一覧</a></p>
    <h1 class="kfc-page-title mt-1">{{ $gameMatch->title }} — チーム</h1>
    <p class="mt-1 text-sm text-zinc-600">{{ $gameMatch->season->name }}</p>

    @include('admin.partials.mail_transport_notice')

    @php
        $canBulkEntryMail = $gameMatch->teams->contains(
            fn ($t) => $t->players->contains(fn ($p) => filled($p->email))
        );
    @endphp
    @if ($canBulkEntryMail)
        <form method="post" action="{{ route('admin.matches.entry-mail.all', $gameMatch) }}" class="mt-4" onsubmit="return confirm('出席者・チームメンバーへ、登録メールがある人すべてに釣果投稿URLを送信します。よろしいですか？');">
            @csrf
            <button type="submit" class="inline-flex items-center rounded-lg border border-sky-300 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-900 shadow-sm transition hover:bg-sky-100">
                参加者全員にメールを送信
            </button>
            <p class="mt-2 text-xs text-zinc-500">チームメンバーにメールが登録されている選手へ、それぞれの投稿URL（チーム単位は同一URL）を送ります。</p>
        </form>
    @endif

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
                    @include('admin.partials.entry_share_copy_button', [
                        'gameMatch' => $gameMatch,
                        'entryUrl' => url('/entry/'.$team->entry_token),
                        'entryMailAction' => $team->players->contains(fn ($p) => filled($p->email))
                            ? route('admin.matches.entry-mail.team', [$gameMatch, $team])
                            : null,
                    ])
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
                    <label class="kfc-label">メンバー1（必須）</label>
                    <select name="player_a_id" class="kfc-select mt-2" required>
                        <option value="">選択</option>
                        @foreach ($players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_a_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="kfc-label">メンバー2（任意）</label>
                    <select name="player_b_id" class="kfc-select mt-2">
                        <option value="">1人チームの場合は空のまま</option>
                        @foreach ($players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_b_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-zinc-500">1人チームも登録できます。2人目を選ぶと通常の2人チームになります。</p>
                </div>
                <button type="submit" class="kfc-btn-primary">追加</button>
            </form>
        </section>
    @endif
@endsection
