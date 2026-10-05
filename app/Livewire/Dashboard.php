<?php

namespace App\Livewire;

use App\Models\Capa;
use App\Models\CapaAction;
use App\Models\Certificate;
use App\Models\Document;
use App\Models\DocumentRevision;
use App\Models\Gauge;
use App\Models\Inspection;
use App\Models\InspectionPlan;
use App\Models\IsoClause;
use App\Models\Ncr;
use App\Models\QualityAudit;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    /**
     * The number cards; each only for people who may open the list it links to.
     * Labels are translation keys, translated in the view.
     *
     * @return list<array{label: string, value: int, route: string, query: array<string, int|string>, icon: string}>
     */
    #[Computed]
    public function tiles(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $tiles = [
            ['manage-ncrs', 'Open NCRs', fn () => Ncr::query()->whereIn('status', ['draft', 'open', 'disposition-approved'])->count(), 'ncrs.index', [], 'exclamation-triangle'],
            ['manage-capas', 'Overdue CAPAs', fn () => Capa::query()->overdue()->count(), 'capas.index', ['overdue' => 1], 'wrench-screwdriver'],
            ['manage-gauges', 'Gauges overdue', fn () => Gauge::query()->inState('overdue')->count(), 'gauges.index', ['state' => 'overdue'], 'scale'],
            ['manage-gauges', 'Gauges due soon', fn () => Gauge::query()->inState('due')->count(), 'gauges.index', ['state' => 'due'], 'clock'],
            ['manage-certificates', 'Certificates to verify', fn () => Certificate::query()->where('status', 'received')->count(), 'certificates.index', ['status' => 'received'], 'document-check'],
            ['manage-inspections', 'Inspections in progress', fn () => Inspection::query()->where('status', 'in-progress')->count(), 'inspections.index', ['status' => 'in-progress'], 'clipboard-document-check'],
            ['manage-documents', 'Documents due for review', fn () => Document::query()->dueForReview()->count(), 'documents.index', ['due' => 1], 'document-text'],
            [null, 'Documents for me to read', fn () => $user->readings()->where('document_revisions.status', 'effective')->wherePivotNull('acknowledged_at')->count(), 'reading-list', [], 'book-open'],
        ];

        $visible = array_filter($tiles, fn (array $tile) => $tile[0] === null || $user->can($tile[0]));

        return array_values(array_map(fn (array $tile) => [
            'label' => $tile[1], 'value' => ($tile[2])(), 'route' => $tile[3], 'query' => $tile[4], 'icon' => $tile[5],
        ], $visible));
    }

    /**
     * Things waiting for this person, most urgent first: approvals their role allows (never
     * their own work), and CAPAs, actions and gauges they own.
     *
     * @return list<array{label: string, detail: string, url: string, due: string|null, overdue: bool}>
     */
    #[Computed]
    public function waiting(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $today = now()->toDateString();
        $items = [];

        if ($user->can('approve-documents')) {
            DocumentRevision::query()->with('document:id,number,title')->where('status', 'in-review')
                ->where(fn ($q) => $q->whereNull('created_by')->orWhere('created_by', '!=', $user->id))
                ->get()->each(function (DocumentRevision $r) use (&$items) {
                    $items[] = ['label' => __('Approve :number rev :r', ['number' => $r->document->number, 'r' => $r->revision]), 'detail' => $r->document->title, 'url' => route('documents.show', $r->document_id), 'due' => null, 'overdue' => false];
                });
        }

        if ($user->can('approve-ncrs')) {
            Ncr::query()->where('status', 'open')->whereNotNull('disposition')->get()->each(function (Ncr $n) use (&$items) {
                $items[] = ['label' => __('Approve the disposition of :number', ['number' => $n->number]), 'detail' => $n->title, 'url' => route('ncrs.show', $n), 'due' => null, 'overdue' => false];
            });
        }

        if ($user->can('approve-inspection-plans')) {
            InspectionPlan::query()->where('status', 'draft')->has('items')
                ->where(fn ($q) => $q->whereNull('created_by')->orWhere('created_by', '!=', $user->id))
                ->get()->each(function (InspectionPlan $p) use (&$items) {
                    $items[] = ['label' => __('Approve plan :title', ['title' => $p->title]), 'detail' => __('Revision :n', ['n' => $p->revision]), 'url' => route('inspection-plans.show', $p), 'due' => null, 'overdue' => false];
                });
        }

        if ($user->can('manage-capas')) {
            CapaAction::query()->with('capa:id,number,status')->where('owner_id', $user->id)->whereNull('done_at')
                ->whereHas('capa', fn ($q) => $q->where('status', '!=', 'closed'))
                ->get()->each(function (CapaAction $a) use (&$items, $today) {
                    $items[] = ['label' => $a->description, 'detail' => __('Action on :number', ['number' => $a->capa->number]), 'url' => route('capas.show', $a->capa_id), 'due' => $a->due_on?->toDateString(), 'overdue' => $a->due_on !== null && $a->due_on->toDateString() < $today];
                });

            Capa::query()->where('owner_id', $user->id)->where('status', '!=', 'closed')->get()->each(function (Capa $c) use (&$items) {
                $items[] = ['label' => __(':number: :status', ['number' => $c->number, 'status' => __(Str::headline($c->status))]), 'detail' => $c->title, 'url' => route('capas.show', $c), 'due' => $c->due_on?->toDateString(), 'overdue' => $c->isOverdue()];
            });
        }

        if ($user->can('manage-gauges')) {
            Gauge::query()->where('owner_id', $user->id)->where(fn ($q) => $q->inState('due')->orWhere(fn ($q) => $q->inState('overdue')))
                ->get()->each(function (Gauge $g) use (&$items) {
                    $items[] = ['label' => __('Calibrate :code', ['code' => $g->code]), 'detail' => $g->description, 'url' => route('gauges.show', $g), 'due' => $g->next_due_on?->toDateString(), 'overdue' => $g->state() === 'overdue'];
                });
        }

        usort($items, fn (array $a, array $b) => [! $a['overdue'], $a['due'] ?? '9999'] <=> [! $b['overdue'], $b['due'] ?? '9999']);

        return $items;
    }

    /**
     * NCRs raised in the last 90 days by source, largest first, with the running share (Pareto).
     *
     * @return list<array{source: string, count: int, share: float, cumulative: float}>
     */
    #[Computed]
    public function pareto(): array
    {
        $counts = Ncr::query()
            ->where('created_at', '>=', now()->subDays(90))
            ->where('status', '!=', 'cancelled')
            ->select('source', DB::raw('count(*) as total'))
            ->groupBy('source')
            ->orderByDesc('total')
            ->pluck('total', 'source')
            ->map(fn ($total) => (int) $total);

        $sum = max(1, $counts->sum());
        $running = 0;

        return array_values($counts->map(function (int $count, string $source) use ($sum, &$running) {
            $running += $count;

            return ['source' => $source, 'count' => $count, 'share' => round($count / $sum * 100, 1), 'cumulative' => round($running / $sum * 100, 1)];
        })->all());
    }

    /**
     * Evidence per ISO 9001 clause: tagged documents plus the records the modules keep.
     * Source labels are translation keys, translated in the view.
     *
     * @return list<array{number: string, title: string, heading: bool, documents: int, records: int, source: string|null}>
     */
    #[Computed]
    public function isoMatrix(): array
    {
        $documents = DB::table('document_iso_clause')
            ->join('document_revisions', fn ($join) => $join->on('document_revisions.document_id', '=', 'document_iso_clause.document_id')->where('document_revisions.status', 'effective'))
            ->select('iso_clause_id', DB::raw('count(distinct document_iso_clause.document_id) as total'))
            ->groupBy('iso_clause_id')
            ->pluck('total', 'iso_clause_id');

        $records = [
            '7.1.5' => [Gauge::query()->where('status', 'active')->count(), 'active gauges'],
            '7.5' => [Document::query()->whereHas('effectiveRevision')->count(), 'controlled documents'],
            '8.4' => [Certificate::query()->count(), 'supplier certificates'],
            '8.6' => [Inspection::query()->where('status', '!=', 'in-progress')->count(), 'completed inspections'],
            '8.7' => [Ncr::query()->count(), 'NCRs'],
            '9.2' => [QualityAudit::query()->where('status', 'completed')->count(), 'completed audits'],
            '10.2' => [Capa::query()->count(), 'CAPAs'],
        ];

        return array_values(IsoClause::query()->orderBy('id')->get()->map(fn (IsoClause $clause) => [
            'number' => $clause->number,
            'title' => $clause->title,
            'heading' => ! str_contains($clause->number, '.'),
            'documents' => (int) ($documents[$clause->id] ?? 0),
            'records' => $records[$clause->number][0] ?? 0,
            'source' => $records[$clause->number][1] ?? null,
        ])->all());
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
