<?php

use Anthropic\Beta\Messages\BetaImageBlockParam;
use Anthropic\Beta\Messages\BetaRequestDocumentBlock;
use Anthropic\Client;
use App\Actions\Certificates\ReadCertificateFile;
use App\Livewire\Certificates\Show;
use App\Models\Certificate;
use App\Models\Lot;
use App\Models\Material;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * A reader that answers from $response instead of calling Claude, and keeps what it was sent.
 *
 * @param  array<string, mixed>|RuntimeException  $response
 */
function fakeReader(array|RuntimeException $response): ReadCertificateFile
{
    $reader = new class(app(Client::class), $response) extends ReadCertificateFile
    {
        /** @var list<mixed> */
        public array $sent = [];

        /** @param array<string, mixed>|RuntimeException $response */
        public function __construct(Client $client, private array|RuntimeException $response)
        {
            parent::__construct($client);
        }

        protected function ask(array $content): array
        {
            $this->sent = $content;

            if ($this->response instanceof RuntimeException) {
                throw $this->response;
            }

            return $this->response;
        }
    };

    app()->instance(ReadCertificateFile::class, $reader);

    return $reader;
}

beforeEach(function () {
    Storage::fake('local');
    config(['services.anthropic.key' => 'test-key']);

    $this->material = Material::factory()->create(['code' => 'S355JR']);
    $this->material->limits()->createMany([['property' => 'C', 'max' => '0.24'], ['property' => 'Yield', 'min' => '355']]);

    $this->certificate = Certificate::factory()->create();
    $this->certificate->attachUpload(UploadedFile::fake()->create('mtc.pdf', 40, 'application/pdf'))->save();
    $this->existing = Lot::factory()->for($this->certificate)->for($this->material)->create(['lot_number' => 'H1']);

    $this->actingAs($this->userWithRole('inspector'));
});

it('sends the PDF as a document block with the known property names', function () {
    $reader = fakeReader(['lots' => [], 'notes' => '']);

    $reader->read($this->certificate);

    expect($reader->sent[0])->toBeInstanceOf(BetaRequestDocumentBlock::class)
        ->and(json_encode($reader->sent[1]))->toContain('C, Yield');
});

it('sends a scan as an image block', function () {
    $this->certificate->attachUpload(UploadedFile::fake()->image('mtc.jpg'))->save();
    $reader = fakeReader(['lots' => [], 'notes' => '']);

    $reader->read($this->certificate);

    expect($reader->sent[0])->toBeInstanceOf(BetaImageBlockParam::class);
});

it('shows what was read for review, then saves it on apply', function () {
    fakeReader(['notes' => 'S printed as <0.005.', 'lots' => [
        ['lot_number' => 'H1', 'size' => null, 'quantity' => null, 'quantity_unit' => null, 'results' => [['property' => 'c', 'value' => '0.18'], ['property' => 'Yield', 'value' => '372']]],
        ['lot_number' => 'H2', 'size' => '12', 'quantity' => '4', 'quantity_unit' => 'plates', 'results' => [['property' => 'C', 'value' => '0.21'], ['property' => 'Hardness', 'value' => '150']]],
    ]]);

    $page = Livewire::test(Show::class, ['certificate' => $this->certificate])
        ->call('readFile')
        ->assertSet('extractedNotes', 'S printed as <0.005.')
        ->assertSet('extractMaterialId', (string) $this->material->id);

    expect($this->existing->results()->count())->toBe(0, 'Nothing is saved before apply.');

    $page->set('extracted.0.results.1.value', '370')->call('applyExtraction')->assertHasNoErrors();

    expect($this->existing->results()->pluck('value', 'property')->all())->toBe(['C' => '0.180000', 'Yield' => '370.000000'])
        ->and(Lot::query()->where('lot_number', 'H2')->sole()->results()->pluck('value', 'property')->all())->toBe(['C' => '0.210000'])
        ->and($this->certificate->auditLogs()->where('event', 'results-read-from-file')->exists())->toBeTrue();
});

it('refuses values that are not numbers', function () {
    fakeReader(['notes' => '', 'lots' => [['lot_number' => 'H1', 'size' => null, 'quantity' => null, 'quantity_unit' => null, 'results' => [['property' => 'C', 'value' => '0,18']]]]]);

    Livewire::test(Show::class, ['certificate' => $this->certificate])
        ->call('readFile')
        ->call('applyExtraction')
        ->assertHasErrors('extracted.0.results.0.value');

    expect($this->existing->results()->count())->toBe(0);
});

it('reports a reading failure without opening the review', function () {
    fakeReader(new RuntimeException('The certificate could not be read right now.'));

    Livewire::test(Show::class, ['certificate' => $this->certificate])
        ->call('readFile')
        ->assertSet('extracted', []);
});

it('is off without an API key and closed to people who cannot edit', function () {
    config(['services.anthropic.key' => null]);
    Livewire::test(Show::class, ['certificate' => $this->certificate])->call('readFile')->assertNotFound();

    config(['services.anthropic.key' => 'test-key']);
    $this->actingAs($this->userWithRole('viewer'));
    Livewire::test(Show::class, ['certificate' => $this->certificate])->call('readFile')->assertForbidden();
});
