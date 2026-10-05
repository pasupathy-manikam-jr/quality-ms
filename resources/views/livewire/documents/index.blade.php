<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Documents') }}</flux:heading>
            <flux:subheading>{{ __('Controlled documents: policies, procedures, work instructions and forms (ISO 9001 §7.5).') }}</flux:subheading>
        </div>

        @can('create-documents')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add document') }}</flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search number or title')" clearable />
        </div>

        <div class="w-full sm:w-48">
            <x-select wire:model.live="type">
                <x-select.option value="">{{ __('All types') }}</x-select.option>
                @foreach (\App\Models\Document::TYPES as $value)
                    <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
                @endforeach
            </x-select>
        </div>

        <flux:switch wire:model.live="due" :label="__('Due for review')" />

        <flux:spacer />

        <div class="w-24">
            <x-select wire:model.live="perPage" :aria-label="__('Rows per page')">
                @foreach ($this->perPageOptions() as $option)
                    <x-select.option :value="$option">{{ $option }}</x-select.option>
                @endforeach
            </x-select>
        </div>
    </div>

    <flux:table :paginate="$this->documents">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'number' || $sortField === ''" :direction="$sortDirection" wire:click="sort('number')">{{ __('Number') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'title'" :direction="$sortDirection" wire:click="sort('title')">{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column>{{ __('Effective') }}</flux:table.column>
            <flux:table.column>{{ __('In progress') }}</flux:table.column>
            <flux:table.column>{{ __('Owner') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'next_review_on'" :direction="$sortDirection" wire:click="sort('next_review_on')">{{ __('Next review') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->documents as $document)
                <flux:table.row :key="$document->id">
                    <flux:table.cell>
                        <a href="{{ route('documents.show', $document) }}" wire:navigate><flux:badge size="sm" color="blue" variant="outline">{{ $document->number }}</flux:badge></a>
                    </flux:table.cell>
                    <flux:table.cell><flux:link :href="route('documents.show', $document)" variant="ghost" wire:navigate class="font-medium">{{ $document->title }}</flux:link></flux:table.cell>
                    <flux:table.cell>{{ __(Str::headline($document->type)) }}</flux:table.cell>
                    <flux:table.cell>{{ $document->effectiveRevision ? __('Rev :r', ['r' => $document->effectiveRevision->revision]) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($working = $document->revisions->first())
                            <span class="me-1">{{ __('Rev :r', ['r' => $working->revision]) }}</span><x-status-badge :status="$working->status" />
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $document->owner?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <span @class(['font-medium text-amber-600 dark:text-amber-400' => $document->isDueForReview()])>{{ $document->next_review_on?->format('Y-m-d') ?? '—' }}</span>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-8 text-center">{{ __('No documents found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @can('create-documents')
    <x-modal.form name="document-form" :title="__('Add document')" submit="save" icon="document-text" :submit-label="__('Create')">
        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="number" :label="__('Number')" badge="*" :placeholder="__('e.g. QP-075')" />
            <div class="sm:col-span-2">
                <flux:input wire:model="title" :label="__('Title')" badge="*" />
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-select wire:model="docType" :label="__('Type')" badge="*">
                @foreach (\App\Models\Document::TYPES as $value)
                    <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
                @endforeach
            </x-select>
            <x-select wire:model="owner_id" :label="__('Owner')" badge="*">
                @foreach ($this->owners as $owner)
                    <x-select.option :value="$owner->id">{{ $owner->name }}</x-select.option>
                @endforeach
            </x-select>
            <flux:input wire:model="review_interval_months" :label="__('Review every (months)')" badge="*" inputmode="numeric" />
        </div>
    </x-modal.form>
    @endcan
</section>
