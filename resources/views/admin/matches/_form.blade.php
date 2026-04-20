@php
    $mt = old('match_type', isset($gameMatch) ? $gameMatch->match_type->value : 'team');
@endphp
<div>
    <span class="kfc-label">試合形式</span>
    @if (isset($gameMatch) && $gameMatch->is_finalized)
        <p class="mt-2 text-sm font-medium text-zinc-800">{{ $gameMatch->match_type->label() }}</p>
        <p class="mt-1 text-xs text-zinc-500">確定済みのため形式は変更できません。</p>
    @else
        <div class="mt-3 flex flex-wrap gap-4 text-sm">
            <label class="inline-flex cursor-pointer items-center gap-2">
                <input type="radio" name="match_type" value="team" class="text-emerald-600 focus:ring-emerald-500/40" @checked($mt === 'team') required>
                <span>チーム戦</span>
            </label>
            <label class="inline-flex cursor-pointer items-center gap-2">
                <input type="radio" name="match_type" value="individual" class="text-emerald-600 focus:ring-emerald-500/40" @checked($mt === 'individual')>
                <span>個人戦</span>
            </label>
        </div>
        <p class="mt-2 text-xs text-zinc-500">
            @if (isset($gameMatch))
                チーム戦はチーム単位、個人戦は「参加者」に登録した選手のみが順位対象です。変更したらチーム／参加者・釣果の整合を確認してください。
            @else
                個人戦は参加者ごとに投稿URLを発行します。試合が確定するまでは、試合編集で形式を変更できます。
            @endif
        </p>
    @endif
</div>
<div>
    <label class="kfc-label">シーズン</label>
    <select name="season_id" class="kfc-select mt-2" required>
        @foreach ($seasons as $s)
            <option value="{{ $s->id }}" @selected(old('season_id', $gameMatch?->season_id ?? $selectedSeasonId) == $s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="kfc-label">試合名</label>
    <input type="text" name="title" value="{{ old('title', $gameMatch?->title ?? '') }}" required class="kfc-input mt-2">
</div>
<div>
    <label class="kfc-label">開始日時</label>
    <input type="datetime-local" name="start_datetime" value="{{ old('start_datetime', optional($gameMatch)->start_datetime?->format('Y-m-d\TH:i')) }}" required class="kfc-input mt-2">
    <p class="mt-1 text-xs text-zinc-500">この時刻より前は釣果投稿を受け付けません（Asia/Tokyo）。</p>
</div>
<div>
    <label class="kfc-label">終了日時（任意）</label>
    <input type="datetime-local" name="end_datetime" value="{{ old('end_datetime', optional($gameMatch)->end_datetime?->format('Y-m-d\TH:i')) }}" class="kfc-input mt-2">
    <p class="mt-1 text-xs text-zinc-500">未入力なら終了後も投稿可能。入力時はその時刻以降は投稿不可。</p>
</div>
<div>
    <label class="kfc-label">フィールド</label>
    <input type="text" name="field" value="{{ old('field', $gameMatch?->field ?? '') }}" required class="kfc-input mt-2">
</div>
<div>
    <label class="kfc-label">出艇店舗</label>
    <input type="text" name="launch_shop" value="{{ old('launch_shop', $gameMatch?->launch_shop ?? '') }}" class="kfc-input mt-2">
</div>
<div>
    <label class="kfc-label">ルール</label>
    <textarea name="rules" rows="4" class="kfc-input mt-2">{{ old('rules', $gameMatch?->rules ?? '') }}</textarea>
</div>
<div>
    <label class="kfc-label">順位の算出（基準）</label>
    <select name="catch_scoring_basis" class="kfc-select mt-2" required>
        @foreach (\App\Enums\CatchScoringBasis::cases() as $basis)
            <option
                value="{{ $basis->value }}"
                @selected(old('catch_scoring_basis', $gameMatch ? $gameMatch->resolvedCatchScoringBasis()->value : 'weight') === $basis->value)
            >{{ $basis->label() }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-zinc-500">各チーム／選手の釣果のうち、この基準で大きい順に並べ、下の「本数」だけを合計して順位を付けます。</p>
</div>
<div>
    <label class="kfc-label" for="catch_scoring_limit">順位に使う本数（リミット）</label>
    <input
        type="number"
        id="catch_scoring_limit"
        name="catch_scoring_limit"
        min="{{ \App\Models\GameMatch::CATCH_SCORING_LIMIT_MIN }}"
        max="{{ \App\Models\GameMatch::CATCH_SCORING_LIMIT_MAX }}"
        step="1"
        value="{{ old('catch_scoring_limit', $gameMatch ? $gameMatch->effectiveCatchScoringLimit() : 3) }}"
        required
        class="kfc-input mt-2 w-32 tabular-nums"
    >
    <p class="mt-1 text-xs text-zinc-500">{{ \App\Models\GameMatch::CATCH_SCORING_LIMIT_MIN }}〜{{ \App\Models\GameMatch::CATCH_SCORING_LIMIT_MAX }}本。大きい順にこの本数だけ合計します。</p>
</div>
