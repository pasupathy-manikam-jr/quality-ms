<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Internal audits') }}</flux:heading>
            <flux:subheading>{{ __('Planned audits of the quality system (ISO 9001 §9.2). Nonconformities found raise NCRs.') }}</flux:subheading>
        </div>

        @can('create-audits')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Plan audit') }}</flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap gap-2" role="tablist">
        @php($counts = $this->statusCounts)
        @foreach (['' => __('All'), ...array_combine(\App\Models\QualityAudit::STATUSES, array_map(fn ($s) => __(Str::headline($s)), \App\Models\QualityAudit::STATUSES))] as $value => $label)
            <flux:button size="sm" role="tab" :aria-selected="$status === $value ? 'true' : 'false'"
                :variant="$status === $value ? 'primary' : 'ghost'" wire:click="$set('status', '{{ $value }}')">
                {{ $label }}
                <flux:badge size="sm" class="ms-1">{{ $value === '' ? array_sum($counts) : ($counts[$value] ?? 0) }}</flux:badge>
            </flux:button>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search number, title or auditor')" clearable />
        </div>
        <flux:spacer />
        <div class="w-24">
            <x-select wire:model.live="perPage" :aria-label="__('Rows per page')">
                @foreach ($this->perPageOptions() as $option)
                    <x-select.option :value="$option">{{ $option }}</x-select.option>
                @endforeach
            </x-select>
        </div>
    </div>

    <flux:table :paginate="$this->audits">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'number'" :direction="$sortDirection" wire:click="sort('number')">{{ __('Number') }}</flux:table.column>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Lead auditor') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'planned_on' || $sortField === ''" :direction="$sortDirection" wire:click="sort('planned_on')">{{ __('Planned') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Findings') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->audits as $audit)
                <flux:table.row :key="$audit->id">
                    <flux:table.cell><flux:link :href="route('audits.show', $audit)" wire:navigate class="font-medium">{{ $audit->number }}</flux:link></flux:table.cell>
                    <flux:table.cell class="max-w-md truncate">{{ $audit->title }}</flux:table.cell>
                    <flux:table.cell>{{ $audit->leadAuditor?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $audit->planned_on->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell align="end">{{ $audit->findings_count }}</flux:table.cell>
                    <flux:table.cell><x-status-badge :status="$audit->status" /></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-8 text-center">{{ __('No audits found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @can('create-audits')
    <x-modal.form name="audit-form" :title="__('Plan audit')" submit="save" icon="magnifying-glass-circle" :submit-label="__('Plan')" width="xl">
        <flux:input wire:model="title" :label="__('Title')" :badge="__('Required')" :placeholder="__('e.g. Receiving inspection process')" />
        <flux:textarea wire:model="scope" :label="__('Scope')" rows="2" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-select wire:model="lead_auditor_id" :label="__('Lead auditor')" :badge="__('Required')">
                @foreach ($this->users as $user)
                    <x-select.option :value="$user->id">{{ $user->name }}</x-select.option>
                @endforeach
            </x-select>
            <flux:input wire:model="planned_on" type="date" :label="__('Planned for')" :badge="__('Required')" />
        </div>
        <flux:checkbox.group wire:model="clauseIds" :label="__('ISO 9001 clauses in scope')">
            <div class="grid max-h-56 gap-1 overflow-y-auto sm:grid-cols-2">
                @foreach ($this->isoClauses as $clause)
                    <flux:checkbox :value="(string) $clause->id" :label="'§'.$clause->number.' '.__($clause->title)" />
                @endforeach
            </div>
        </flux:checkbox.group>
    </x-modal.form>
    @endcan
</section>
