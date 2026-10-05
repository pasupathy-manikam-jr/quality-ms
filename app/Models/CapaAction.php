<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $capa_id
 * @property string $description
 * @property int|null $owner_id
 * @property CarbonImmutable|null $due_on
 * @property CarbonImmutable|null $done_at
 */
class CapaAction extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'date:Y-m-d',
            'done_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Capa, $this>
     */
    public function capa(): BelongsTo
    {
        return $this->belongsTo(Capa::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id')->withTrashed();
    }
}
