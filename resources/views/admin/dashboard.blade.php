@extends('layouts.admin')

@section('title', 'ダッシュボード')

@section('content')
    <h1 class="kfc-page-title">ダッシュボード</h1>
    <p class="kfc-muted mt-2">概要と未処理の釣果を確認できます。</p>
    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="kfc-card-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">シーズン</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-zinc-900">{{ $seasonsCount }}</p>
        </div>
        <div class="kfc-card-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">試合</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-zinc-900">{{ $matchesCount }}</p>
        </div>
        <div class="kfc-card-sm border-emerald-100/80 bg-gradient-to-br from-emerald-50/80 to-white">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800/80">未承認釣果</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-emerald-900">{{ $pendingCatches }}</p>
            <a href="{{ route('admin.catches.pending') }}" class="kfc-link mt-3 inline-block text-sm">承認へ →</a>
        </div>
    </div>
@endsection
