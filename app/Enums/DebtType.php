<?php

namespace App\Enums;

enum DebtType: string
{
    // Money the family borrowed (we owe it back).
    case Borrowed = 'borrowed';
    // Money the family lent out (owed back to us).
    case Lent = 'lent';

    public function label(): string
    {
        return match ($this) {
            self::Borrowed => 'Borrowed',
            self::Lent => 'Lent',
        };
    }

    /**
     * A short phrase describing the counterparty relationship for the given type.
     */
    public function personLabel(): string
    {
        return match ($this) {
            self::Borrowed => 'Borrowed from',
            self::Lent => 'Given to',
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
