<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPlayerBonusPoint extends Model
{
    protected $table = 'match_player_bonus_points';

    protected $fillable = [
        'match_id',
        'player_id',
        'points',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }

    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id');
    }
}
