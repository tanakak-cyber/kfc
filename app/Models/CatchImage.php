<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatchImage extends Model
{
    protected $fillable = [
        'catch_id',
        'path',
        'sort_order',
    ];

    public function fishCatch(): BelongsTo
    {
        return $this->belongsTo(FishCatch::class, 'catch_id');
    }
}
