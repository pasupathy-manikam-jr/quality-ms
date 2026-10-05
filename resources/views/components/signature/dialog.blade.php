{{-- The shared signature dialog; open it with wire:click="requestSignature('action')" (SignsRecords). --}}
<x-modal.form name="signature" icon="finger-print" submit="sign" width="md"
    :title="__('Sign electronically')"
    :description="__('Enter your password to sign. Your name, the time and the meaning (:meaning) are recorded with the record and cannot be changed.', ['meaning' => __($this->signingMeaning())])"
    :submit-label="__('Sign')">
    <x-signature.password />
    <flux:error name="status" />
</x-modal.form>
