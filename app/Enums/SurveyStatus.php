<?php

namespace App\Enums;

enum SurveyStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::Open => '受付中',
            self::Closed => '受付終了',
            self::Finalized => '確定済み（試合作成済み）',
        };
    }
}
