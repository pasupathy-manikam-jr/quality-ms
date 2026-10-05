{{-- Shared by the NCR list (add) and the NCR page (edit); both bind to $form (NcrForm). --}}
<x-modal.form name="ncr-form" :title="$title" submit="save" icon="exclamation-triangle">
    @if (in_array($form->source, \App\Models\Ncr::MANUAL_SOURCES, true))
        <x-select wire:model.live="form.source" :label="__('Source')" badge="*">
            @foreach (\App\Models\Ncr::MANUAL_SOURCES as $value)
                <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
            @endforeach
        </x-select>
    @endif

    <flux:input wire:model="form.title" :label="__('Title')" badge="*" />
    <flux:textarea wire:model="form.description" :label="__('What is wrong')" rows="4" :description="__('Required before the NCR is opened.')" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-select wire:model="form.severity" :label="__('Severity')" badge="*">
            @foreach (\App\Models\Ncr::SEVERITIES as $value)
                <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
            @endforeach
        </x-select>
        <flux:input wire:model="form.quantity_affected" :label="__('Quantity affected')" inputmode="decimal" />
    </div>

    @if ($form->source === 'customer-complaint')
        <flux:input wire:model="form.customer" :label="__('Customer')" badge="*" />
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <x-select wire:model="form.part_id" :label="__('Part')">
            <x-select.option value="">{{ __('None') }}</x-select.option>
            @foreach ($this->parts as $part)
                <x-select.option :value="$part->id">{{ $part->label() }}</x-select.option>
            @endforeach
        </x-select>
        <x-select wire:model="form.supplier_id" :label="__('Supplier')" :badge="$form->source === 'supplier' ? '*' : null">
            <x-select.option value="">{{ __('None') }}</x-select.option>
            @foreach ($this->suppliers as $supplier)
                <x-select.option :value="$supplier->id">{{ $supplier->name }}</x-select.option>
            @endforeach
        </x-select>
    </div>
</x-modal.form>
