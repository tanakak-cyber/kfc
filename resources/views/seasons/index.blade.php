@extends('layouts.app')

@section('title', 'シーズン一覧')

@section('content')
    <h1 class="kfc-page-title">シーズン一覧</h1>
    <p class="kfc-muted mt-2">開催シーズンを選んで順位や試合を確認できます。</p>
    <ul class="mt-8 space-y-4">
        @foreach ($seasons as $season)
            <li class="kfc-card-sm transition hover:border-emerald-200/60 hover:shadow-md">
                <a href="{{ route('seasons.show', $season) }}" class="kfc-link text-lg">{{ $season->name }}</a>
                <p class="mt-1 text-sm text-zinc-600">{{ $season->starts_on->format('Y/m/d') }} — {{ $season->ends_on->format('Y/m/d') }}</p>
                @if ($season->is_current)
                    <span class="kfc-badge-success mt-3">現在</span>
                @endif
            </li>
        @endforeach
    </ul>
@endsection
