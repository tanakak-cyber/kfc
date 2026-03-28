@extends('layouts.admin')

@section('title', 'シーズン編集')

@section('content')
    <h1 class="kfc-page-title">シーズン編集</h1>
    <form method="post" action="{{ route('admin.seasons.update', $season) }}" enctype="multipart/form-data" class="kfc-card mt-8 space-y-5">
        @csrf
        @method('put')
        @include('admin.seasons._form', ['season' => $season])
        <button type="submit" class="kfc-btn-primary">更新</button>
    </form>
@endsection
