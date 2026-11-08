<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Closed sign-up and email verification.
 */
class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_there_is_no_public_sign_up(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_verification_email_can_be_sent(): void
    {
        Notification::fake();
        Sanctum::actingAs($user = User::factory()->unverified()->create());

        $this->postJson('/api/profile/verification-email')->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_already_verified_users_are_not_sent_another_email(): void
    {
        Notification::fake();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/profile/verification-email')->assertOk()->assertJsonPath('message', 'Your email is already verified.');

        Notification::assertNothingSent();
    }

    public function test_the_link_in_the_email_verifies_and_opens_the_profile(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertRedirect('/profile?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_forged_or_edited_link_does_not_verify(): void
    {
        $user = User::factory()->unverified()->create();

        // Wrong hash, properly signed.
        $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('other@test.com')]))
            ->assertForbidden();

        // Right hash, but not signed.
        $this->get("/email/verify/{$user->id}/" . sha1($user->email))->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_changing_email_requires_verifying_again(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->putJson('/api/profile', ['name' => $user->name, 'email' => 'new-address@test.com'])->assertOk()
            ->assertJsonPath('data.email_verified_at', null);
    }
}
