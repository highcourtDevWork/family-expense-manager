<?php

namespace App\Enums;

enum DebtStatus: string
{
    case Open = 'open';
    case Settled = 'settled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Settled => 'Settled',
        };
    }
}
