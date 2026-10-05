<?php

namespace App\Livewire\Audits;

use App\Livewire\Concerns\WithTable;
use App\Models\IsoClause;
use App\Models\QualityAudit;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, IsoClause> $isoClauses
 */
#[Title('Internal audits')]
class Index extends Component
{
    use WithTable;

    private const SEARCHABLE = ['number', 'title', 'leadAuditor.name'];

    #[Url(except: '')]
    public string $status = '';

    public string $title = '';

    public string $scope = '';

    public string $lead_auditor_id = '';

    public string $planned_on = '';

    /** @var array<int, string> */
    public array $clauseIds = [];

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, QualityAudit>
     */
    #[Computed]
    public function audits(): LengthAwarePaginator
    {
        return $this->paginateTable($this->listQuery(), self::SEARCHABLE, ['number', 'planned_on'], 'planned_on');
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        return $this->countBy(QualityAudit::query(), 'status', self::SEARCHABLE);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return Collection<int, IsoClause>
     */
    #[Computed]
    public function isoClauses(): Collection
    {
        return IsoClause::query()->orderBy('id')->get();
    }

    public function create(): void
    {
        $this->authorize('create-audits');

        $this->reset('title', 'scope', 'planned_on', 'clauseIds');
        $this->lead_auditor_id = (string) auth()->id();
        $this->resetValidation();
        Flux::modal('audit-form')->show();
    }

    public function save(): void
    {
        $this->authorize('create-audits');

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'max:5000'],
            'lead_auditor_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'planned_on' => ['required', 'date_format:Y-m-d'],
            'clauseIds' => ['array'],
            'clauseIds.*' => ['integer', Rule::exists('iso_clauses', 'id')],
        ], attributes: ['lead_auditor_id' => __('lead auditor'), 'clauseIds' => __('ISO clauses')]);

        $audit = QualityAudit::query()->create([
            'title' => $validated['title'],
            'scope' => $validated['scope'] ?: null,
            'lead_auditor_id' => (int) $validated['lead_auditor_id'],
            'planned_on' => $validated['planned_on'],
        ]);
        $audit->clauses()->sync(array_map('intval', $validated['clauseIds']));

        Flux::toast(variant: 'success', text: __('Audit :number planned.', ['number' => $audit->number]));
        $this->redirectRoute('audits.show', $audit, navigate: true);
    }

    /**
     * The list as on screen (search excluded; WithTable adds it), shared by the table and the export.
     *
     * @return Builder<QualityAudit>
     */
    private function listQuery(): Builder
    {
        return QualityAudit::query()
            ->with('leadAuditor:id,name')
            ->withCount('findings')
            ->when(in_array($this->status, QualityAudit::STATUSES, true), fn (Builder $q) => $q->where('status', $this->status));
    }

    public function export(): StreamedResponse
    {
        return $this->exportCsv($this->listQuery(), self::SEARCHABLE, 'audits', [
            'Number' => fn (QualityAudit $a) => $a->number,
            'Title' => fn (QualityAudit $a) => $a->title,
            'Lead auditor' => fn (QualityAudit $a) => $a->leadAuditor?->name,
            'Planned' => fn (QualityAudit $a) => $a->planned_on->format('Y-m-d'),
            'Findings' => fn (QualityAudit $a) => $a->findings_count,
            'Status' => fn (QualityAudit $a) => __(Str::headline($a->status)),
            'Completed' => fn (QualityAudit $a) => $a->completed_at?->format('Y-m-d'),
        ]);
    }

    public function render(): View
    {
        return view('livewire.audits.index');
    }
}
