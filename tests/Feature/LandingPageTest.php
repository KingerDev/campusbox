<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_renders_for_students_by_default()
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Landing')->where('audience', 'studenti'));
    }

    public function test_landing_page_switches_to_business_audience()
    {
        $this->get('/?pre=podniky')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Landing')->where('audience', 'podniky'));
    }
}
