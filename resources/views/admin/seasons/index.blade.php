@extends('layouts.admin')

@section('title', 'シーズン')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold">シーズン</h1>
        <a href="{{ route('admin.seasons.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">新規</a>
    </div>
    <ul class="mt-6 divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white shadow-sm">
        @foreach ($seasons as $season)
            <li class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold">{{ $season->name }}</p>
                    <p class="text-sm text-slate-600">{{ $season->starts_on->format('Y/m/d') }} — {{ $season->ends_on->format('Y/m/d') }}</p>
                    @if ($season->is_current)
                        <span class="mt-1 inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800">現在</span>
                    @endif
                </div>
                <div class="flex gap-2 text-sm">
                    <a href="{{ route('admin.seasons.edit', $season) }}" class="text-sky-700 hover:underline">編集</a>
                    <form method="post" action="{{ route('admin.seasons.destroy', $season) }}" onsubmit="return confirm('削除しますか？');">
                        @csrf
                        @method('delete')
                        <button type="submit" class="text-red-600 hover:underline">削除</button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>
@endsection
