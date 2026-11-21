<?php

namespace App\Modules\Persons\Enums;

/**
 * What kind of contact a person is. Drives which fields matter (a doctor has
 * a specialization, a medical store does not) and how lists are filtered.
 */
enum PersonType: string
{
    case Doctor = 'doctor';
    case Hospital = 'hospital';
    case Medical = 'medical';

    public function label(): string
    {
        return match ($this) {
            self::Doctor => 'Doctor',
            self::Hospital => 'Hospital',
            self::Medical => 'Medical Store',
        };
    }

    /** Only a doctor carries a specialization. */
    public function hasSpecialization(): bool
    {
        return $this === self::Doctor;
    }

    /** Cuba badge class, so lists colour-code type consistently. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Doctor => 'badge-light-primary',
            self::Hospital => 'badge-light-secondary',
            self::Medical => 'badge-light-success',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
