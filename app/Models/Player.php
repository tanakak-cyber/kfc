<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'icon',
    ];

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withTimestamps();
    }

    public function catches(): HasMany
    {
        return $this->hasMany(FishCatch::class, 'player_id');
    }

    public function displayLabel(): string
    {
        return $this->display_name !== null && $this->display_name !== ''
            ? $this->display_name
            : $this->name;
    }
}
