@extends('layouts.admin')

@section('title', '選手')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">選手</h1>
        <a href="{{ route('admin.players.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">新規</a>
    </div>
    <ul class="mt-6 divide-y rounded-xl border border-slate-200 bg-white shadow-sm">
        @foreach ($players as $player)
            <li class="flex items-center justify-between px-4 py-3 text-sm">
                <span class="font-medium">{{ $player->displayLabel() }}</span>
                <span class="flex gap-3">
                    <a href="{{ route('players.show', $player) }}" class="text-slate-600 hover:underline" target="_blank">公開</a>
                    <a href="{{ route('admin.players.edit', $player) }}" class="text-sky-700 hover:underline">編集</a>
                    <form method="post" action="{{ route('admin.players.destroy', $player) }}" onsubmit="return confirm('削除しますか？');">
                        @csrf
                        @method('delete')
                        <button type="submit" class="text-red-600 hover:underline">削除</button>
                    </form>
                </span>
            </li>
        @endforeach
    </ul>
    <div class="mt-4">{{ $players->links() }}</div>
@endsection
