{{-- Shared by the certificates list (add) and the certificate page (edit); both bind to $form (CertificateForm). --}}
<x-modal.form name="certificate-form" :title="$title" submit="save" icon="document-check" busy="form.file">
    <x-select wire:model="form.supplier_id" :label="__('Supplier')" badge="*" :placeholder="__('Choose a supplier')">
        @foreach ($this->suppliers as $option)
            <x-select.option :value="$option->id">{{ $option->name }}{{ $option->is_approved ? '' : ' ('.__('not approved').')' }}</x-select.option>
        @endforeach
    </x-select>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:input wire:model="form.number" :label="__('Certificate number')" badge="*" />
        <flux:input wire:model="form.issued_on" :label="__('Issued on')" type="date" badge="*" />
    </div>

    <x-select wire:model.live="form.type" :label="__('Type')" badge="*">
        @foreach (\App\Models\Certificate::TYPES as $value => $label)
            <x-select.option :value="$value">{{ __($label) }}</x-select.option>
        @endforeach
    </x-select>

    @if ($form->type === 'en10204-3.2')
        <flux:input wire:model="form.third_party_inspector" :label="__('Third-party inspector')" badge="*"
            :description="__('A 3.2 certificate is also signed by an independent inspector.')" />
    @endif

    <flux:input wire:model="form.po_number" :label="__('Purchase order')" />

    <div>
        <flux:input type="file" wire:model="form.file" :label="__('Certificate file')" accept=".pdf,.jpg,.jpeg,.png"
            :description="__('PDF or scan, up to 10 MB. Stored privately.')" />
        <div wire:loading wire:target="form.file" class="mt-1 text-xs text-zinc-500">{{ __('Uploading…') }}</div>
    </div>
</x-modal.form>
