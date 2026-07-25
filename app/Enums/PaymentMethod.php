<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Upi = 'upi';
    case BankTransfer = 'bank_transfer';
    case Other = 'other';

    /**
     * Human-friendly label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Upi => 'UPI',
            self::BankTransfer => 'Bank Transfer',
            self::Other => 'Other',
        };
    }

    /**
     * All cases as [value => label], handy for building <select> options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static function (array $carry, self $method): array {
                $carry[$method->value] = $method->label();

                return $carry;
            },
            []
        );
    }
}
