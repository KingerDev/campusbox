<?php

namespace Tests\Feature\Panel;

use App\Models\Business;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class PanelTestCase extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        $this->business = Business::firstOrFail();
    }

    protected function as(string $who): static
    {
        return $this->actingAs(User::where('email', $who.'@podurpinom.sk')->firstOrFail());
    }
}
