@extends('print.layout', ['title' => $capa->number])

@section('content')
    <h1>{{ __('8D report') }} {{ $capa->number }}</h1>
    <p class="meta">{{ $capa->title }}</p>

    <table>
        <tr><th>{{ __('Type') }}</th><td>{{ __(Str::headline($capa->type)) }}</td></tr>
        <tr><th>{{ __('Status') }}</th><td>{{ __(Str::headline($capa->status)) }}</td></tr>
        <tr><th>{{ __('Owner') }}</th><td>{{ $capa->owner?->name ?? '—' }}</td></tr>
        <tr><th>{{ __('Due') }}</th><td>{{ $capa->due_on?->format('Y-m-d') ?? '—' }}</td></tr>
        <tr><th>{{ __('Non-conformances') }}</th><td>{{ $capa->ncrs->pluck('number')->join(', ') ?: '—' }}</td></tr>
    </table>

    @foreach (\App\Models\Capa::DISCIPLINES as $field => $label)
        <h2>{{ __($label) }}</h2>
        <p class="pre">{{ $capa->{$field} ?: '—' }}</p>

        @if ($field === 'd5_actions' && $capa->actions->isNotEmpty())
            <table>
                @foreach ($capa->actions as $action)
                    <tr><td class="box">{{ $action->done_at ? '☑' : '☐' }} {{ $action->description }}</td><td class="box">{{ $action->owner?->name ?? '—' }}</td><td class="box">{{ $action->due_on?->format('Y-m-d') ?? '—' }}</td></tr>
                @endforeach
            </table>
        @endif
    @endforeach

    <h2>{{ __('Effectiveness check') }}</h2>
    <p class="pre">{{ $capa->effectiveness_verified_at ? __('Verified by :name on :date.', ['name' => $capa->verifier?->name ?? '—', 'date' => $capa->effectiveness_check_on?->format('Y-m-d')])."\n".$capa->effectiveness_notes : '—' }}</p>
@endsection
