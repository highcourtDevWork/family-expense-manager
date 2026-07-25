<?php

namespace App\Enums;

enum FamilyRole: string
{
    case Admin = 'admin';
    case Member = 'member';

    /**
     * Human-friendly label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Family Admin',
            self::Member => 'Family Member',
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
            static function (array $carry, self $role): array {
                $carry[$role->value] = $role->label();

                return $carry;
            },
            []
        );
    }
}
