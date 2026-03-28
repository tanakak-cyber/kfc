@extends('layouts.app')

@section('title', 'シーズン一覧')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">シーズン一覧</h1>
    <ul class="mt-6 space-y-3">
        @foreach ($seasons as $season)
            <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <a href="{{ route('seasons.show', $season) }}" class="text-lg font-semibold text-sky-700 hover:underline">{{ $season->name }}</a>
                <p class="text-sm text-slate-600">{{ $season->starts_on->format('Y/m/d') }} — {{ $season->ends_on->format('Y/m/d') }}</p>
                @if ($season->is_current)
                    <span class="mt-2 inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">現在</span>
                @endif
            </li>
        @endforeach
    </ul>
@endsection
