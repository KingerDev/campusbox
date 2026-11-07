<?php

namespace App\Support;

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\BusinessMember;

/**
 * Podnik a rola prihláseného používateľa pre aktuálnu požiadavku do panela.
 */
class CurrentBusiness
{
    public function __construct(public readonly BusinessMember $member) {}

    public function business(): Business
    {
        return $this->member->business;
    }

    public function role(): BusinessRole
    {
        return $this->member->role;
    }

    public function can(string $ability): bool
    {
        return $this->role()->can($ability);
    }
}
