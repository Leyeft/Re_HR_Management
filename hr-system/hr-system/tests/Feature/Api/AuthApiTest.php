<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    // The SPA runs on the same site, so requests carry its Origin and get a session.
    private function fromSpa(): static
    {
        return $this->withHeader('Origin', 'http://localhost');
    }

    public function test_login_returns_the_profile_and_permissions(): void
    {
        $employee = $this->makeEmployee(['rank' => 'head']);

        $this->fromSpa()->postJson('/api/auth/login', [
            'email' => $employee->user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email', $employee->user->email)
            ->assertJsonPath('data.employee.rank', 'head')
            ->assertJsonPath('data.can.approve_department_leave', true)
            ->assertJsonPath('data.can.manage_staff', false)
            ->assertJsonMissingPath('data.user.password');

        $this->assertAuthenticatedAs($employee->user);
    }

    public function test_wrong_password_gives_a_structured_validation_error(): void
    {
        $user = User::factory()->create();

        $this->fromSpa()->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_error')
            ->assertJsonStructure(['code', 'message', 'errors' => ['email']]);

        $this->assertGuest();
    }

    public function test_signed_out_requests_get_401_with_the_error_shape(): void
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertExactJson([
                'code' => 'unauthenticated',
                'message' => 'Please sign in to continue.',
                'errors' => [],
            ]);
    }

    public function test_me_returns_the_signed_in_user(): void
    {
        Sanctum::actingAs($user = User::factory()->create(['role' => 'hr']));

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.role', 'hr')
            ->assertJsonPath('data.can.manage_staff', true)
            ->assertJsonPath('data.employee', null);
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->fromSpa()->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->fromSpa()->postJson('/api/auth/logout')->assertOk();

        $this->assertGuest('web');
    }

    public function test_forgot_password_does_not_reveal_whether_an_account_exists(): void
    {
        $a = $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@test.com'])->assertOk()->json('message');
        $b = $this->postJson('/api/auth/forgot-password', ['email' => User::factory()->create()->email])->assertOk()->json('message');

        $this->assertSame($a, $b);
    }

    public function test_profile_can_be_updated(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->putJson('/api/profile', ['name' => 'New Name', 'email' => 'new@test.com'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertNull($user->fresh()->email_verified_at); // new email must be verified again
    }

    public function test_password_change_needs_the_current_password(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/profile/password', [
            'current_password' => 'wrong',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.current_password.0', 'Your current password is incorrect.');
    }

    public function test_unknown_api_route_gives_json_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/does-not-exist')->assertNotFound()->assertJsonPath('code', 'not_found');
    }
}
