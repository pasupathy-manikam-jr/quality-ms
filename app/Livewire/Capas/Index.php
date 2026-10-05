<?php

namespace App\Livewire\Capas;

use App\Livewire\Concerns\WithTable;
use App\Models\Capa;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Collection<int, User> $owners
 */
#[Title('CAPA')]
class Index extends Component
{
    use WithTable;

    private const SEARCHABLE = ['number', 'title', 'owner.name'];

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: false)]
    public bool $overdue = false;

    public string $title = '';

    public string $type = 'corrective';

    public string $owner_id = '';

    public string $due_on = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedOverdue(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Capa>
     */
    #[Computed]
    public function capas(): LengthAwarePaginator
    {
        $query = $this->filtered()
            ->with('owner:id,name')
            ->withCount('ncrs')
            ->when(in_array($this->status, Capa::STATUSES, true), fn (Builder $q) => $q->where('status', $this->status));

        return $this->paginateTable($query, self::SEARCHABLE, ['number', 'due_on', 'created_at'], 'created_at');
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        return $this->countBy($this->filtered(), 'status', self::SEARCHABLE);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function owners(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    public function create(): void
    {
        $this->authorize('create-capas');

        $this->reset('title', 'type', 'owner_id', 'due_on');
        $this->owner_id = (string) auth()->id();
        $this->due_on = now()->addDays(30)->toDateString();
        $this->resetValidation();
        Flux::modal('capa-form')->show();
    }

    public function save(): void
    {
        $this->authorize('create-capas');

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Capa::TYPES)],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'due_on' => ['required', 'date_format:Y-m-d'],
        ], attributes: ['owner_id' => __('owner')]);

        $capa = Capa::query()->create($validated);

        Flux::toast(variant: 'success', text: __('CAPA :number created.', ['number' => $capa->number]));
        $this->redirectRoute('capas.show', $capa, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.capas.index');
    }

    /**
     * @return Builder<Capa>
     */
    private function filtered(): Builder
    {
        return Capa::query()->when($this->overdue, fn (Builder $q) => $q->overdue());
    }
}
