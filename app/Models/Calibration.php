<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\StoresUploads;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One calibration event. Never edited or deleted: a correction is a new calibration.
 * Create through Gauge::recordCalibration(), which keeps the gauge's due date in step.
 *
 * @property int $id
 * @property int $gauge_id
 * @property CarbonImmutable $performed_on
 * @property string $performed_by in-house name or external lab
 * @property string $result
 * @property string|null $as_found
 * @property string|null $as_left
 * @property CarbonImmutable|null $next_due_on
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $file_type
 * @property int|null $file_size
 * @property string|null $file_sha256
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 */
class Calibration extends Model
{
    use Auditable, HasCreator, StoresUploads;

    public const UPLOAD_DIRECTORY = 'calibrations';

    protected $guarded = ['id', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_on' => 'date:Y-m-d',
            'next_due_on' => 'date:Y-m-d',
            'file_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Gauge, $this>
     */
    public function gauge(): BelongsTo
    {
        return $this->belongsTo(Gauge::class);
    }
}
