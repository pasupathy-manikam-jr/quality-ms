<?php

namespace App\Livewire\Forms;

use App\Models\Ncr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class NcrForm extends Form
{
    #[Locked]
    public ?int $id = null;

    /** Only editable for NCRs entered by hand; system-raised ones keep their source. */
    public string $source = 'customer-complaint';

    public string $title = '';

    public string $description = '';

    public string $severity = 'minor';

    public string $part_id = '';

    public string $supplier_id = '';

    public string $customer = '';

    public string $quantity_affected = '';

    public function load(Ncr $ncr): void
    {
        $this->id = $ncr->id;
        $this->source = $ncr->source;
        $this->title = $ncr->title;
        $this->description = (string) $ncr->description;
        $this->severity = $ncr->severity;
        $this->part_id = (string) $ncr->part_id;
        $this->supplier_id = (string) $ncr->supplier_id;
        $this->customer = (string) $ncr->customer;
        $this->quantity_affected = (string) $ncr->quantity_affected;
    }

    public function save(): Ncr
    {
        $ncr = $this->id ? Ncr::query()->findOrFail($this->id) : new Ncr;
        $manual = ! $ncr->exists || in_array($ncr->source, Ncr::MANUAL_SOURCES, true);

        $validated = $this->validate([
            'source' => $manual ? ['required', Rule::in(Ncr::MANUAL_SOURCES)] : ['nullable'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'severity' => ['required', Rule::in(Ncr::SEVERITIES)],
            'part_id' => ['nullable', 'integer', Rule::exists('parts', 'id')],
            'supplier_id' => ['nullable', 'required_if:source,supplier', 'integer', Rule::exists('suppliers', 'id')],
            'customer' => ['nullable', 'required_if:source,customer-complaint', 'string', 'max:255'],
            'quantity_affected' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999999'],
        ], attributes: ['part_id' => __('part'), 'supplier_id' => __('supplier')]);

        $ncr->fill([
            ...array_map(fn ($value) => $value === '' ? null : $value, $validated),
            'source' => $manual ? $validated['source'] : $ncr->source,
        ])->save();

        $this->reset();

        return $ncr;
    }
}
