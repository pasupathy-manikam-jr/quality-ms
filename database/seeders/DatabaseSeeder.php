<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Module seeders in dependency order.
     *
     * @var list<class-string<Seeder>>
     */
    private const MODULES = [
        Modules\SupplierSeeder::class,
        Modules\MaterialSeeder::class,
        Modules\CertificateSeeder::class,
        Modules\GaugeSeeder::class,
        Modules\InspectionSeeder::class,
        Modules\NcrSeeder::class,
        Modules\DocumentSeeder::class,
        Modules\AuditSeeder::class,
    ];

    /**
     * Seed the application's database. Safe to run more than once. Model events stay on,
     * so seeded records get their numbers, creators and audit trail like any other.
     */
    public function run(): void
    {
        $this->call([RolesSeeder::class, IsoClauseSeeder::class]);

        // One demo account per role: <role>@example.com / Zx123456.
        $password = Hash::make('Zx123456');

        foreach (array_keys(RolesSeeder::ROLES) as $role) {
            User::query()->firstOrCreate(
                ['email' => "{$role}@example.com"],
                ['name' => Str::headline($role), 'password' => $password, 'email_verified_at' => now()],
            )->syncRoles([$role]);
        }

        $this->call(self::MODULES);
    }
}
