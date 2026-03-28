<?php

namespace App\Models;

use App\Enums\MatchStatus;
use App\Enums\MatchType;
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
        'held_at',
        'field',
        'launch_shop',
        'rules',
        'status',
        'is_finalized',
    ];

    protected function casts(): array
    {
        return [
            'held_at' => 'datetime',
            'is_finalized' => 'boolean',
            'status' => MatchStatus::class,
            'match_type' => MatchType::class,
        ];
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
