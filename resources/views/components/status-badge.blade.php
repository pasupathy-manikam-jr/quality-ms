@props(['status'])

@php
    // One colour per status, for every module. Add new statuses here, never inline.
    $color = match ($status) {
        'verified', 'approved', 'calibrated', 'adjusted', 'disposition-approved', 'acknowledged', 'covered', 'completed', 'opportunity', 'pass', 'passed', 'closed', 'effective', 'active' => 'green',
        'rejected', 'fail', 'failed', 'overdue', 'out-of-service', 'critical', 'major', 'major-nonconformity' => 'red',
        'received', 'draft', 'open', 'due', 'gap', 'planned', 'minor-nonconformity', 'observation', 'in-review', 'missing', 'in-progress', 'investigating', 'implementing', 'verifying', 'minor' => 'yellow',
        'no-limit' => 'orange',
        'superseded', 'cancelled' => 'zinc',
        default => 'zinc',
    };
@endphp

<flux:badge size="sm" :color="$color" {{ $attributes }}>{{ __(Str::headline($status)) }}</flux:badge>
