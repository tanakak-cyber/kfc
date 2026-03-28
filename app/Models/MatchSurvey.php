<?php

namespace App\Models;

use App\Enums\SurveyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchSurvey extends Model
{
    protected $fillable = [
        'season_id',
        'title',
        'description',
        'survey_token',
        'status',
        'created_match_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => SurveyStatus::class,
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    public function createdMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'created_match_id');
    }

    public function dates(): HasMany
    {
        return $this->hasMany(MatchSurveyDate::class, 'survey_id')->orderBy('on_date');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(MatchSurveyField::class, 'survey_id')->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(MatchSurveyAnswer::class, 'survey_id');
    }

    public function acceptsResponses(): bool
    {
        return $this->status === SurveyStatus::Open;
    }
}
