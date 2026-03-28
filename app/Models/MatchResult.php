<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchResult extends Model
{
    protected $fillable = [
        'match_id',
        'team_id',
        'player_id',
        'rank',
        'total_weight',
        'big_fish_weight',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'total_weight' => 'decimal:3',
            'big_fish_weight' => 'decimal:3',
        ];
    }

    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
