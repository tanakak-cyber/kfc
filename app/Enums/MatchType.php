<?php

namespace App\Enums;

enum MatchType: string
{
    case Team = 'team';
    case Individual = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::Team => 'チーム戦',
            self::Individual => '個人戦',
        };
    }
}
