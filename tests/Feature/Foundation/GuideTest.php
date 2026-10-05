<?php

use App\Models\User;

it('shows the user guide with its table of contents to any signed-in user', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('guide'))
        ->assertOk()
        ->assertSee('guide-toc', escape: false)
        ->assertSee('Recording a calibration')
        ->assertSee('href="#electronic-signatures"', escape: false);
});

it('falls back to English for a language without a guide', function () {
    config(['app.locales' => [...config('app.locales'), 'xx' => 'Test']]);
    $user = User::factory()->create(['lang' => 'xx']);

    $this->actingAs($user)->get(route('guide'))->assertOk()->assertSee('User guide');
});

it('is linked from the user menu and closed to guests', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertSee(route('guide'));
    auth()->logout();
    $this->get(route('guide'))->assertRedirect(route('login'));
});
