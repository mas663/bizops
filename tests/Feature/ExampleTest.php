<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root URL redirects straight into the admin panel.
     */
    public function test_the_root_url_redirects_to_the_admin_panel(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin');
    }
}
