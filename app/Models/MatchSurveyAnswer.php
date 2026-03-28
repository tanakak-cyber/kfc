<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchSurveyAnswer extends Model
{
    protected $fillable = [
        'survey_id',
        'player_id',
        'selected_field_id',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(MatchSurvey::class, 'survey_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function selectedField(): BelongsTo
    {
        return $this->belongsTo(MatchSurveyField::class, 'selected_field_id');
    }

    public function dateAnswers(): HasMany
    {
        return $this->hasMany(MatchSurveyDateAnswer::class, 'answer_id');
    }
}
