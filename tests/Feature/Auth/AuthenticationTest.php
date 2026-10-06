<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrorsIn('email');

        $this->assertGuest();
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge(): void
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_quick_login_is_hidden_by_default(): void
    {
        config(['app.demo_logins' => false]);

        $this->get(route('login'))
            ->assertOk()
            ->assertViewHas('demoLogins', [])
            ->assertDontSee('admin@example.com');
    }

    public function test_quick_login_lists_the_demo_accounts_when_enabled(): void
    {
        config(['app.demo_logins' => true, 'app.demo_password' => 'secret-demo']);

        $this->get(route('login'))
            ->assertOk()
            ->assertViewHas('demoLogins', array_values(DatabaseSeeder::logins()))
            ->assertSee('Quick login')
            ->assertSee('quality-manager@example.com')
            ->assertSee('secret-demo');
    }

    public function test_seeded_demo_login_can_authenticate(): void
    {
        config(['app.demo_password' => 'secret-demo']);
        $this->seed();
        $login = DatabaseSeeder::logins()['admin'];

        $this->post(route('login.store'), ['email' => $login['email'], 'password' => $login['password']])
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }
}
