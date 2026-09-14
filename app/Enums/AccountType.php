<?php

namespace App\Enums;

/**
 * The three expense-management modes an account (workspace) can operate in.
 * Chosen at signup; stored on the families table. "Family" is the default so
 * every pre-existing account keeps its current behaviour unchanged.
 */
enum AccountType: string
{
    case Family = 'family';
    case Bachelor = 'bachelor';
    case Individual = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::Family => 'Family Expense',
            self::Bachelor => 'Bachelor Split',
            self::Individual => 'Individual Expense',
        };
    }

    public function tagline(): string
    {
        return match ($this) {
            self::Family => 'Manage expenses, income and savings for your family.',
            self::Bachelor => 'Share household costs with roommates and settle up each month.',
            self::Individual => 'Track your own personal income and expenses.',
        };
    }

    /**
     * The default workspace name for a new account of this type.
     */
    public function defaultWorkspaceName(string $userName): string
    {
        return match ($this) {
            self::Family => $userName."'s Family",
            self::Bachelor => $userName."'s Apartment",
            self::Individual => $userName."'s Expenses",
        };
    }

    /**
     * @return array<string, string> value => label
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
