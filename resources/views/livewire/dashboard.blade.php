<section class="w-full space-y-8">
    <div>
        <flux:heading size="xl" level="1">{{ __('Dashboard') }}</flux:heading>
        <flux:subheading>{{ __('What needs attention today.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($this->tiles as $tile)
            <a href="{{ route($tile['route'], $tile['query']) }}" wire:navigate wire:key="tile-{{ $loop->index }}"
                @class([
                    'rounded-lg border border-s-4 bg-white p-4 transition-colors hover:bg-zinc-50 dark:bg-zinc-800 dark:hover:bg-zinc-700/60',
                    'border-zinc-200 border-s-zinc-200 dark:border-zinc-700 dark:border-s-zinc-700' => $tile['value'] === 0,
                    'border-zinc-200 border-s-safety dark:border-zinc-700' => $tile['value'] > 0,
                ])>
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm">{{ __($tile['label']) }}</flux:text>
                    <flux:icon :name="$tile['icon']" variant="mini" class="text-zinc-400" />
                </div>
                <div @class(['font-display mt-2 text-4xl font-bold', 'text-zinc-400 dark:text-zinc-500' => $tile['value'] === 0, 'text-ink dark:text-white' => $tile['value'] > 0])>{{ $tile['value'] }}</div>
            </a>
        @endforeach
    </div>

    <div class="space-y-3">
        <flux:heading size="lg">{{ __('Waiting for me') }}</flux:heading>
        @forelse ($this->waiting as $item)
            <a href="{{ $item['url'] }}" wire:navigate wire:key="waiting-{{ $loop->index }}"
                @class([
                    'flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border border-s-4 border-zinc-200 bg-white px-4 py-3 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700/60',
                    'border-s-fail' => $item['overdue'],
                    'border-s-safety' => ! $item['overdue'],
                ])>
                <span class="font-medium text-zinc-800 dark:text-white">{{ $item['label'] }}</span>
                <span class="min-w-0 flex-1 truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $item['detail'] }}</span>
                @if ($item['due'])
                    <span @class(['whitespace-nowrap text-sm', 'font-medium text-fail dark:text-red-400' => $item['overdue'], 'text-zinc-500 dark:text-zinc-400' => ! $item['overdue']])>
                        {{ $item['overdue'] ? __('Overdue since :date', ['date' => $item['due']]) : __('Due :date', ['date' => $item['due']]) }}
                    </span>
                @endif
                <flux:icon name="chevron-right" variant="mini" class="text-zinc-400 rtl:rotate-180" />
            </a>
        @empty
            <flux:text>{{ __('Nothing is waiting for you.') }}</flux:text>
        @endforelse
    </div>

    @can('manage-ncrs')
        <div class="space-y-3">
            <div>
                <flux:heading>{{ __('Non-conformances by source, last 90 days') }}</flux:heading>
                <flux:text class="text-sm">{{ __('Largest first. Fix the top sources and most problems go away.') }}</flux:text>
            </div>
            @if ($this->pareto === [])
                <flux:text>{{ __('No NCRs in the last 90 days.') }}</flux:text>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Source') }}</flux:table.column>
                        <flux:table.column class="w-1/2"><span class="sr-only">{{ __('Share') }}</span></flux:table.column>
                        <flux:table.column align="end">{{ __('NCRs') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Share') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Cumulative') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->pareto as $row)
                            <flux:table.row :key="'pareto-'.$row['source']">
                                <flux:table.cell>
                                    <flux:link :href="route('ncrs.index', ['source' => $row['source']])" wire:navigate>{{ __(Str::headline($row['source'])) }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="h-2.5 w-full rounded-full bg-zinc-100 dark:bg-zinc-700" aria-hidden="true">
                                        <div class="h-2.5 rounded-full bg-accent" style="width: {{ $row['share'] }}%"></div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell align="end">{{ $row['count'] }}</flux:table.cell>
                                <flux:table.cell align="end">{{ $row['share'] }}%</flux:table.cell>
                                <flux:table.cell align="end">{{ $row['cumulative'] }}%</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </div>
    @endcan

    @can('manage-documents')
        <div class="space-y-3">
            <div>
                <flux:heading>{{ __('ISO 9001 evidence') }}</flux:heading>
                <flux:text class="text-sm">{{ __('Effective documents tagged with each clause, and the records this system keeps for it. A clause with neither is a gap to look at before an audit.') }}</flux:text>
            </div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Clause') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Documents') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Records') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->isoMatrix as $row)
                        <flux:table.row :key="'iso-'.$row['number']">
                            @if ($row['heading'])
                                <flux:table.cell colspan="4" class="pt-5 font-semibold text-zinc-800 dark:text-white">§{{ $row['number'] }} {{ __($row['title']) }}</flux:table.cell>
                            @else
                                <flux:table.cell class="whitespace-normal ps-4">§{{ $row['number'] }} {{ __($row['title']) }}</flux:table.cell>
                                <flux:table.cell align="end">{{ $row['documents'] ?: '—' }}</flux:table.cell>
                                <flux:table.cell align="end">{{ $row['source'] ? $row['records'].' '.__($row['source']) : '—' }}</flux:table.cell>
                                <flux:table.cell><x-status-badge :status="$row['documents'] + $row['records'] > 0 ? 'covered' : 'gap'" /></flux:table.cell>
                            @endif
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endcan
</section>
