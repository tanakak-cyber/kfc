@extends('layouts.admin')

@section('title', '選手登録')

@section('content')
    <h1 class="kfc-page-title">選手登録</h1>
    <form method="post" action="{{ route('admin.players.store') }}" enctype="multipart/form-data" class="kfc-card mt-8 max-w-lg space-y-5">
        @csrf
        <div>
            <label class="kfc-label">名前（本名・管理用）</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="kfc-input mt-2">
        </div>
        <div>
            <label class="kfc-label">表示名</label>
            <input type="text" name="display_name" value="{{ old('display_name') }}" class="kfc-input mt-2" placeholder="未入力時は名前を表示">
        </div>
        <div>
            <label class="kfc-label">アイコン画像</label>
            <input type="file" name="icon" accept="image/*" class="mt-2 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100">
        </div>
        <button type="submit" class="kfc-btn-primary">保存</button>
    </form>
@endsection
