<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_root_redirects_to_login_and_login_page_loads(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertStatus(200)->assertSee('GearHub');
    }
}
