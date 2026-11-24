<?php

namespace App\Modules\Persons\Exceptions;

use RuntimeException;

/**
 * Add Client was reached by an account that is neither an MR nor a
 * telecaller.
 *
 * The route's ability middleware already makes this unreachable, so this is
 * a guard against a future caller rather than a path a device can take: the
 * service refuses to write a client with nobody on either side rather than
 * letting an admin token create an unowned row through an app endpoint.
 */
class AppClientNotPermitted extends RuntimeException
{
    public static function forRole(?string $role): self
    {
        return new self(
            'Adding a client from an app needs an MR or telecaller account, got: '
            .($role ?? 'none').'.'
        );
    }
}
