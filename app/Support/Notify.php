<?php

namespace App\Support;

use App\Models\Document;
use App\Models\Gauge;
use App\Models\User;
use App\Notifications\CalibrationDue;
use App\Notifications\DocumentReviewDue;
use Illuminate\Support\Collection;

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
}
