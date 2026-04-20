@if (filled($gameMatch->survey_rsvp_snapshot))
    @php
        $snap = $gameMatch->survey_rsvp_snapshot;
        $intended = $snap['intended_player_ids'] ?? [];
        $dateYes = $snap['date_yes_player_ids'] ?? [];
        $dfYes = $snap['date_and_field_yes_player_ids'] ?? [];
        $labelDate = $snap['label_date'] ?? '';
        $labelField = $snap['label_field'] ?? '';

        $notIncludedDateYes = array_values(array_diff($dateYes, $intended));
        $notIncludedDf = array_values(array_diff($dfYes, $intended));
        $addedWithoutDateYes = array_values(array_diff($intended, $dateYes));

        $_cmpA = $notIncludedDf;
        $_cmpB = $notIncludedDateYes;
        sort($_cmpA);
        sort($_cmpB);
        $showDfRefBlock = $_cmpA !== $_cmpB;

        $allIds = array_values(array_unique(array_merge($intended, $notIncludedDateYes, $notIncludedDf, $addedWithoutDateYes)));
        $byId = \App\Models\Player::query()->whereIn('id', $allIds)->orderBy('name')->get()->keyBy('id');
    @endphp

    <section class="kfc-card mt-6 border-emerald-200/60 bg-emerald-50/25 ring-emerald-500/10">
        <h2 class="kfc-section-title">アンケートからの参加者リスト（確定時）</h2>
        <p class="mt-2 text-sm text-zinc-700">
            この試合は出欠アンケートから作成されています。確定時に選んだ<strong>候補日 {{ $labelDate }}</strong>・<strong>フィールド「{{ $labelField }}」</strong>に基づく一覧です。
        </p>
        @if ($gameMatch->source_match_survey_id)
            <p class="mt-2 text-sm">
                <a href="{{ route('admin.match-surveys.show', $gameMatch->source_match_survey_id) }}" class="kfc-link">紐づくアンケートを開く</a>
            </p>
        @endif

        <div class="mt-6 space-y-6 text-sm">
            <div>
                <h3 class="font-semibold text-zinc-900">試合に含めた選手</h3>
                @if ($intended === [])
                    <p class="mt-2 kfc-muted">なし</p>
                @else
                    <ul class="mt-2 list-inside list-disc space-y-1 text-zinc-800">
                        @foreach ($intended as $pid)
                            @if (isset($byId[$pid]))
                                <li>{{ $byId[$pid]->displayLabel() }}</li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <h3 class="font-semibold text-zinc-900">候補日に「出席（○）」と答えたが、試合参加者に含めなかった選手</h3>
                <p class="mt-1 text-xs text-zinc-500">確定画面でチェックを外した人、別フィールド希望で日程だけ○の人などがここに含まれることがあります。</p>
                @if ($notIncludedDateYes === [])
                    <p class="mt-2 kfc-muted">なし</p>
                @else
                    <ul class="mt-2 list-inside list-disc space-y-1 text-zinc-800">
                        @foreach ($notIncludedDateYes as $pid)
                            @if (isset($byId[$pid]))
                                <li>{{ $byId[$pid]->displayLabel() }}</li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($showDfRefBlock)
                <div>
                    <h3 class="font-semibold text-zinc-900">（参考）このフィールドを希望し、候補日に○だったが試合に含めなかった選手</h3>
                    @if ($notIncludedDf === [])
                        <p class="mt-2 kfc-muted">なし</p>
                    @else
                        <ul class="mt-2 list-inside list-disc space-y-1 text-zinc-800">
                            @foreach ($notIncludedDf as $pid)
                                @if (isset($byId[$pid]))
                                    <li>{{ $byId[$pid]->displayLabel() }}</li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if ($addedWithoutDateYes !== [])
                <div>
                    <h3 class="font-semibold text-zinc-900">候補日の○リストにいなかったが、試合に含めた選手</h3>
                    <p class="mt-1 text-xs text-zinc-500">確定画面で手動追加した場合など。</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-zinc-800">
                        @foreach ($addedWithoutDateYes as $pid)
                            @if (isset($byId[$pid]))
                                <li>{{ $byId[$pid]->displayLabel() }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>
@endif
