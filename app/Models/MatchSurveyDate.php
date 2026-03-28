<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchSurveyDate extends Model
{
    protected $fillable = [
        'survey_id',
        'on_date',
    ];

    protected function casts(): array
    {
        return [
            'on_date' => 'date',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(MatchSurvey::class, 'survey_id');
    }

    public function dateAnswers(): HasMany
    {
        return $this->hasMany(MatchSurveyDateAnswer::class, 'date_id');
    }
}
