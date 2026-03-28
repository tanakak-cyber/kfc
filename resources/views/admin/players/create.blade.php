@extends('layouts.admin')

@section('title', '選手登録')

@section('content')
    <h1 class="text-2xl font-bold">選手登録</h1>
    <form method="post" action="{{ route('admin.players.store') }}" enctype="multipart/form-data" class="mt-6 max-w-md space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label class="block text-sm font-medium">名前（本名・管理用）</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium">表示名</label>
            <input type="text" name="display_name" value="{{ old('display_name') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="未入力時は名前を表示">
        </div>
        <div>
            <label class="block text-sm font-medium">アイコン画像</label>
            <input type="file" name="icon" accept="image/*" class="mt-1 w-full text-sm">
        </div>
        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">保存</button>
    </form>
@endsection
