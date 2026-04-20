{{-- 期待: $standings, $seasonCatchStats, $seasonParticipationStats (player_id キーの Collection) --}}
{{-- SP ではカード内いっぱいに広げつつ、表は最小幅で横スクロールして列を潰さない --}}
<div class="kfc-table-shell kfc-table-shell--card-bleed mt-6">
    <div class="kfc-table-scroll-x">
        <div class="kfc-table-scroll-inner">
        <table class="kfc-table-pinned kfc-table-season-standings w-full min-w-[64rem] text-left text-sm lg:min-w-full">
        <colgroup>
            <col style="width:3rem" />
            <col style="width:7rem" />
            <col />
            <col />
            <col />
            <col />
            <col />
            <col />
        </colgroup>
        <thead class="kfc-thead">
            <tr>
                <th class="kfc-pin-rank-th whitespace-nowrap px-2 py-2.5 sm:px-2.5 sm:py-3">順位</th>
                <th class="kfc-pin-name-th whitespace-nowrap px-2 py-2.5 sm:px-2.5 sm:py-3">プレイヤー</th>
                <th class="whitespace-nowrap px-3 py-2.5 sm:px-4 sm:py-3">合計ポイント</th>
                <th class="whitespace-nowrap px-3 py-2.5 sm:px-4 sm:py-3">参加試合数</th>
                <th class="whitespace-nowrap px-3 py-2.5 sm:px-4 sm:py-3">坊主回数</th>
                <th class="whitespace-nowrap px-3 py-2.5 sm:px-4 sm:py-3">シーズン通算釣果数</th>
                <th class="whitespace-nowrap px-3 py-2.5 sm:px-4 sm:py-3">最長（cm）</th>
                <th class="whitespace-nowrap px-3 py-2.5 sm:px-4 sm:py-3">最大重量（g）</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($standings as $row)
                @php
                    $c = $seasonCatchStats->get($row->player_id);
                    $p = $seasonParticipationStats->get($row->player_id);
                    $maxLen = data_get($c, 'max_length_cm');
                    $maxWt = data_get($c, 'max_weight_g');
                    $matchesPlayed = (int) data_get($p, 'matches_played', 0);
                    $catchCount = (int) data_get($c, 'catch_count', 0);
                    $isSeasonBozu = $matchesPlayed >= 1 && $catchCount === 0;
                @endphp
                <tr class="kfc-trow">
                    <td class="kfc-pin-rank-td whitespace-nowrap px-2 py-2.5 font-semibold text-zinc-900 sm:px-2.5 sm:py-3">{{ $row->display_rank }}</td>
                    <td class="kfc-pin-name-td px-2 py-2.5 sm:px-2.5 sm:py-3">
                        <span class="inline-flex max-w-full flex-wrap items-center gap-1">
                            <a href="{{ route('players.show', $row->player) }}" class="kfc-link min-w-0">{{ $row->player->displayLabel() }}</a>
                            @if ($isSeasonBozu)
                                <img
                                    src="{{ asset('images/kfc-bozu-icon.png') }}"
                                    alt="坊主"
                                    width="22"
                                    height="22"
                                    class="h-[1.35rem] w-[1.35rem] shrink-0 object-contain align-[-0.2em]"
                                    loading="lazy"
                                    decoding="async"
                                />
                            @endif
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-2.5 font-medium tabular-nums text-zinc-800 sm:px-4 sm:py-3">{{ $row->total_points }}</td>
                    <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-zinc-800 sm:px-4 sm:py-3">{{ $matchesPlayed }}</td>
                    <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-zinc-800 sm:px-4 sm:py-3">{{ data_get($p, 'blank_matches', 0) }}</td>
                    <td @class([
                        'whitespace-nowrap px-3 py-2.5 tabular-nums sm:px-4 sm:py-3',
                        'font-semibold text-red-600' => $isSeasonBozu,
                        'text-zinc-800' => ! $isSeasonBozu,
                    ])>{{ $catchCount }}</td>
                    <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-zinc-800 sm:px-4 sm:py-3">{{ $maxLen !== null && $maxLen !== '' ? $maxLen : '—' }}</td>
                    <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-zinc-800 sm:px-4 sm:py-3">{{ $maxWt !== null && $maxWt !== '' ? $maxWt.' g' : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
        </div>
    </div>
</div>
