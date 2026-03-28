<?php

namespace App\Models;

use App\Enums\CatchApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FishCatch extends Model
{
    protected $table = 'catches';

    protected $fillable = [
        'match_id',
        'team_id',
        'player_id',
        'length_cm',
        'weight_kg',
        'approval_status',
    ];

    protected function casts(): array
    {
        return [
            'length_cm' => 'decimal:2',
            'weight_kg' => 'decimal:3',
            'approval_status' => CatchApprovalStatus::class,
        ];
    }

    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CatchImage::class, 'catch_id')->orderBy('sort_order');
    }
}
