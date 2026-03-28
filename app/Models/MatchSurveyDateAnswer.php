<?php

namespace App\Models;

use App\Enums\SurveyDateAnswerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchSurveyDateAnswer extends Model
{
    protected $fillable = [
        'answer_id',
        'date_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => SurveyDateAnswerStatus::class,
        ];
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(MatchSurveyAnswer::class, 'answer_id');
    }

    public function surveyDate(): BelongsTo
    {
        return $this->belongsTo(MatchSurveyDate::class, 'date_id');
    }
}
