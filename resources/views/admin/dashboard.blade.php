@extends('layouts.admin')

@section('title', 'ダッシュボード')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">ダッシュボード</h1>
    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">シーズン</p>
            <p class="text-2xl font-semibold">{{ $seasonsCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">試合</p>
            <p class="text-2xl font-semibold">{{ $matchesCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">未承認釣果</p>
            <p class="text-2xl font-semibold">{{ $pendingCatches }}</p>
            <a href="{{ route('admin.catches.pending') }}" class="mt-2 inline-block text-sm text-sky-700 hover:underline">承認へ</a>
        </div>
    </div>
@endsection
