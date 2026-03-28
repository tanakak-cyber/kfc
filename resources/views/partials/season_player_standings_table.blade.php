{{-- 期待: $standings, $seasonCatchStats (player_id キーの Collection) --}}
<div class="kfc-table-shell mt-6 overflow-x-auto">
    <table class="min-w-full text-left text-sm">
        <thead class="kfc-thead">
            <tr>
                <th class="px-4 py-3">順位</th>
                <th class="px-4 py-3">プレイヤー</th>
                <th class="px-4 py-3">合計ポイント</th>
                <th class="px-4 py-3">シーズン通算釣果数</th>
                <th class="px-4 py-3">最長（cm）</th>
                <th class="px-4 py-3">最大重量（kg）</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($standings as $row)
                @php
                    $c = $seasonCatchStats->get($row->player_id);
                    $maxLen = data_get($c, 'max_length_cm');
                    $maxWt = data_get($c, 'max_weight_kg');
                @endphp
                <tr class="kfc-trow">
                    <td class="px-4 py-3 font-semibold text-zinc-900">{{ $row->display_rank }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('players.show', $row->player) }}" class="kfc-link">{{ $row->player->displayLabel() }}</a>
                    </td>
                    <td class="px-4 py-3 font-medium tabular-nums text-zinc-800">{{ $row->total_points }}</td>
                    <td class="px-4 py-3 tabular-nums text-zinc-800">{{ data_get($c, 'catch_count', 0) }}</td>
                    <td class="px-4 py-3 tabular-nums text-zinc-800">{{ $maxLen !== null && $maxLen !== '' ? $maxLen : '—' }}</td>
                    <td class="px-4 py-3 tabular-nums text-zinc-800">{{ $maxWt !== null && $maxWt !== '' ? $maxWt : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
