<?php

namespace App\Support;

use App\Models\CapaAction;
use App\Models\Document;
use App\Models\DocumentRevision;
use App\Models\Gauge;
use App\Models\Ncr;
use App\Models\User;
use App\Notifications\ActionNeeded;
use App\Notifications\CalibrationDue;
use App\Notifications\DocumentReviewDue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Every notification the app sends, one method per event.
 */
class Notify
{
    /**
     * @param  Collection<int, Gauge>  $gauges
     */
    public static function calibrationDue(User $owner, Collection $gauges): void
    {
        $owner->notify(new CalibrationDue($gauges));
    }

    /**
     * @param  Collection<int, Document>  $documents
     */
    public static function documentReviewDue(User $owner, Collection $documents): void
    {
        $owner->notify(new DocumentReviewDue($documents));
    }

    /**
     * A document revision was sent for review: tell everyone who may approve it, except its author.
     */
    public static function documentReviewRequested(DocumentRevision $revision): void
    {
        $document = $revision->document;

        self::toPermission('approve-documents', $revision->created_by, new ActionNeeded(
            __(':number rev :r is waiting for approval', ['number' => $document->number, 'r' => $revision->revision]),
            __(':title was sent for review. Check it and approve it, or return it to draft.', ['title' => $document->title]),
            route('documents.show', $document),
            __('Open document'),
        ));
    }

    /**
     * An NCR disposition was proposed: tell everyone who may approve it, except who proposed it.
     */
    public static function ncrDispositionProposed(Ncr $ncr, ?int $proposedBy): void
    {
        self::toPermission('approve-ncrs', $proposedBy, new ActionNeeded(
            __(':number: disposition waiting for approval', ['number' => $ncr->number]),
            __(':title. Proposed disposition: :disposition.', ['title' => $ncr->title, 'disposition' => __(Str::headline((string) $ncr->disposition))]),
            route('ncrs.show', $ncr),
            __('Open NCR'),
        ));
    }

    /**
     * A CAPA action was given to someone other than the person adding it.
     */
    public static function capaActionAssigned(CapaAction $action, ?int $assignedBy): void
    {
        if ($action->owner === null || $action->owner_id === $assignedBy) {
            return;
        }

        $action->owner->notify(new ActionNeeded(
            __('New action for you on :number', ['number' => $action->capa->number]),
            __(':action. Due :date.', ['action' => $action->description, 'date' => $action->due_on?->format('Y-m-d') ?? '—']),
            route('capas.show', $action->capa_id),
            __('Open CAPA'),
        ));
    }

    /**
     * Notify every active user holding a permission (through any role), except one person.
     */
    private static function toPermission(string $permission, ?int $except, ActionNeeded $notification): void
    {
        User::permission($permission)->whereKeyNot($except ?? 0)->get()
            ->each(fn (User $user) => $user->notify($notification));
    }
}
