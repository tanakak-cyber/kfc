<?php

namespace App\Models;

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

    protected $fillable = [
        'season_id',
        'match_type',
        'title',
        'start_datetime',
        'end_datetime',
        'field',
        'launch_shop',
        'rules',
        'is_finalized',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'is_finalized' => 'boolean',
            'match_type' => MatchType::class,
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
}
