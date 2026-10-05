<?php

namespace App\View\Components;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * A record's audit trail: who changed what, when, newest first.
 */
class AuditHistory extends Component
{
    /** Bookkeeping columns that say nothing to a reader. */
    private const HIDDEN = ['id', 'file_path', 'file_type', 'file_size', 'created_by', 'remember_token'];

    /** @var Collection<int, AuditLog> */
    public Collection $logs;

    public function __construct(public Model $record, public int $limit = 50)
    {
        $this->logs = AuditLog::query()
            ->where('auditable_type', $record->getMorphClass())
            ->where('auditable_id', $record->getKey())
            ->with('user:id,name')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * The changed fields of one entry, as label => [old, new] display strings.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public function changes(AuditLog $log): array
    {
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];
        $changes = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $field) {
            if (in_array($field, self::HIDDEN, true) || str_ends_with((string) $field, '_id')) {
                continue;
            }

            $changes[__(Str::headline((string) $field))] = [$this->show($old[$field] ?? null), $this->show($new[$field] ?? null)];
        }

        return $changes;
    }

    public function render(): View
    {
        return view('components.audit-history');
    }

    private function show(mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' => '—',
            is_bool($value) => $value ? __('Yes') : __('No'),
            is_array($value) => Str::limit(implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : (string) json_encode($v), $value)), 160),
            is_scalar($value) => Str::limit((string) $value, 160),
            default => (string) json_encode($value),
        };
    }
}
