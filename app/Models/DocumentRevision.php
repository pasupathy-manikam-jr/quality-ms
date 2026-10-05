<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\StoresUploads;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One revision of a controlled document: draft → in review → effective → superseded.
 * Approving makes it effective at once and supersedes the previous effective revision.
 *
 * @property int $id
 * @property int $document_id
 * @property string $revision
 * @property string|null $change_summary
 * @property string $status
 * @property int|null $capa_id the CAPA whose D7 asked for this change
 * @property int|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $file_type
 * @property int|null $file_size
 * @property string|null $file_sha256
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class DocumentRevision extends Model
{
    use Auditable, HasCreator, StoresUploads;

    public const UPLOAD_DIRECTORY = 'documents';

    /**
     * Controlled documents are often Word or Excel files, not only PDFs.
     *
     * @return list<string>
     */
    public static function uploadExtensions(): array
    {
        return [...self::UPLOAD_EXTENSIONS, 'doc', 'docx', 'xls', 'xlsx'];
    }

    public const STATUSES = ['draft', 'in-review', 'effective', 'superseded'];

    protected $guarded = ['id', 'created_by', 'status', 'approved_by', 'approved_at'];

    protected $attributes = ['status' => 'draft'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
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
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    /**
     * People asked to read this revision; acknowledged_at is set once they confirm.
     *
     * @return BelongsToMany<User, $this>
     */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_acknowledgements')->withPivot('acknowledged_at')->withTimestamps();
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function submit(): void
    {
        $this->move('draft', 'in-review', match (true) {
            $this->file_path === null => 'Upload the document file first.',
            blank($this->change_summary) => 'Describe what changed in this revision.',
            default => null,
        });
    }

    public function returnToDraft(): void
    {
        $this->move('in-review', 'draft');
    }

    /**
     * Make this revision effective. The author may not approve their own revision.
     */
    public function approve(): void
    {
        $this->move('in-review', 'effective', $this->created_by !== null && $this->created_by === auth()->id()
            ? 'You wrote this revision, so someone else must approve it.'
            : null);
    }

    /**
     * @param  string|null  $error  untranslated reason the move is refused
     */
    private function move(string $from, string $to, ?string $error = null): void
    {
        if ($this->status !== $from) {
            throw ValidationException::withMessages(['status' => __('A :from revision cannot be marked :to.', ['from' => __($this->status), 'to' => __($to)])]);
        }

        if ($error !== null) {
            throw ValidationException::withMessages(['status' => __($error)]);
        }

        DB::transaction(function () use ($to) {
            if ($to === 'effective') {
                $this->document->revisions()->where('status', 'effective')->get()
                    ->each(fn (self $old) => $old->forceFill(['status' => 'superseded'])->save());

                $this->document->forceFill(['next_review_on' => CarbonImmutable::today()->addMonths($this->document->review_interval_months)])->save();
            }

            $this->forceFill([
                'status' => $to,
                ...($to === 'effective' ? ['approved_by' => auth()->id(), 'approved_at' => now()] : []),
            ])->save();
        });
    }
}
