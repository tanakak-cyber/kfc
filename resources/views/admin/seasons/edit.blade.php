@extends('layouts.admin')

@section('title', 'シーズン編集')

@section('content')
    <h1 class="text-2xl font-bold">シーズン編集</h1>
    <form method="post" action="{{ route('admin.seasons.update', $season) }}" enctype="multipart/form-data" class="mt-6 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('put')
        @include('admin.seasons._form', ['season' => $season])
        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">更新</button>
    </form>
@endsection
