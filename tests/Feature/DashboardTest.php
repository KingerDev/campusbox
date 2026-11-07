<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_redirects_to_the_business_panel()
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect('/panel');
    }

    public function test_user_without_business_is_sent_to_registration()
    {
        $this->actingAs(User::factory()->create())->get('/panel')->assertRedirect(route('panel.registration'));
    }
}
