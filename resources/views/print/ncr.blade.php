@extends('print.layout', ['title' => $ncr->number])

@section('content')
    <h1>{{ __('Non-conformance report') }} {{ $ncr->number }}</h1>
    <p class="meta">{{ $ncr->title }}</p>

    <h2>{{ __('Details') }}</h2>
    <table>
        @foreach ([
            __('Status') => __(Str::headline($ncr->status)),
            __('Severity') => __(Str::headline($ncr->severity)),
            __('Source') => __(Str::headline($ncr->source)),
            __('Part') => $ncr->part?->label(),
            \App\Models\Lot::label() => $ncr->lot?->lot_number,
            __('Supplier') => $ncr->supplier?->name,
            __('Customer') => $ncr->customer,
            __('Quantity affected') => $ncr->quantity_affected !== null ? \App\Support\Decimal::format($ncr->quantity_affected) : null,
            __('Raised') => ($ncr->creator?->name ?? '—').', '.$ncr->created_at?->format('Y-m-d'),
        ] as $label => $value)
            @if ($value)
                <tr><th>{{ $label }}</th><td>{{ $value }}</td></tr>
            @endif
        @endforeach
    </table>

    <h2>{{ __('Description') }}</h2>
    <p class="pre">{{ $ncr->description ?: '—' }}</p>

    <h2>{{ __('Disposition') }}</h2>
    <table>
        <tr><th>{{ __('Decision') }}</th><td>{{ $ncr->disposition ? __(Str::headline($ncr->disposition)) : '—' }}</td></tr>
        <tr><th>{{ __('Notes') }}</th><td class="pre">{{ $ncr->disposition_notes ?: '—' }}</td></tr>
        <tr><th>{{ __('Approved') }}</th><td>{{ $ncr->disposition_approved_at ? ($ncr->dispositionApprover?->name ?? '—').', '.$ncr->disposition_approved_at->format('Y-m-d') : '—' }}</td></tr>
    </table>

    <h2>{{ __('Corrective action') }}</h2>
    <p>{{ $ncr->capas->map(fn ($c) => $c->number.' ('.__(Str::headline($c->status)).')')->join(', ') ?: '—' }}</p>

    @if ($ncr->closed_at)
        <h2>{{ $ncr->status === 'cancelled' ? __('Cancelled') : __('Closed') }}</h2>
        <p class="pre">{{ $ncr->closed_at->format('Y-m-d') }}{{ $ncr->closure_notes ? ': '.$ncr->closure_notes : '' }}</p>
    @endif
@endsection
