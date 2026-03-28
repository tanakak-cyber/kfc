<?php

namespace App\Enums;

enum MatchPhase: string
{
    case Scheduled = 'scheduled';
    case Ongoing = 'ongoing';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => '予定',
            self::Ongoing => '開催中',
            self::Finalized => '確定',
        };
    }
}
