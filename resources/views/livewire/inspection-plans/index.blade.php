<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Inspection plans') }}</flux:heading>
            <flux:subheading>{{ __('What to check for each part or material, at receiving, in process and at final inspection.') }}</flux:subheading>
        </div>

        @can('create-inspection-plans')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add plan') }}</flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap gap-2" role="tablist">
        @php($counts = $this->statusCounts)
        @foreach (['' => __('All'), ...array_combine(\App\Models\InspectionPlan::STATUSES, array_map(fn ($s) => __(Str::headline($s)), \App\Models\InspectionPlan::STATUSES))] as $value => $label)
            <flux:button size="sm" role="tab" :aria-selected="$status === $value ? 'true' : 'false'"
                :variant="$status === $value ? 'primary' : 'ghost'" wire:click="$set('status', '{{ $value }}')">
                {{ $label }}
                <flux:badge size="sm" class="ms-1">{{ $value === '' ? array_sum($counts) : ($counts[$value] ?? 0) }}</flux:badge>
            </flux:button>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search title, part or material')" clearable />
        </div>

        <div class="w-full sm:w-48">
            <x-select wire:model.live="stage">
                <x-select.option value="">{{ __('All stages') }}</x-select.option>
                @foreach (\App\Models\InspectionPlan::STAGES as $value)
                    <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
                @endforeach
            </x-select>
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

    <flux:table :paginate="$this->plans">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'title'" :direction="$sortDirection" wire:click="sort('title')">{{ __('Plan') }}</flux:table.column>
            <flux:table.column>{{ __('Part or material') }}</flux:table.column>
            <flux:table.column>{{ __('Stage') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Revision') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Characteristics') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->plans as $plan)
                <flux:table.row :key="$plan->id">
                    <flux:table.cell><flux:link :href="route('inspection-plans.show', $plan)" wire:navigate class="font-medium">{{ $plan->title }}</flux:link></flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" color="blue" variant="outline">{{ $plan->subjectLabel() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ __(Str::headline($plan->stage)) }}</flux:table.cell>
                    <flux:table.cell align="end">{{ $plan->revision }}</flux:table.cell>
                    <flux:table.cell align="end">{{ $plan->items_count }}</flux:table.cell>
                    <flux:table.cell><x-status-badge :status="$plan->status" /></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-8 text-center">{{ __('No inspection plans found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @can('create-inspection-plans')
    <x-modal.form name="plan-form" :title="__('Add plan')" submit="save" icon="clipboard-document-list" :submit-label="__('Create')">
        <x-select wire:model.live="subject_type" :label="__('Plan for')" badge="*">
            <x-select.option value="part">{{ __('A part') }}</x-select.option>
            <x-select.option value="material">{{ __('A purchased material') }}</x-select.option>
        </x-select>

        @if ($subject_type === 'part')
            <x-select wire:model="part_id" :label="__('Part')" badge="*" :placeholder="__('Choose a part')">
                @foreach ($this->parts as $part)
                    <x-select.option :value="$part->id">{{ $part->label() }} · {{ $part->name }}</x-select.option>
                @endforeach
            </x-select>
        @else
            <x-select wire:model="material_id" :label="__('Material')" badge="*" :placeholder="__('Choose a material')">
                @foreach ($this->materials as $material)
                    <x-select.option :value="$material->id">{{ $material->code }} · {{ $material->name }}</x-select.option>
                @endforeach
            </x-select>
        @endif

        <x-select wire:model="planStage" :label="__('Stage')" badge="*">
            @foreach (\App\Models\InspectionPlan::STAGES as $value)
                <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
            @endforeach
        </x-select>

        <flux:input wire:model="title" :label="__('Title')" badge="*" :placeholder="__('e.g. Bracket final inspection')" />
    </x-modal.form>
    @endcan
</section>
