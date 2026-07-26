<?php

namespace App\Enums;

enum SavingType: string
{
    case Bank = 'bank';
    case Gold = 'gold';
    case Cash = 'cash';
    case FixedDeposit = 'fixed_deposit';
    case Mutual = 'mutual_fund';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Bank',
            self::Gold => 'Gold',
            self::Cash => 'Cash',
            self::FixedDeposit => 'Fixed Deposit',
            self::Mutual => 'Mutual Fund',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static function (array $carry, self $type): array {
                $carry[$type->value] = $type->label();

                return $carry;
            },
            []
        );
    }
}
