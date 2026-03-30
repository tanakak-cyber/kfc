<?php

namespace App\Models;

use App\Enums\CatchScoringBasis;
use App\Enums\MatchPhase;
use App\Enums\MatchType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class GameMatch extends Model
{
    protected $table = 'matches';

    public const CATCH_SCORING_LIMIT_MIN = 1;

    public const CATCH_SCORING_LIMIT_MAX = 30;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'catch_scoring_basis' => 'weight',
        'catch_scoring_limit' => 3,
    ];

    protected $fillable = [
        'season_id',
        'match_type',
        'title',
        'start_datetime',
        'end_datetime',
        'field',
        'launch_shop',
        'rules',
        'catch_scoring_basis',
        'catch_scoring_limit',
        'is_finalized',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'is_finalized' => 'boolean',
            'match_type' => MatchType::class,
            'catch_scoring_basis' => CatchScoringBasis::class,
            'catch_scoring_limit' => 'integer',
        ];
    }

    /**
     * DB に保存しない表示用ステータス（現在時刻・開始・終了・確定から算出）。
     * 終了日時を設定している場合、終了日時の1時間後から「終了」と表示する。
     */
    protected function status(): Attribute
    {
        return Attribute::get(function (): MatchPhase {
            $now = now();
            $start = $this->start_datetime;

            if ($start !== null && $now->lt($start)) {
                return MatchPhase::Scheduled;
            }

            if ($this->is_finalized) {
                return MatchPhase::Finalized;
            }

            if ($this->end_datetime !== null && $now->gte($this->end_datetime->copy()->addHour())) {
                return MatchPhase::Ended;
            }

            return MatchPhase::Ongoing;
        });
    }

    public function isBeforeStartDatetime(): bool
    {
        return $this->start_datetime !== null && now()->lt($this->start_datetime);
    }

    public function isAtOrAfterEndDatetime(): bool
    {
        return $this->end_datetime !== null && now()->gte($this->end_datetime);
    }

    public function acceptsPublicCatchSubmissions(): bool
    {
        if ($this->is_finalized) {
            return false;
        }

        if ($this->isBeforeStartDatetime()) {
            return false;
        }

        if ($this->isAtOrAfterEndDatetime()) {
            return false;
        }

        return true;
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'match_id');
    }

    public function catches(): HasMany
    {
        return $this->hasMany(FishCatch::class, 'match_id');
    }

    public function matchResults(): HasMany
    {
        return $this->hasMany(MatchResult::class, 'match_id');
    }

    public function matchParticipants(): HasMany
    {
        return $this->hasMany(MatchParticipant::class, 'match_id');
    }

    public function matchPlayerBonusPoints(): HasMany
    {
        return $this->hasMany(MatchPlayerBonusPoint::class, 'match_id');
    }

    /**
     * この試合に紐づく選手のみ（チーム戦: 全チームのメンバー、個人戦: 参加者一覧）
     *
     * @return Collection<int, Player>
     */
    public function playersEligibleForBonus(): Collection
    {
        if ($this->isTeamMatch()) {
            return $this->teams()
                ->with('players')
                ->get()
                ->flatMap(fn (Team $team) => $team->players)
                ->unique('id')
                ->values();
        }

        return $this->matchParticipants()
            ->with('player')
            ->get()
            ->pluck('player')
            ->filter()
            ->unique('id')
            ->values();
    }

    public function isTeamMatch(): bool
    {
        return $this->match_type === MatchType::Team;
    }

    public function isIndividualMatch(): bool
    {
        return $this->match_type === MatchType::Individual;
    }

    /**
     * 未マイグレーションや null でも落ちないよう、集計・表示用の基準を返す。
     */
    public function resolvedCatchScoringBasis(): CatchScoringBasis
    {
        $b = $this->getAttribute('catch_scoring_basis');

        if ($b instanceof CatchScoringBasis) {
            return $b;
        }

        if (is_string($b) && $b !== '') {
            return CatchScoringBasis::tryFrom($b) ?? CatchScoringBasis::Weight;
        }

        return CatchScoringBasis::Weight;
    }

    public function catchScoringUnitLabel(): string
    {
        return $this->resolvedCatchScoringBasis()->unitLabel();
    }

    /**
     * 順位表ブロックの見出し（公開ページ用）。
     */
    public function catchScoringStandingsHeading(): string
    {
        $basis = $this->resolvedCatchScoringBasis()->label();
        $n = $this->effectiveCatchScoringLimit();

        return '順位表（承認済み釣果・'.$basis.'・上位'.$n.'本合計）';
    }

    public function catchScoringTotalColumnLabel(): string
    {
        return '合計（'.$this->catchScoringUnitLabel().'）';
    }

    public function catchScoringBigColumnLabel(): string
    {
        return $this->resolvedCatchScoringBasis() === CatchScoringBasis::Length
            ? '最長（cm）'
            : 'ビッグ（g）';
    }

    public function effectiveCatchScoringLimit(): int
    {
        $n = (int) $this->catch_scoring_limit;

        if ($n < self::CATCH_SCORING_LIMIT_MIN || $n > self::CATCH_SCORING_LIMIT_MAX) {
            return 3;
        }

        return $n;
    }
}
