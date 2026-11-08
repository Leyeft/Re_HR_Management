<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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

    public function test_api_clients_can_get_use_and_revoke_a_token(): void
    {
        $user = User::factory()->create(['role' => 'hr']);

        $token = $this->postJson('/api/auth/token', ['email' => $user->email, 'password' => 'password', 'device_name' => 'Postman'])
            ->assertCreated()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.profile.can.manage_staff', true)
            ->json('data.token');

        $this->withToken($token)->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.user.id', $user->id);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->withToken($token)->deleteJson('/api/auth/token')->assertOk();
        $this->assertSame(0, $user->tokens()->count());

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_token_needs_the_right_password_and_a_device_name(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/token', ['email' => $user->email, 'password' => 'wrong', 'device_name' => 'Postman'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson('/api/auth/token', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(422)->assertJsonValidationErrors('device_name');
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

    public function test_reset_email_links_to_the_spa_and_the_reset_works(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;
            $this->assertStringContainsString('/reset-password/' . $notification->token, $url);
            $this->assertStringContainsString('email=' . urlencode($user->email), $url);

            $this->postJson('/api/auth/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'brand-new-pass-1',
                'password_confirmation' => 'brand-new-pass-1',
            ])->assertOk();

            return true;
        });

        $this->assertTrue(Hash::check('brand-new-pass-1', $user->fresh()->password));
    }

    public function test_reset_with_a_bad_token_is_refused(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'brand-new-pass-1',
            'password_confirmation' => 'brand-new-pass-1',
        ])->assertStatus(422)->assertJsonPath('code', 'validation_error');
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
