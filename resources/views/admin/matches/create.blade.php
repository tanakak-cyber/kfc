@extends('layouts.admin')

@section('title', '試合作成')

@section('content')
    <h1 class="text-2xl font-bold">試合作成</h1>
    <form method="post" action="{{ route('admin.matches.store') }}" class="mt-6 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @include('admin.matches._form', ['gameMatch' => null, 'seasons' => $seasons, 'selectedSeasonId' => $selectedSeasonId])
        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">保存</button>
    </form>
@endsection
