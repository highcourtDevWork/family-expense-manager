<?php

namespace App\Enums;

enum Relationship: string
{
    // NOTE: "self" cannot be used as a case name — it is a reserved word in
    // PHP — so the family creator is modelled as "Head" of the family.
    case Head = 'head';
    case Spouse = 'spouse';
    case Father = 'father';
    case Mother = 'mother';
    case Son = 'son';
    case Daughter = 'daughter';
    case Brother = 'brother';
    case Sister = 'sister';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Head => 'Head of Family',
            self::Spouse => 'Spouse',
            self::Father => 'Father',
            self::Mother => 'Mother',
            self::Son => 'Son',
            self::Daughter => 'Daughter',
            self::Brother => 'Brother',
            self::Sister => 'Sister',
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
            static function (array $carry, self $relationship): array {
                $carry[$relationship->value] = $relationship->label();

                return $carry;
            },
            []
        );
    }
}
