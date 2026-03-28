<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchSurveyField extends Model
{
    protected $fillable = [
        'survey_id',
        'field_name',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(MatchSurvey::class, 'survey_id');
    }

    public function answersSelectingThis(): HasMany
    {
        return $this->hasMany(MatchSurveyAnswer::class, 'selected_field_id');
    }
}
