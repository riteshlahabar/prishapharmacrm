<?php

namespace App\Modules\Access\Support;

use App\Modules\Users\Enums\UserRole;

/**
 * The whole permission vocabulary of the panel, in one place. Seeders, the
 * grant UI and the sidebar all read from here, so adding a capability means
 * editing this file and re-running the Access seeders — nothing else.
 *
 * Names are `<group>.<ability>`, e.g. `persons.create`.
 */
final class PermissionCatalog
{
    /**
     * Group key => label + abilities. Group keys match the module they guard.
     *
     * @return array<string, array{label: string, abilities: array<int, string>}>
     */
    public static function groups(): array
    {
        return [
            'dashboard' => ['label' => 'Dashboard', 'abilities' => ['view']],
            'regions' => ['label' => 'Regions', 'abilities' => ['view', 'create', 'update', 'delete']],
            'persons' => ['label' => 'Clients', 'abilities' => ['view', 'create', 'update', 'delete']],
            'client_types' => ['label' => 'Client Types', 'abilities' => ['view', 'create', 'update', 'delete']],
            'products' => ['label' => 'Products', 'abilities' => ['view', 'create', 'update', 'delete']],
            'mrs' => ['label' => 'Medical Reps', 'abilities' => ['view', 'create', 'update', 'delete']],
            'telecallers' => ['label' => 'Telecallers', 'abilities' => ['view', 'create', 'update', 'delete']],
            'attendance' => ['label' => 'Attendance', 'abilities' => ['view']],
            'visits' => ['label' => 'Visits', 'abilities' => ['view', 'create', 'update', 'delete']],
            'calls' => ['label' => 'Calls', 'abilities' => ['view', 'create', 'update', 'delete']],
            'messages' => ['label' => 'Messages', 'abilities' => ['view', 'create', 'delete']],
            'reports' => ['label' => 'Reports', 'abilities' => ['view', 'export']],
            'access' => ['label' => 'Access control', 'abilities' => ['manage']],
        ];
    }

    /**
     * Every permission name, flat.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        $names = [];

        foreach (self::groups() as $group => $meta) {
            foreach ($meta['abilities'] as $ability) {
                $names[] = "{$group}.{$ability}";
            }
        }

        return $names;
    }

    /**
     * Permissions an admin may hand to a telecaller. `access.manage` stays with
     * the admin role: a telecaller must not be able to widen its own rights.
     *
     * @return array<string, array{label: string, abilities: array<int, string>}>
     */
    public static function grantable(): array
    {
        $groups = self::groups();
        unset($groups['access'], $groups['mrs'], $groups['telecallers'], $groups['client_types']);

        return $groups;
    }

    /**
     * What a freshly seeded role starts with. `admin` gets everything; `mr`
     * never touches the panel, so it gets nothing.
     *
     * @return array<int, string>
     */
    public static function defaultsFor(UserRole $role): array
    {
        return match ($role) {
            UserRole::Admin => self::all(),
            UserRole::Telecaller => [
                'dashboard.view',
                'persons.view',
                'calls.view', 'calls.create', 'calls.update',
                'messages.view', 'messages.create',
                // Their own figures, clamped to the regions they cover.
                // `reports.export` is not a default: downloading the whole
                // contact book is a decision an admin makes per person.
                'reports.view',
            ],
            UserRole::Mr => [],
        };
    }

    public static function label(string $permission): string
    {
        [$group, $ability] = array_pad(explode('.', $permission, 2), 2, '');

        return (self::groups()[$group]['label'] ?? ucfirst($group)).' — '.ucfirst($ability);
    }
}
