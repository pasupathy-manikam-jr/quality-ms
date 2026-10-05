<?php

namespace App\Livewire\Certificates;

use App\Actions\Certificates\ReadCertificateFile;
use App\Livewire\Concerns\SignsRecords;
use App\Livewire\Forms\CertificateForm;
use App\Models\Certificate;
use App\Models\Lot;
use App\Models\Material;
use App\Models\Supplier;
use App\Support\Decimal;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * @property-read Certificate $certificate
 * @property-read Collection<int, Material> $materials
 * @property-read Collection<int, Supplier> $suppliers
 */
class Show extends Component
{
    use SignsRecords, WithFileUploads;

    #[Locked]
    public int $certificateId;

    public CertificateForm $form;

    #[Locked]
    public ?int $lotId = null;

    public string $material_id = '';

    public string $lot_number = '';

    public string $size = '';

    public string $quantity = '';

    public string $quantity_unit = '';

    public string $expires_on = '';

    #[Locked]
    public ?int $deletingLotId = null;

    #[Locked]
    public ?int $resultsLotId = null;

    /** @var list<string> one value per resultProperties() entry, same order */
    public array $resultValues = [];

    public string $reason = '';

    /** @var list<array{lot_number: string, size: string|null, quantity: string|null, quantity_unit: string|null, results: list<array{property: string, value: string}>}> what Claude read, editable before applying */
    public array $extracted = [];

    public string $extractedNotes = '';

    public string $extractMaterialId = '';

    public function mount(Certificate $certificate): void
    {
        $this->certificateId = $certificate->id;
    }

    #[Computed]
    public function certificate(): Certificate
    {
        return Certificate::query()
            ->with(['supplier', 'decider', 'creator', 'lots.material.limits', 'lots.results', 'signatures'])
            ->findOrFail($this->certificateId);
    }

    /**
     * @return Collection<int, Supplier>
     */
    #[Computed]
    public function suppliers(): Collection
    {
        return Supplier::query()->orderBy('name')->get(['id', 'code', 'name', 'is_approved']);
    }

    /**
     * @return Collection<int, Material>
     */
    #[Computed]
    public function materials(): Collection
    {
        return Material::query()->orderBy('code')->get(['id', 'code', 'name', 'size_label']);
    }

    public function selectedMaterial(): ?Material
    {
        return $this->materials->firstWhere('id', (int) $this->material_id);
    }

    // Certificate details ------------------------------------------------------------------

    public function edit(): void
    {
        $this->authorizeEdit();

        $this->form->load($this->certificate);
        $this->resetValidation();
        Flux::modal('certificate-form')->show();
    }

    public function save(): void
    {
        $this->authorizeEdit();

        $this->form->id = $this->certificateId;
        $this->form->save();

        unset($this->certificate);
        Flux::modal('certificate-form')->close();
        Flux::toast(variant: 'success', text: __('Certificate updated.'));
    }

    // Lots -------------------------------------------------------------------------------

    public function addLot(): void
    {
        $this->authorizeEdit();

        $this->resetLotForm();
        Flux::modal('lot-form')->show();
    }

    public function editLot(int $id): void
    {
        $this->authorizeEdit();

        $lot = $this->lot($id);

        $this->resetLotForm();
        $this->lotId = $lot->id;
        $this->material_id = (string) $lot->material_id;
        $this->lot_number = $lot->lot_number;
        $this->size = (string) $lot->size;
        $this->quantity = (string) $lot->quantity;
        $this->quantity_unit = (string) $lot->quantity_unit;
        $this->expires_on = (string) $lot->expires_on?->format('Y-m-d');

        Flux::modal('lot-form')->show();
    }

    public function saveLot(): void
    {
        $this->authorizeEdit();

        $validated = $this->validate([
            'material_id' => ['required', 'integer', Rule::exists('materials', 'id')],
            'lot_number' => ['required', 'string', 'max:100', Rule::unique('lots')->where('certificate_id', $this->certificateId)->ignore($this->lotId)],
            'size' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999'],
            'quantity' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999999'],
            'quantity_unit' => ['nullable', 'string', 'max:20'],
            'expires_on' => ['nullable', 'date_format:Y-m-d'],
        ], attributes: ['material_id' => __('material'), 'lot_number' => Lot::label()]);

        $lot = $this->lotId ? $this->lot($this->lotId) : new Lot(['certificate_id' => $this->certificateId]);
        $lot->fill([
            ...array_map(fn ($value) => $value === '' ? null : $value, $validated),
            'material_id' => (int) $validated['material_id'],
            'size' => $this->selectedMaterial()?->size_label ? ($validated['size'] ?: null) : null,
        ])->save();

        unset($this->certificate);
        Flux::modal('lot-form')->close();
        Flux::toast(variant: 'success', text: __(':lot saved.', ['lot' => Lot::label()]));
        $this->resetLotForm();
    }

    public function confirmDeleteLot(int $id): void
    {
        $this->authorizeEdit();

        $this->deletingLotId = $this->lot($id)->id;
        Flux::modal('confirm-lot-delete')->show();
    }

    public function deleteLot(): void
    {
        $this->authorizeEdit();

        $this->lot((int) $this->deletingLotId)->delete();

        unset($this->certificate);
        $this->deletingLotId = null;
        Flux::modal('confirm-lot-delete')->close();
        Flux::toast(variant: 'success', text: __(':lot removed.', ['lot' => Lot::label()]));
    }

    // Results ----------------------------------------------------------------------------

    /**
     * The properties shown in the results form: those with a limit at the lot's size,
     * plus any already recorded.
     *
     * @return list<array{property: string, unit: string|null, range: string|null}>
     */
    public function resultProperties(): array
    {
        if (! $this->resultsLotId) {
            return [];
        }

        $lot = $this->lot($this->resultsLotId);
        $limits = collect($lot->applicableLimits())->keyBy('property');

        return array_values($limits->keys()->merge($lot->results->pluck('property'))->unique()->sort()
            ->map(fn (string $property) => [
                'property' => $property,
                'unit' => $limits->get($property)?->unit,
                'range' => $limits->get($property)?->rangeLabel(),
            ])->all());
    }

    public function enterResults(int $lotId): void
    {
        $this->authorizeEdit();

        $lot = $this->lot($lotId);
        $this->resultsLotId = $lot->id;
        $recorded = $lot->results->pluck('value', 'property');

        $this->resultValues = array_map(
            fn (array $row) => $recorded->has($row['property']) ? Decimal::format((string) $recorded[$row['property']]) : '',
            $this->resultProperties(),
        );

        $this->resetValidation();
        Flux::modal('results-form')->show();
    }

    public function saveResults(): void
    {
        $this->authorizeEdit();

        $properties = array_column($this->resultProperties(), 'property');

        $this->validate([
            'resultValues' => ['array', 'size:'.count($properties)],
            'resultValues.*' => ['nullable', 'numeric', 'decimal:0,6', 'min:-999999999999', 'max:999999999999'],
        ], attributes: collect($properties)->mapWithKeys(fn (string $property, int $i) => ["resultValues.{$i}" => $property])->all());

        $lot = $this->lot((int) $this->resultsLotId);

        DB::transaction(function () use ($lot, $properties) {
            foreach ($properties as $i => $property) {
                $value = trim((string) ($this->resultValues[$i] ?? ''));
                $existing = $lot->results->firstWhere('property', $property);

                if ($value === '') {
                    $existing?->delete();
                } elseif ($existing) {
                    $existing->update(['value' => $value]);
                } else {
                    $lot->results()->create(['property' => $property, 'value' => $value]);
                }
            }
        });

        unset($this->certificate);
        $this->reset('resultsLotId', 'resultValues');
        Flux::modal('results-form')->close();
        Flux::toast(variant: 'success', text: __('Results saved.'));
    }

    // Reading the file with Claude ----------------------------------------------------------

    public function readFile(ReadCertificateFile $reader): void
    {
        $this->authorizeEdit();
        abort_unless(ReadCertificateFile::isEnabled(), 404);

        try {
            $result = $reader->read($this->certificate);
        } catch (RuntimeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->extracted = $result['lots'];
        $this->extractedNotes = $result['notes'];
        $this->extractMaterialId = (string) $this->certificate->lots->value('material_id');
        $this->resetValidation();

        Flux::modal('extract-review')->show();
    }

    /**
     * Save what was read (after the person's edits): existing lots are matched by number, new
     * ones get the chosen material; only properties that material has limits for are kept.
     */
    public function applyExtraction(): void
    {
        $this->authorizeEdit();

        $this->validate([
            'extracted' => ['required', 'array', 'max:50'],
            'extracted.*.lot_number' => ['required', 'string', 'max:100', 'distinct'],
            'extracted.*.size' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999'],
            'extracted.*.quantity' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999999'],
            'extracted.*.quantity_unit' => ['nullable', 'string', 'max:20'],
            'extracted.*.results' => ['array', 'max:100'],
            'extracted.*.results.*.property' => ['required', 'string', 'max:50'],
            'extracted.*.results.*.value' => ['nullable', 'numeric', 'decimal:0,6', 'min:-999999999999', 'max:999999999999'],
            'extractMaterialId' => ['required', 'integer', Rule::exists('materials', 'id')],
        ], attributes: ['extractMaterialId' => __('material')]);

        $certificate = $this->certificate;
        $material = Material::query()->with('limits')->findOrFail((int) $this->extractMaterialId);
        $skipped = [];

        DB::transaction(function () use ($certificate, $material, &$skipped) {
            foreach ($this->extracted as $row) {
                $lot = $certificate->lots()->firstOrCreate(
                    ['lot_number' => trim($row['lot_number'])],
                    [
                        'material_id' => $material->id,
                        'size' => $material->size_label ? (($row['size'] ?? '') ?: null) : null,
                        'quantity' => ($row['quantity'] ?? '') ?: null,
                        'quantity_unit' => ($row['quantity_unit'] ?? '') ?: null,
                    ],
                );
                $known = $lot->material()->with('limits')->firstOrFail()->limits->mapWithKeys(fn ($l) => [mb_strtolower($l->property) => $l->property]);

                foreach ($row['results'] as $result) {
                    $property = $known[mb_strtolower(trim($result['property']))] ?? null;
                    $value = trim($result['value']);

                    if ($property === null || $value === '') {
                        $skipped[] = $result['property'];

                        continue;
                    }

                    $lot->results()->updateOrCreate(['property' => $property], ['value' => $value]);
                }
            }

            $certificate->audit('results-read-from-file', null, ['model' => config('services.anthropic.model'), 'lots' => count($this->extracted)]);
        });

        unset($this->certificate);
        $this->reset('extracted', 'extractedNotes', 'extractMaterialId');
        Flux::modal('extract-review')->close();
        Flux::toast(variant: 'success', text: $skipped === []
            ? __('Results saved. Check them against the certificate before verifying.')
            : __('Results saved. Skipped (no limit for this material): :list.', ['list' => implode(', ', array_unique($skipped))]));
    }

    // Decision ---------------------------------------------------------------------------

    /**
     * @return array<string, string>
     */
    protected function signedActions(): array
    {
        return ['verify' => 'verified'];
    }

    public function verify(): void
    {
        $this->authorize('verify-certificates');

        $this->signAs($this->certificate, 'verified', fn () => $this->certificate->transitionTo('verified'));

        unset($this->certificate);
        Flux::toast(variant: 'success', text: __('Certificate verified. Its :lots can be used.', ['lots' => strtolower(Lot::label())]));
    }

    public function openReject(): void
    {
        $this->authorize('verify-certificates');

        $this->reset('reason');
        $this->resetValidation();
        Flux::modal('reject-form')->show();
    }

    public function reject(): void
    {
        $this->authorize('verify-certificates');

        $this->validate(['reason' => ['required', 'string', 'max:2000']]);
        $this->signAs($this->certificate, 'rejected', fn () => $this->certificate->transitionTo('rejected', $this->reason));

        unset($this->certificate);
        Flux::modal('reject-form')->close();
        Flux::toast(variant: 'success', text: __('Certificate rejected.'));
    }

    public function delete(): void
    {
        $this->authorize('delete-certificates');
        abort_unless($this->certificate->isEditable(), 403);

        $this->certificate->delete();

        Flux::toast(variant: 'success', text: __('Certificate deleted.'));
        $this->redirectRoute('certificates.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.certificates.show')->title($this->certificate->number);
    }

    /**
     * Lots, results and details can only change while the certificate waits for a decision.
     */
    private function authorizeEdit(): void
    {
        $this->authorize('edit-certificates');
        abort_unless($this->certificate->isEditable(), 403, __('A decided certificate cannot be changed.'));
    }

    private function lot(int $id): Lot
    {
        return Lot::query()->with(['material.limits', 'results'])->where('certificate_id', $this->certificateId)->findOrFail($id);
    }

    private function resetLotForm(): void
    {
        $this->reset('lotId', 'material_id', 'lot_number', 'size', 'quantity', 'quantity_unit', 'expires_on');
        $this->resetValidation();
    }
}
