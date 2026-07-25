<?php

namespace App\Enums;

enum LoanType: string
{
    case Home = 'home';
    case Car = 'car';
    case Personal = 'personal';
    case Gold = 'gold';
    case Education = 'education';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Home Loan',
            self::Car => 'Car Loan',
            self::Personal => 'Personal Loan',
            self::Gold => 'Gold Loan',
            self::Education => 'Education Loan',
            self::Other => 'Other',
        };
    }

    /**
     * All cases as [value => label], for building <select> options.
     *
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
