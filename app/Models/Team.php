<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'match_id',
        'name',
        'entry_token',
    ];

    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'team_members')
            ->withTimestamps();
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'team_id');
    }

    public function catches(): HasMany
    {
        return $this->hasMany(FishCatch::class, 'team_id');
    }

    public function matchResults(): HasMany
    {
        return $this->hasMany(MatchResult::class, 'team_id');
    }
}
