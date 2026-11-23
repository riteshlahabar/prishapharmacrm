<?php

namespace App\Modules\ClientTypes\Exceptions;

use RuntimeException;

/**
 * Raised instead of deleting a type that clients still carry: the slug lives
 * in `persons.type`, and removing the row would leave those clients with a
 * type nothing can label.
 */
class ClientTypeInUse extends RuntimeException
{
    public static function make(string $label, int $clients): self
    {
        return new self(
            "{$label} is still used by {$clients} client(s). Deactivate it instead — "
            .'it then disappears from the client form while existing clients keep their type.'
        );
    }

    public static function builtIn(string $label): self
    {
        return new self("{$label} is a built-in type and cannot be deleted. Deactivate it instead.");
    }
}
