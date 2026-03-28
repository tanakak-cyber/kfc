@extends('layouts.app')

@section('title', '釣果投稿')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">釣果投稿</h1>
    <p class="mt-1 text-sm text-slate-600">{{ $gameMatch->title }} — {{ $team->name }}</p>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">自チーム TOP3（未承認含む）</h2>
        @if (empty($top3))
            <p class="mt-2 text-sm text-slate-500">まだ投稿がありません。</p>
        @else
            <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm">
                @foreach ($top3 as $row)
                    <li>{{ $row['weight_kg'] }} kg / {{ $row['length_cm'] }} cm</li>
                @endforeach
            </ol>
        @endif
    </section>

    @if ($gameMatch->is_finalized)
        <p class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            この試合は結果確定済みのため、新規投稿はできません。
        </p>
    @else
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">新規投稿</h2>
            <form method="post" action="{{ route('entry.store', $team->entry_token) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700">釣った人</label>
                    <select name="player_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                        @foreach ($team->players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">長さ（cm）</label>
                    <input type="number" step="0.01" name="length_cm" value="{{ old('length_cm') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">重さ（kg）</label>
                    <input type="number" step="0.001" name="weight_kg" value="{{ old('weight_kg') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">写真</label>
                    <input type="file" name="photo" accept="image/*" class="mt-1 w-full text-sm" required>
                    <p class="mt-1 text-xs text-slate-500">アップロード時にリサイズ・JPEG化されます（EXIF削除）。</p>
                </div>
                <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 sm:w-auto">送信</button>
            </form>
        </section>
    @endif
@endsection
