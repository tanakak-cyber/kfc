@extends('layouts.admin')

@section('title', '試合作成')

@section('content')
    <h1 class="kfc-page-title">試合作成</h1>
    <form method="post" action="{{ route('admin.matches.store') }}" class="kfc-card mt-8 space-y-5">
        @csrf
        @include('admin.matches._form', ['gameMatch' => null, 'seasons' => $seasons, 'selectedSeasonId' => $selectedSeasonId])
        <button type="submit" class="kfc-btn-primary">保存</button>
    </form>
@endsection
