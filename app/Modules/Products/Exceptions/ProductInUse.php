<?php

namespace App\Modules\Products\Exceptions;

use RuntimeException;

/**
 * Raised instead of deleting a product that a visit or a call already names:
 * the line stores what was discussed that day, and deleting the product
 * would erase part of that record.
 */
class ProductInUse extends RuntimeException
{
    public static function make(string $name, int $lines): self
    {
        return new self(
            "{$name} is on {$lines} visit or call record(s) and cannot be deleted. "
            .'Deactivate it instead — it then disappears from both apps while those records keep it.'
        );
    }
}
