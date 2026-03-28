@extends('layouts.admin')

@section('title', 'シーズン')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="kfc-page-title">シーズン</h1>
            <p class="kfc-muted mt-1">公開ページのシーズン情報を管理します。</p>
        </div>
        <a href="{{ route('admin.seasons.create') }}" class="kfc-btn-primary shrink-0">新規</a>
    </div>
    <ul class="kfc-table-shell mt-8 divide-y divide-zinc-100">
        @foreach ($seasons as $season)
            <li class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold text-zinc-900">{{ $season->name }}</p>
                    <p class="text-sm text-zinc-600">{{ $season->starts_on->format('Y/m/d') }} — {{ $season->ends_on->format('Y/m/d') }}</p>
                    @if ($season->is_current)
                        <span class="kfc-badge-success mt-2">現在</span>
                    @endif
                </div>
                <div class="flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('admin.seasons.edit', $season) }}" class="kfc-link">編集</a>
                    <form method="post" action="{{ route('admin.seasons.destroy', $season) }}" onsubmit="return confirm('削除しますか？');">
                        @csrf
                        @method('delete')
                        <button type="submit" class="font-medium text-red-600 transition hover:text-red-700 hover:underline">削除</button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>
@endsection
