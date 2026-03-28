@extends('layouts.app')

@section('title', '釣果投稿')

@section('content')
    <h1 class="kfc-page-title">釣果投稿</h1>
    <p class="kfc-muted mt-2">{{ $gameMatch->title }} — <span class="font-medium text-zinc-700">{{ $team->name }}</span></p>

    <section class="kfc-card mt-8">
        <h2 class="kfc-section-title">自チーム TOP3（未承認含む）</h2>
        @if (empty($top3))
            <p class="kfc-muted mt-3">まだ投稿がありません。</p>
        @else
            <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-zinc-800">
                @foreach ($top3 as $row)
                    <li><span class="tabular-nums font-medium">{{ $row['weight_kg'] }}</span> kg / <span class="tabular-nums">{{ $row['length_cm'] }}</span> cm</li>
                @endforeach
            </ol>
        @endif
    </section>

    @if ($gameMatch->is_finalized)
        <p class="kfc-alert-warn mt-8" role="status">
            この試合は結果確定済みのため、新規投稿はできません。
        </p>
    @else
        <section class="kfc-card mt-8">
            <h2 class="kfc-section-title">新規投稿</h2>
            <form method="post" action="{{ route('entry.store', $team->entry_token) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label class="kfc-label">釣った人</label>
                    <select name="player_id" class="kfc-select mt-2" required>
                        @foreach ($team->players as $p)
                            <option value="{{ $p->id }}" @selected(old('player_id') == $p->id)>{{ $p->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="kfc-label">長さ（cm）</label>
                    <input type="number" step="0.01" name="length_cm" value="{{ old('length_cm') }}" class="kfc-input mt-2" required>
                </div>
                <div>
                    <label class="kfc-label">重さ（kg）</label>
                    <input type="number" step="0.001" name="weight_kg" value="{{ old('weight_kg') }}" class="kfc-input mt-2" required>
                </div>
                <div>
                    <label class="kfc-label">写真（複数可）</label>
                    <input type="file" name="photos[]" accept="image/*" multiple class="mt-2 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100" required>
                    @error('photos')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-zinc-500">1〜10枚まで。アップロード時にリサイズ・JPEG化されます（EXIF削除）。タップで拡大し、複数枚のときは左右の矢印・スワイプで切り替えられます。</p>
                </div>
                <button type="submit" class="kfc-btn-primary w-full sm:w-auto">送信</button>
            </form>
        </section>
    @endif
@endsection
