<?php

namespace App\Enums;

enum CatchScoringBasis: string
{
    case Weight = 'weight';
    case Length = 'length';

    public function label(): string
    {
        return match ($this) {
            self::Weight => '重さ',
            self::Length => '長さ',
        };
    }

    public function unitLabel(): string
    {
        return match ($this) {
            self::Weight => 'g',
            self::Length => 'cm',
        };
    }
}
