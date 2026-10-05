<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Permissions per module, named "<action>-<module>" (manage-users, create-users, ...).
     * Each module adds its own line here.
     *
     * @var array<string, list<string>>
     */
    public const PERMISSIONS = [
        'users' => ['manage', 'create', 'edit', 'delete'],
        'suppliers' => ['manage', 'create', 'edit', 'delete'],
        'materials' => ['manage', 'create', 'edit', 'delete'],
        'certificates' => ['manage', 'create', 'edit', 'delete', 'verify'],
        'gauges' => ['manage', 'create', 'edit', 'delete', 'calibrate'],
        'parts' => ['manage', 'create', 'edit', 'delete'],
        'inspection-plans' => ['manage', 'create', 'edit', 'approve'],
        'inspections' => ['manage', 'create', 'edit'],
        'ncrs' => ['manage', 'create', 'edit', 'approve'],
        'capas' => ['manage', 'create', 'edit', 'verify'],
        'documents' => ['manage', 'create', 'edit', 'approve'],
        'audits' => ['manage', 'create', 'edit'],
    ];

    /**
     * Built-in roles and their permissions; "*" grants every permission.
     *
     * @var array<string, list<string>>
     */
    public const ROLES = [
        'admin' => ['*'],
        'quality-manager' => [
            'manage-suppliers', 'create-suppliers', 'edit-suppliers', 'delete-suppliers',
            'manage-materials', 'create-materials', 'edit-materials', 'delete-materials',
            'manage-certificates', 'create-certificates', 'edit-certificates', 'delete-certificates', 'verify-certificates',
            'manage-gauges', 'create-gauges', 'edit-gauges', 'delete-gauges', 'calibrate-gauges',
            'manage-parts', 'create-parts', 'edit-parts', 'delete-parts',
            'manage-inspection-plans', 'create-inspection-plans', 'edit-inspection-plans', 'approve-inspection-plans',
            'manage-inspections', 'create-inspections', 'edit-inspections',
            'manage-ncrs', 'create-ncrs', 'edit-ncrs', 'approve-ncrs',
            'manage-capas', 'create-capas', 'edit-capas', 'verify-capas',
            'manage-documents', 'create-documents', 'edit-documents', 'approve-documents',
            'manage-audits', 'create-audits', 'edit-audits',
        ],
        'inspector' => ['manage-suppliers', 'manage-materials', 'manage-certificates', 'create-certificates', 'edit-certificates', 'manage-gauges',
            'manage-parts', 'manage-inspection-plans', 'manage-inspections', 'create-inspections', 'edit-inspections',
            'manage-ncrs', 'create-ncrs', 'edit-ncrs', 'manage-capas', 'edit-capas', 'manage-documents',
        ],
        'auditor' => ['manage-suppliers', 'manage-materials', 'manage-certificates', 'manage-gauges', 'manage-parts', 'manage-inspection-plans', 'manage-inspections', 'manage-ncrs', 'manage-capas', 'manage-documents', 'manage-audits', 'create-audits', 'edit-audits'],
        'viewer' => ['manage-suppliers', 'manage-materials', 'manage-certificates', 'manage-gauges', 'manage-parts', 'manage-inspection-plans', 'manage-inspections', 'manage-ncrs', 'manage-capas', 'manage-documents', 'manage-audits'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = collect(self::PERMISSIONS)
            ->flatMap(fn (array $actions, string $module) => array_map(fn (string $action) => "{$action}-{$module}", $actions))
            ->values();

        foreach ($all as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Model events may be off while seeding, so the cached (empty) permission list must be dropped by hand.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $name => $permissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($permissions === ['*'] ? $all : $permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
