<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeRoutesTest extends TestCase
{
    public function test_login_page_is_reachable(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_register_page_is_reachable(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_dashboard_redirects_for_guest(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
