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
     * One demo account per role: <role>@example.com, password from config('app.demo_password').
     *
     * @return array<string, array{name: string, email: string, password: string}>
     */
    public static function logins(): array
    {
        $password = (string) config('app.demo_password');

        return collect(RolesSeeder::ROLES)
            ->map(fn ($permissions, string $role) => ['name' => Str::headline($role), 'email' => "{$role}@example.com", 'password' => $password])
            ->all();
    }

    /**
     * Seed the application's database. Safe to run more than once. Model events stay on,
     * so seeded records get their numbers, creators and audit trail like any other.
     */
    public function run(): void
    {
        $this->call([RolesSeeder::class, IsoClauseSeeder::class]);

        $password = Hash::make(config('app.demo_password'));

        foreach (self::logins() as $role => $login) {
            User::query()->firstOrCreate(
                ['email' => $login['email']],
                ['name' => $login['name'], 'password' => $password, 'email_verified_at' => now()],
            )->syncRoles([$role]);
        }

        $this->call(self::MODULES);
    }
}
