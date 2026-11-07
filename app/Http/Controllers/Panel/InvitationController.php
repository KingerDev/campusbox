<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\BusinessMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $member = $this->find($token);

        // Po prihlásení sa používateľ vráti späť na pozvánku.
        if (! $request->user()) {
            $request->session()->put('url.intended', $request->url());
        }

        return Inertia::render('Panel/Invitation', [
            'token' => $token,
            'business' => $member->business->name,
            'role' => $member->role->label(),
            'email' => $member->email,
            'hasAccount' => User::where('email', $member->email)->exists(),
            'loggedInAs' => $request->user()?->email,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $member = $this->find($token);
        $user = $request->user();

        if ($user && strcasecmp($user->email, $member->email) !== 0) {
            return back()->withErrors(['email' => 'Pozvánka patrí účtu '.$member->email.'. Odhláste sa a prihláste sa ním.']);
        }

        if (! $user) {
            if (User::where('email', $member->email)->exists()) {
                return redirect()->guest(route('login'))->with('status', 'Prihláste sa a pozvánku prijmite znova.');
            }
            $data = $request->validate([
                'name' => ['required', 'string', 'max:80'],
                'password' => ['required', 'confirmed', Password::min(8)],
            ], ['name.required' => 'Vyplňte meno.', 'password.min' => 'Heslo musí mať aspoň 8 znakov.', 'password.confirmed' => 'Heslá sa nezhodujú.']);
            $user = User::create(['name' => $data['name'], 'email' => $member->email, 'password' => $data['password']]);
            $user->forceFill(['email_verified_at' => now()])->save();
            Auth::login($user);
        }

        $member->update(['user_id' => $user->id, 'joined_at' => now(), 'invite_token' => null]);
        $request->session()->put('business_id', $member->business_id);

        return redirect()->route('panel.overview')->with('toast', 'Vitajte v tíme '.$member->business->name);
    }

    private function find(string $token): BusinessMember
    {
        return BusinessMember::with('business')->where('invite_token', $token)->whereNull('joined_at')->firstOrFail();
    }
}
