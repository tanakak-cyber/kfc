<?php

namespace App\Models;

use App\Enums\MatchPhase;
use App\Enums\MatchType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * DB に保存しない表示用ステータス（現在時刻・開始・確定フラグから算出）。
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

    public function isTeamMatch(): bool
    {
        return $this->match_type === MatchType::Team;
    }

    public function isIndividualMatch(): bool
    {
        return $this->match_type === MatchType::Individual;
    }
}
