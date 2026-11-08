<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The React SPA is one HTML page; React Router handles every path under /app.
 */
class SpaShellTest extends TestCase
{
    public function test_any_app_path_serves_the_spa_page(): void
    {
        foreach (['/app', '/app/login', '/app/employees/5', '/app/does/not/exist'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('<div id="root"></div>', false);
        }
    }

    public function test_csrf_cookie_endpoint_is_available_for_the_spa(): void
    {
        $this->get('/sanctum/csrf-cookie')->assertNoContent()->assertCookie('XSRF-TOKEN');
    }
}
