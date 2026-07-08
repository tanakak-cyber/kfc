<?php

namespace Tests\Unit;

use App\Models\SeasonPlayerPoint;
use App\Support\SeasonPlayerStandings;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class SeasonPlayerStandingsTest extends TestCase
{
    public function test_points_take_highest_priority(): void
    {
        // ポイントが多い方が、最大重量・釣果数に関わらず先。
        $this->assertLessThan(0, SeasonPlayerStandings::compareStanding(
            10, 0.0, 0, 1,
            5, 9999.0, 9999, 2,
        ));
    }

    public function test_max_weight_is_second_priority(): void
    {
        // 同ポイントなら最大重量が多い方が先（釣果数に関わらず）。
        $this->assertLessThan(0, SeasonPlayerStandings::compareStanding(
            5, 100.0, 0, 1,
            5, 50.0, 9999, 2,
        ));
    }

    public function test_catch_count_is_third_priority(): void
    {
        // 同ポイント・同最大重量なら釣果数が多い方が先。
        $this->assertLessThan(0, SeasonPlayerStandings::compareStanding(
            5, 100.0, 10, 1,
            5, 100.0, 5, 2,
        ));
    }

    public function test_id_is_final_tiebreak(): void
    {
        // 全て同点なら id 昇順で決定的にする。
        $this->assertLessThan(0, SeasonPlayerStandings::compareStanding(
            5, 100.0, 10, 1,
            5, 100.0, 10, 2,
        ));
        $this->assertSame(0, SeasonPlayerStandings::compareStanding(
            5, 100.0, 10, 7,
            5, 100.0, 10, 7,
        ));
    }

    public function test_max_weight_value_handles_null_and_blank(): void
    {
        $this->assertSame(0.0, SeasonPlayerStandings::maxWeightValue(null));
        $this->assertSame(0.0, SeasonPlayerStandings::maxWeightValue(['catch_count' => 0, 'max_length_cm' => null, 'max_weight_g' => null]));
        $this->assertSame(0.0, SeasonPlayerStandings::maxWeightValue(['catch_count' => 0, 'max_length_cm' => null, 'max_weight_g' => '']));
        $this->assertSame(1234.5, SeasonPlayerStandings::maxWeightValue(['catch_count' => 1, 'max_length_cm' => null, 'max_weight_g' => '1234.5']));
    }

    public function test_order_by_points_max_weight_catch_count(): void
    {
        $standings = new Collection([
            SeasonPlayerPoint::make(['season_id' => 1, 'player_id' => 1, 'total_points' => 6]),
            SeasonPlayerPoint::make(['season_id' => 1, 'player_id' => 2, 'total_points' => 6]),
            SeasonPlayerPoint::make(['season_id' => 1, 'player_id' => 3, 'total_points' => 10]),
            SeasonPlayerPoint::make(['season_id' => 1, 'player_id' => 4, 'total_points' => 6]),
        ]);

        // player 1: 最大重量 大 / player 2: 最大重量 小・釣果多 / player 4: 中
        $catchStats = new Collection([
            1 => ['catch_count' => 1, 'max_length_cm' => null, 'max_weight_g' => '500'],
            2 => ['catch_count' => 20, 'max_length_cm' => null, 'max_weight_g' => '100'],
            4 => ['catch_count' => 1, 'max_length_cm' => null, 'max_weight_g' => '300'],
        ]);

        $ordered = SeasonPlayerStandings::orderByPointsMaxWeightCatchCount($standings, $catchStats)
            ->map(fn (SeasonPlayerPoint $p) => (int) $p->player_id)
            ->all();

        // 3(10pt) → 1(6pt,重500) → 4(6pt,重300) → 2(6pt,重100)
        $this->assertSame([3, 1, 4, 2], $ordered);
    }

    public function test_catch_count_breaks_tie_when_points_and_weight_equal(): void
    {
        $standings = new Collection([
            SeasonPlayerPoint::make(['season_id' => 1, 'player_id' => 1, 'total_points' => 6]),
            SeasonPlayerPoint::make(['season_id' => 1, 'player_id' => 2, 'total_points' => 6]),
        ]);
        $catchStats = new Collection([
            1 => ['catch_count' => 3, 'max_length_cm' => null, 'max_weight_g' => '100'],
            2 => ['catch_count' => 9, 'max_length_cm' => null, 'max_weight_g' => '100'],
        ]);

        $ordered = SeasonPlayerStandings::orderByPointsMaxWeightCatchCount($standings, $catchStats)
            ->map(fn (SeasonPlayerPoint $p) => (int) $p->player_id)
            ->all();

        $this->assertSame([2, 1], $ordered);
    }
}
