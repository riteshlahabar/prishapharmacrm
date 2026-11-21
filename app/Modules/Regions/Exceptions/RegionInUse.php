<?php

namespace App\Modules\Regions\Exceptions;

use RuntimeException;

/**
 * Deleting a region that MRs, telecallers or persons still point at would
 * orphan them, so the service refuses and the controller explains why.
 */
class RegionInUse extends RuntimeException
{
    public static function make(string $name, array $counts): self
    {
        $parts = [];

        foreach (['mrs' => 'medical rep', 'telecallers' => 'telecaller', 'persons' => 'person'] as $key => $noun) {
            if (($n = $counts[$key] ?? 0) > 0) {
                $parts[] = $n.' '.$noun.($n === 1 ? '' : 's');
            }
        }

        return new self(
            "{$name} is still assigned to ".implode(', ', $parts).
            '. Reassign them first, or deactivate the region instead of deleting it.'
        );
    }
}
