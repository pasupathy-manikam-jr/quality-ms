<?php

use App\Livewire\Certificates\Show;
use App\Models\Lot;
use App\Models\Material;
use App\Models\Signature;
use Livewire\Livewire;

beforeEach(function () {
    $material = Material::factory()->create();
    $material->limits()->create(['property' => 'C', 'max' => '0.2']);
    $lot = Lot::factory()->for($material)->create();
    $lot->results()->create(['property' => 'C', 'value' => '0.1']);
    $this->certificate = $lot->certificate;
    $this->manager = $this->userWithRole('quality-manager');
    $this->actingAs($this->manager);
});

it('refuses a signed action without the password, or with a wrong one', function (?string $password) {
    $page = Livewire::test(Show::class, ['certificate' => $this->certificate]);

    if ($password !== null) {
        $page->set('signaturePassword', $password);
    }

    $page->call('verify')->assertHasErrors('signaturePassword');

    expect($this->certificate->refresh()->status)->toBe('received')
        ->and(Signature::query()->count())->toBe(0);
})->with(['no password' => [null], 'wrong password' => ['not-my-password']]);

it('records who signed, what it meant and when, through the signature dialog', function () {
    Livewire::test(Show::class, ['certificate' => $this->certificate])
        ->call('requestSignature', 'verify')
        ->assertSet('signingAction', 'verify')
        ->set('signaturePassword', 'password')
        ->call('sign')
        ->assertHasNoErrors()
        ->assertSet('signaturePassword', '');

    expect($this->certificate->refresh()->status)->toBe('verified')
        ->and($this->certificate->signatures()->sole())
        ->user_id->toBe($this->manager->id)
        ->signer_name->toBe($this->manager->name)
        ->meaning->toBe('verified');
});

it('only opens the dialog for the page\'s own signed actions', function () {
    Livewire::test(Show::class, ['certificate' => $this->certificate])->call('requestSignature', 'delete')->assertNotFound();
});

it('keeps signatures from being changed or deleted', function () {
    Livewire::test(Show::class, ['certificate' => $this->certificate])->set('signaturePassword', 'password')->call('verify');
    $signature = Signature::query()->sole();

    expect(fn () => $signature->update(['meaning' => 'forged']))->toThrow(LogicException::class)
        ->and(fn () => $signature->delete())->toThrow(LogicException::class);
});
