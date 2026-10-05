<?php

namespace App\Livewire\Concerns;

use App\Models\Signature;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

/**
 * Electronic signatures for Livewire actions. A signed action starts with
 * $this->signAs($record, 'approved'): it re-checks the user's password (so calling the action
 * directly without it fails) and records the signature once the action has succeeded.
 *
 * The page opens the shared signature dialog with requestSignature('action'); actions whose
 * own form already holds the password field (reject with a reason, for example) are called directly.
 * The component lists its signed actions in signedActions().
 */
trait SignsRecords
{
    public string $signaturePassword = '';

    #[Locked]
    public string $signingAction = '';

    /**
     * Signed actions this page offers, mapped to the meaning shown in the dialog.
     *
     * @return array<string, string>
     */
    abstract protected function signedActions(): array;

    public function requestSignature(string $action): void
    {
        abort_unless(array_key_exists($action, $this->signedActions()), 404);

        $this->signingAction = $action;
        $this->reset('signaturePassword');
        $this->resetValidation();
        Flux::modal('signature')->show();
    }

    /**
     * Submit of the shared signature dialog: run the chosen action.
     */
    public function sign(): void
    {
        abort_unless(array_key_exists($this->signingAction, $this->signedActions()), 404);

        try {
            $this->{$this->signingAction}();
        } catch (ValidationException $e) {
            // A wrong password keeps the dialog open; any other problem shows on the page itself.
            if (! array_key_exists('signaturePassword', $e->errors())) {
                Flux::modal('signature')->close();
            }

            throw $e;
        }

        Flux::modal('signature')->close();
    }

    /**
     * The meaning of the signature being requested, for the dialog.
     */
    public function signingMeaning(): string
    {
        return $this->signedActions()[$this->signingAction] ?? '';
    }

    /**
     * Re-authenticate, then run $action and record the signature on $record if it succeeds.
     *
     * @template TResult
     *
     * @param  callable(): TResult  $action
     * @return TResult
     */
    protected function signAs(Model $record, string $meaning, callable $action): mixed
    {
        $this->validate(
            ['signaturePassword' => ['required', 'string', 'current_password']],
            ['signaturePassword.current_password' => __('The password is incorrect.')],
            ['signaturePassword' => __('password')],
        );
        $this->reset('signaturePassword');

        /** @var User $user */
        $user = auth()->user();

        return DB::transaction(function () use ($record, $meaning, $action, $user) {
            $result = $action();

            Signature::query()->create([
                'user_id' => $user->id,
                'signable_type' => $record->getMorphClass(),
                'signable_id' => $record->getKey(),
                'meaning' => $meaning,
                'signer_name' => $user->name,
                'ip_address' => request()->ip(),
                'signed_at' => now(),
            ]);

            return $result;
        });
    }
}
