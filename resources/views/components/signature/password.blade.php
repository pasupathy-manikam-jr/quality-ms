{{-- Password re-entry for a signed action (SignsRecords::signAs). --}}
<flux:input wire:model="signaturePassword" type="password" viewable autocomplete="current-password"
    :label="__('Your password')" badge="*"
    :description="__('Signing as :name.', ['name' => auth()->user()->name])" />
