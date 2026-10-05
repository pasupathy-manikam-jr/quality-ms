<?php

namespace App\Livewire\Audits;

use App\Models\AuditFinding;
use App\Models\QualityAudit;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read QualityAudit $audit
 */
class Show extends Component
{
    #[Locked]
    public int $auditId;

    public string $summary = '';

    public string $type = 'minor-nonconformity';

    public string $iso_clause_id = '';

    public string $description = '';

    public function mount(QualityAudit $audit): void
    {
        $this->auditId = $audit->id;
        $this->summary = (string) $audit->summary;
    }

    #[Computed]
    public function audit(): QualityAudit
    {
        return QualityAudit::query()->with(['leadAuditor', 'clauses', 'findings.clause', 'findings.ncr', 'findings.creator'])->findOrFail($this->auditId);
    }

    public function start(): void
    {
        $this->authorize('edit-audits');

        $this->audit->start();

        unset($this->audit);
        Flux::toast(variant: 'success', text: __('Audit started. Record findings as you go.'));
    }

    public function addFinding(): void
    {
        $this->authorize('edit-audits');

        $validated = $this->validate([
            'type' => ['required', Rule::in(AuditFinding::TYPES)],
            'iso_clause_id' => ['nullable', 'integer', Rule::exists('iso_clauses', 'id')],
            'description' => ['required', 'string', 'max:5000'],
        ], attributes: ['iso_clause_id' => __('clause')]);

        $finding = $this->audit->addFinding([
            'type' => $validated['type'],
            'iso_clause_id' => $validated['iso_clause_id'] ? (int) $validated['iso_clause_id'] : null,
            'description' => $validated['description'],
        ]);

        unset($this->audit);
        $this->reset('description', 'iso_clause_id');
        Flux::toast(variant: 'success', text: $finding->isNonconformity() ? __('Finding recorded and an NCR raised.') : __('Finding recorded.'));
    }

    public function saveSummary(): void
    {
        $this->authorize('edit-audits');
        abort_if($this->audit->status === 'completed', 403);

        $this->validate(['summary' => ['nullable', 'string', 'max:10000']]);
        $this->audit->update(['summary' => $this->summary ?: null]);

        unset($this->audit);
        Flux::toast(variant: 'success', text: __('Summary saved.'));
    }

    public function complete(): void
    {
        $this->saveSummary();
        $this->audit->complete();

        unset($this->audit);
        Flux::toast(variant: 'success', text: __('Audit completed.'));
    }

    public function render(): View
    {
        return view('livewire.audits.show')->title($this->audit->number);
    }
}
