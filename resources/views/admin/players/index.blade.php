@extends('layouts.admin')

@section('title', '選手')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="kfc-page-title">選手</h1>
            <p class="kfc-muted mt-1">表示名・アイコンを管理します。</p>
        </div>
        <a href="{{ route('admin.players.create') }}" class="kfc-btn-primary shrink-0">新規</a>
    </div>
    <ul class="kfc-table-shell mt-8 divide-y divide-zinc-100">
        @foreach ($players as $player)
            <li class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <span class="font-semibold text-zinc-900">{{ $player->displayLabel() }}</span>
                <span class="flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('players.show', $player) }}" class="kfc-link-subtle" target="_blank" rel="noopener noreferrer">公開</a>
                    <a href="{{ route('admin.players.edit', $player) }}" class="kfc-link">編集</a>
                    <form method="post" action="{{ route('admin.players.destroy', $player) }}" onsubmit="return confirm('削除しますか？');">
                        @csrf
                        @method('delete')
                        <button type="submit" class="font-medium text-red-600 transition hover:text-red-700 hover:underline">削除</button>
                    </form>
                </span>
            </li>
        @endforeach
    </ul>
    <div class="mt-6">{{ $players->links() }}</div>
@endsection
