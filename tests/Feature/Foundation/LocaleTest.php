<?php

use App\Models\Ncr;
use App\Models\User;

it('switches a signed-in user\'s language and remembers it on their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from(route('dashboard'))->post(route('locale.update'), ['locale' => 'ms'])->assertRedirect(route('dashboard'));

    expect($user->refresh()->lang)->toBe('ms');
    $this->get(route('dashboard'))->assertSee('Papan pemuka', escape: false)->assertSee('lang="ms"', escape: false);
});

it('keeps a guest\'s choice in the session', function () {
    $this->post(route('locale.update'), ['locale' => 'zh']);

    $this->get(route('login'))->assertSee('lang="zh"', escape: false);
});

it('refuses languages that are not offered', function () {
    $this->post(route('locale.update'), ['locale' => 'xx'])->assertSessionHasErrors('locale');
});

it('has a translation, with the same placeholders, for every key in every language file', function (string $locale) {
    $strings = json_decode((string) file_get_contents(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);

    foreach ($strings as $key => $value) {
        preg_match_all('/:[a-z_]+/', $key, $expected);
        preg_match_all('/:[a-z_]+/', $value, $actual);
        sort($expected[0]);
        sort($actual[0]);

        expect($actual[0])->toBe($expected[0], "Placeholders differ in [{$locale}] {$key}");
    }
})->with(['ms', 'zh']);

it('never lets a language file shadow a phrase key (macOS file names ignore case)', function () {
    $phrases = collect(json_decode((string) file_get_contents(lang_path('zh.json')), true))->keys();
    $files = collect(glob(lang_path('en/*.php')))->map(fn (string $f) => strtolower(basename($f, '.php')));

    expect($phrases->map(fn (string $k) => strtolower($k))->intersect($files)->values()->all())->toBe([]);

    foreach (['en', 'ms', 'zh'] as $locale) {
        app()->setLocale($locale);
        expect(__('Actions'))->toBeString()->and(__('ui_verbs.open'))->toBeString()->not->toBe('ui_verbs.open');
    }
});

it('shows an Open button on list rows', function () {
    Ncr::factory()->create();
    $this->actingAs($this->userWithRole('viewer'));

    $this->get(route('ncrs.index'))->assertOk()->assertSee('Open');
});
