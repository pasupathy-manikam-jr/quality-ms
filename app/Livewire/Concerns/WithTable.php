<?php

namespace App\Livewire\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Search, sort and paginate a list page the same way everywhere. State lives in
 * the URL (?search=, ?sort=, ?direction=, ?per_page=, ?page=) so links can be shared.
 */
trait WithTable
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: '')]
    public string $sortField = '';

    #[Url(as: 'direction', except: 'desc')]
    public string $sortDirection = 'desc';

    #[Url(as: 'per_page', except: 10)]
    public int $perPage = 10;

    /**
     * @return list<int>
     */
    public function perPageOptions(): array
    {
        return [10, 25, 50, 100];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sort(string $field): void
    {
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $searchable  columns matched with LIKE
     * @param  list<string>  $sortable  columns the client may sort by
     * @return LengthAwarePaginator<int, TModel>
     */
    protected function paginateTable(Builder $query, array $searchable, array $sortable, string $defaultSort = 'created_at'): LengthAwarePaginator
    {
        $this->applySearch($query, $searchable);

        $field = in_array($this->sortField, $sortable, true) ? $this->sortField : $defaultSort;
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $perPage = in_array($this->perPage, $this->perPageOptions(), true) ? $this->perPage : $this->perPageOptions()[0];

        return $query->orderBy($field, $direction)->orderBy($query->qualifyColumn('id'), $direction)->paginate($perPage);
    }

    /**
     * Row counts per value of a column (status tabs), over the search and the query's filters.
     *
     * Selected columns, eager loads and ordering are dropped first: MySQL's only_full_group_by
     * rejects "table.*" or withCount() sub-selects next to GROUP BY.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $searchable
     * @return array<string, int>
     */
    protected function countBy(Builder $query, string $column, array $searchable): array
    {
        $query = $this->applySearch(clone $query, $searchable);

        return $query->toBase()->reorder()
            ->select($column)->selectRaw('count(*) as total')
            ->groupBy($column)
            ->pluck('total', $column)
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $searchable  columns matched with LIKE; "relation.column" searches a relation
     * @return Builder<TModel>
     */
    protected function applySearch(Builder $query, array $searchable): Builder
    {
        if ($search = trim($this->search)) {
            $like = '%'.addcslashes($search, '%_\\').'%';

            $query->where(function (Builder $q) use ($searchable, $like) {
                foreach ($searchable as $column) {
                    str_contains($column, '.')
                        ? $q->orWhereRelation(Str::beforeLast($column, '.'), Str::afterLast($column, '.'), 'like', $like)
                        : $q->orWhere($column, 'like', $like);
                }
            });
        }

        return $query;
    }
}
