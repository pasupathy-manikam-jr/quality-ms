<?php

namespace App\Livewire\Forms;

use App\Models\Certificate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class CertificateForm extends Form
{
    #[Locked]
    public ?int $id = null;

    public string $supplier_id = '';

    public string $number = '';

    public string $type = 'en10204-3.1';

    public string $issued_on = '';

    public string $po_number = '';

    public string $third_party_inspector = '';

    public ?TemporaryUploadedFile $file = null;

    public function load(Certificate $certificate): void
    {
        $this->id = $certificate->id;
        $this->supplier_id = (string) $certificate->supplier_id;
        $this->number = $certificate->number;
        $this->type = $certificate->type;
        $this->issued_on = $certificate->issued_on->format('Y-m-d');
        $this->po_number = (string) $certificate->po_number;
        $this->third_party_inspector = (string) $certificate->third_party_inspector;
    }

    /**
     * Validate, then create or update the certificate (storing any new file).
     */
    public function save(): Certificate
    {
        $certificate = $this->id ? Certificate::query()->findOrFail($this->id) : new Certificate;

        $validated = $this->validate([
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'number' => ['required', 'string', 'max:100', Rule::unique('certificates')->where('supplier_id', $this->supplier_id)->ignore($certificate->id)],
            'type' => ['required', Rule::in(array_keys(Certificate::TYPES))],
            'issued_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'po_number' => ['nullable', 'string', 'max:100'],
            'third_party_inspector' => ['nullable', 'required_if:type,en10204-3.2', 'string', 'max:255'],
            'file' => Certificate::uploadRules(),
        ], attributes: ['supplier_id' => __('supplier'), 'number' => __('certificate number')]);

        $certificate->fill([
            'supplier_id' => (int) $validated['supplier_id'],
            'number' => $validated['number'],
            'type' => $validated['type'],
            'issued_on' => $validated['issued_on'],
            'po_number' => $validated['po_number'] ?: null,
            'third_party_inspector' => $validated['type'] === 'en10204-3.2' ? ($validated['third_party_inspector'] ?: null) : null,
        ]);

        if ($this->file) {
            $certificate->attachUpload($this->file);
        }

        $certificate->save();
        $this->reset();

        return $certificate;
    }
}
