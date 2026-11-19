<?php

namespace App\Services;

use App\Enums\BusinessRole;
use App\Mail\BusinessApproved;
use App\Mail\BusinessRejected;
use App\Mail\NewBusinessRegistered;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Overenie nového podniku správcom: oznámenie o registrácii, schválenie a zamietnutie.
 * E‑maily sú doplnok – ak sa nepodarí odoslať, samotná akcia prebehne a chyba sa zaloguje.
 */
class BusinessReview
{
    /** Dá správcom vedieť o novej žiadosti. */
    public function registered(Business $business): void
    {
        $to = User::where('is_admin', true)->pluck('email')
            ->merge(array_filter(array_map('trim', explode(',', (string) config('panel.notify_email')))))
            ->unique()->values()->all();

        if ($to) {
            $this->send($to, new NewBusinessRegistered($business));
        }
    }

    public function approve(Business $business): void
    {
        $business->update(['status' => 'approved', 'approved_at' => now(), 'rejected_at' => null, 'rejection_reason' => null]);
        $this->send($this->owners($business), new BusinessApproved($business));
    }

    public function reject(Business $business, string $reason): void
    {
        $business->update(['status' => 'rejected', 'approved_at' => null, 'rejected_at' => now(), 'rejection_reason' => $reason]);
        $this->send($this->owners($business), new BusinessRejected($business));
    }

    /** @return list<string> */
    private function owners(Business $business): array
    {
        return $business->members()->where('role', BusinessRole::Owner)->whereNotNull('joined_at')->pluck('email')->all();
    }

    /** @param list<string> $to */
    private function send(array $to, object $mail): void
    {
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send($mail);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
