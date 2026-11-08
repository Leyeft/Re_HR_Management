<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The React SPA is one HTML page served at every path; React Router picks the screen.
 * The API, reports, Sanctum and uploaded files are not swallowed by it.
 */
class SpaShellTest extends TestCase
{
    public function test_any_page_path_serves_the_spa(): void
    {
        foreach (['/', '/login', '/employees/5', '/leave/new', '/does/not/exist'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('<div id="root"></div>', false);
        }
    }

    public function test_api_paths_are_not_served_the_spa(): void
    {
        $this->getJson('/api/nothing-here')->assertNotFound()->assertJsonPath('code', 'not_found');
    }

    public function test_csrf_cookie_endpoint_is_available_for_the_spa(): void
    {
        $this->get('/sanctum/csrf-cookie')->assertNoContent()->assertCookie('XSRF-TOKEN');
    }

    public function test_health_check_still_works(): void
    {
        $this->get('/up')->assertOk();
    }
}
