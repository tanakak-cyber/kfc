@php
    /** @var \App\Models\GameMatch $gameMatch */
    /** @var string $entryUrl */
    $m = $gameMatch;
    $line1 = $m->start_datetime !== null
        ? $m->start_datetime->format('n').'月'.$m->start_datetime->format('j').'日の釣果投稿は以下のURLからお願いします。'
        : '本試合の釣果投稿は以下のURLからお願いします。';
    $matchPublicUrl = route('matches.show', $m, absolute: true);
@endphp
-----------------------------------
{{ $line1 }}
{{ $entryUrl }}
※投稿は試合時間内しかできません。

なお、本試合のルールについては以下のURLから確認してください。
{{ $matchPublicUrl }}
-----------------------------------
