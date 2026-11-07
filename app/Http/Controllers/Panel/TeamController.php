<?php

namespace App\Http\Controllers\Panel;

use App\Enums\BusinessRole;
use App\Http\Controllers\Controller;
use App\Mail\TeamInvitation;
use App\Models\BusinessMember;
use App\Support\CurrentBusiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(CurrentBusiness $current): Response
    {
        $members = $current->business()->members()->with('user')->get()
            ->sortBy(fn (BusinessMember $m) => [array_search($m->role, BusinessRole::cases(), true), $m->id]);

        return Inertia::render('Panel/Team', [
            'members' => $members->map(fn (BusinessMember $m) => [
                'id' => $m->id,
                'name' => $m->user?->name ?? $m->email,
                'email' => $m->email,
                'role' => $m->role->value,
                'pending' => $m->isPending(),
                'me' => $m->id === $current->member->id,
            ])->values(),
        ]);
    }

    public function invite(Request $request, CurrentBusiness $current): RedirectResponse
    {
        $business = $current->business();
        $data = $request->validate([
            'email' => ['required', 'email', 'max:120', Rule::unique('business_members')->where('business_id', $business->id)],
            'role' => ['required', Rule::in([BusinessRole::Manager->value, BusinessRole::Worker->value])],
        ], [
            'email.required' => 'Zadajte e‑mail člena tímu.',
            'email.email' => 'Skontrolujte e‑mail.',
            'email.unique' => 'Tento e‑mail už v tíme je.',
        ]);

        $member = $business->members()->create([
            'email' => Str::lower($data['email']),
            'role' => $data['role'],
            'invite_token' => Str::random(48),
        ]);

        Mail::to($member->email)->send(new TeamInvitation($member));

        return back()->with('toast', 'Pozvánka odoslaná na '.$member->email);
    }

    public function update(Request $request, CurrentBusiness $current, BusinessMember $member): RedirectResponse
    {
        $this->editable($current, $member);
        $data = $request->validate(['role' => ['required', Rule::in([BusinessRole::Manager->value, BusinessRole::Worker->value])]]);
        $member->update(['role' => $data['role']]);

        return back()->with('toast', ($member->user?->name ?? $member->email).' je teraz '.mb_strtolower($member->role->label()));
    }

    public function destroy(CurrentBusiness $current, BusinessMember $member): RedirectResponse
    {
        $this->editable($current, $member);
        $member->delete();

        return back()->with('toast', 'Člen tímu odobratý');
    }

    /** Majiteľa ani seba samého meniť nejde. */
    private function editable(CurrentBusiness $current, BusinessMember $member): void
    {
        abort_unless($member->business_id === $current->business()->id, 404);
        abort_if($member->role === BusinessRole::Owner || $member->id === $current->member->id, 403);
    }
}
