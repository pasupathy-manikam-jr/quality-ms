<?php

namespace App\Livewire\Documents;

use App\Models\DocumentRevision;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Every signed-in user's list of documents they have been asked to read.
 *
 * @property-read Collection<int, DocumentRevision> $pending
 * @property-read Collection<int, DocumentRevision> $done
 */
#[Title('My reading list')]
class ReadingList extends Component
{
    /**
     * @return Collection<int, DocumentRevision>
     */
    #[Computed]
    public function pending(): Collection
    {
        return $this->mine()->wherePivotNull('acknowledged_at')->get();
    }

    /**
     * @return Collection<int, DocumentRevision>
     */
    #[Computed]
    public function done(): Collection
    {
        return $this->mine()->wherePivotNotNull('acknowledged_at')->orderByPivot('acknowledged_at', 'desc')->limit(20)->get();
    }

    public function acknowledge(int $revisionId): void
    {
        $updated = auth()->user()?->readings()->wherePivotNull('acknowledged_at')->updateExistingPivot($revisionId, ['acknowledged_at' => now()]);

        abort_unless(($updated ?? 0) > 0, 404);

        unset($this->pending, $this->done);
        Flux::toast(variant: 'success', text: __('Thanks, recorded as read.'));
    }

    public function render(): View
    {
        return view('livewire.documents.reading-list');
    }

    /**
     * Effective revisions only: a superseded one no longer needs reading.
     *
     * @return BelongsToMany<DocumentRevision, User>
     */
    private function mine(): BelongsToMany
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->readings()->where('document_revisions.status', 'effective')->with('document');
    }
}
