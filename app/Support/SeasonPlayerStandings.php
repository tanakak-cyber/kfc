<?php

namespace App\Support;

use Illuminate\Support\Collection;

final class SeasonPlayerStandings
{
    /**
     * @param  Collection<int, \App\Models\SeasonPlayerPoint>  $standings
     * @return Collection<int, \App\Models\SeasonPlayerPoint>
     */
    public static function attachDisplayRanks(Collection $standings): Collection
    {
        $list = $standings->values();
        $displayRank = 1;
        foreach ($list as $i => $row) {
            if ($i > 0 && $row->total_points < $list[$i - 1]->total_points) {
                $displayRank = $i + 1;
            }
            $row->display_rank = $displayRank;
        }

        return $list;
    }
}
