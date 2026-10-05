<?php

namespace App\Models\Concerns;

use App\Models\Signature;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Records that can carry electronic signatures.
 */
trait HasSignatures
{
    /**
     * @return MorphMany<Signature, $this>
     */
    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable')->orderBy('signed_at')->orderBy('id');
    }
}
