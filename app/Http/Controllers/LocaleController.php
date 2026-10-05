<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Switch the interface language: saved on the user, and in the session for guests.
     */
    public function update(Request $request): RedirectResponse
    {
        $locale = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys((array) config('app.locales')))],
        ])['locale'];

        $request->user()?->forceFill(['lang' => $locale])->save();
        $request->session()->put('locale', $locale);

        return back();
    }
}
