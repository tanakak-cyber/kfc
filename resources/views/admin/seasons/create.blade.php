@extends('layouts.admin')

@section('title', 'シーズン作成')

@section('content')
    <h1 class="kfc-page-title">シーズン作成</h1>
    <form method="post" action="{{ route('admin.seasons.store') }}" enctype="multipart/form-data" class="kfc-card mt-8 space-y-5">
        @csrf
        @include('admin.seasons._form', ['season' => null])
        <button type="submit" class="kfc-btn-primary">保存</button>
    </form>
@endsection
