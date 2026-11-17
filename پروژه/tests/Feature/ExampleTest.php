<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The application redirects the root URL to the admin panel.
     */
    public function test_the_application_redirects_to_admin(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin');
    }
}
