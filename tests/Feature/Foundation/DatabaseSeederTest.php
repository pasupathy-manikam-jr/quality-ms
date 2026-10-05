<?php

namespace Tests\Feature\Foundation;

use App\Models\IsoClause;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_twice_creates_one_account_per_role_and_the_iso_clauses(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(count(RolesSeeder::ROLES), User::query()->count());
        $this->assertTrue(User::query()->where('email', 'admin@example.com')->firstOrFail()->can('delete-users'));
        $this->assertFalse(User::query()->where('email', 'viewer@example.com')->firstOrFail()->can('manage-users'));
        $this->assertSame('Control of nonconforming outputs', IsoClause::query()->where('number', '8.7')->value('title'));
    }
}
