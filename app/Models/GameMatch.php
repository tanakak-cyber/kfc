<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'season_id',
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
}
