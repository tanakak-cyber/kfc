<?php

namespace App\Enums;

enum SurveyDateAnswerStatus: string
{
    case Yes = 'yes';
    case No = 'no';

    public function label(): string
    {
        return match ($this) {
            self::Yes => '○',
            self::No => '×',
        };
    }
}
