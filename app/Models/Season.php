<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    protected $fillable = [
        'name',
        'starts_on',
        'ends_on',
        'description',
        'image_path',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'season_id');
    }

    public function seasonPlayerPoints(): HasMany
    {
        return $this->hasMany(SeasonPlayerPoint::class, 'season_id');
    }
}
