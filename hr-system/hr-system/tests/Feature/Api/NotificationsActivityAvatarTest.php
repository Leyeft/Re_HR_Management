<?php

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationsActivityAvatarTest extends TestCase
{
    use RefreshDatabase;

    private function submitLeave($employee): int
    {
        Sanctum::actingAs($employee->user);
        $monday = $this->nextWeekday(1);

        return $this->postJson('/api/leave-requests', [
            'type' => 'annual',
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays(2)->toDateString(),
            'reason' => 'Family trip',
        ])->assertCreated()->json('data.id');
    }

    /* ===== In-app notifications ===== */

    public function test_each_step_of_leave_notifies_the_next_person(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $employee = $this->makeEmployee();
        $hr = User::factory()->create(['role' => 'hr']);
        $otherHead = $this->makeEmployee(['rank' => 'head', 'department' => Department::factory()->create()]);

        $id = $this->submitLeave($employee);

        // 1. Submitted → the Head of that department (not other Heads).
        $this->assertSame('submitted', $head->user->notifications()->first()->data['event']);
        $this->assertSame(0, $otherHead->user->notifications()->count());

        // 2. Head approves → HR is told it's ready for sign-off.
        Sanctum::actingAs($head->user);
        $this->postJson("/api/approvals/{$id}/approve")->assertOk();
        $this->assertSame('needs_hr', $hr->notifications()->first()->data['event']);

        // 3. HR approves → the employee hears back.
        Sanctum::actingAs($hr);
        $this->postJson("/api/hr/leave-requests/{$id}/approve")->assertOk();
        $note = $employee->user->notifications()->first();
        $this->assertSame('approved', $note->data['event']);
        $this->assertSame('/leave', $note->data['url']);
    }

    public function test_rejection_notice_includes_the_reason(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $employee = $this->makeEmployee();
        $id = $this->submitLeave($employee);

        Sanctum::actingAs($head->user);
        $this->postJson("/api/approvals/{$id}/reject", ['rejection_reason' => 'Audit week'])->assertOk();

        $this->assertStringContainsString('Audit week', $employee->user->notifications()->first()->data['message']);
    }

    public function test_heads_own_leave_goes_straight_to_hr_notice(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->submitLeave($head);

        $this->assertSame('needs_hr', $admin->notifications()->first()->data['event']);
        $this->assertSame(0, $head->user->notifications()->count()); // never notified about their own action
    }

    public function test_user_can_list_count_and_mark_notifications_read(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->submitLeave($this->makeEmployee());
        $this->submitLeave($this->makeEmployee());

        Sanctum::actingAs($head->user);
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('data.unread_count', 2);

        $list = $this->getJson('/api/notifications')->assertOk()->assertJsonPath('unread_count', 2);
        $first = $list->json('data.0.id');
        $this->assertSame('New leave request', $list->json('data.0.title'));

        $this->postJson("/api/notifications/{$first}/read")->assertJsonPath('data.unread_count', 1);
        $this->getJson('/api/notifications?unread=1')->assertJsonCount(1, 'data');

        $this->postJson('/api/notifications/read-all')->assertJsonPath('data.unread_count', 0);
    }

    public function test_cannot_read_someone_elses_notification(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->submitLeave($this->makeEmployee());
        $id = $head->user->notifications()->first()->id;

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/notifications/{$id}/read")->assertNotFound();
    }

    /* ===== Activity log ===== */

    public function test_changes_are_logged_with_who_and_old_new_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Alice Admin']);
        Sanctum::actingAs($admin);

        $id = $this->postJson('/api/departments', ['name' => 'Sales'])->json('data.id');
        $this->putJson("/api/departments/{$id}", ['name' => 'Sales & Marketing'])->assertOk();
        $this->deleteJson("/api/departments/{$id}")->assertOk();

        $logs = ActivityLog::where('subject_type', 'Department')->where('subject_id', $id)->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('action')->all());
        $this->assertSame($admin->id, $logs[1]->user_id);
        $this->assertSame(['old' => 'Sales', 'new' => 'Sales & Marketing'], $logs[1]->changes['name']);
    }

    public function test_passwords_are_never_written_to_the_log(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/profile/password', [
            'current_password' => 'password',
            'password' => 'new-secret-pass-1',
            'password_confirmation' => 'new-secret-pass-1',
        ])->assertOk();

        $log = ActivityLog::where('subject_type', 'User')->where('action', 'updated')->latest('id')->first();
        $this->assertSame(['old' => '(hidden)', 'new' => '(hidden)'], $log->changes['password']);
    }

    public function test_sign_in_is_logged_and_sets_last_login(): void
    {
        $user = User::factory()->create();

        $this->withHeader('Origin', 'http://localhost')
            ->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])->assertOk();

        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login', 'user_id' => $user->id]);
        // The last-login bookkeeping itself is not logged as an edit.
        $this->assertDatabaseMissing('activity_logs', ['action' => 'updated', 'subject_type' => 'User', 'subject_id' => $user->id]);
    }

    public function test_skill_changes_are_logged(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $employee = $this->makeEmployee();
        $skill = \App\Models\Skill::factory()->create(['name' => 'Excel']);

        $this->putJson("/api/employees/{$employee->id}", [
            'name' => $employee->user->name, 'email' => $employee->user->email, 'role' => 'employee', 'rank' => 'employee',
            'department_id' => $employee->department_id, 'date_of_birth' => '1990-01-01', 'date_hired' => $employee->date_hired->toDateString(),
            'skills' => [['id' => $skill->id, 'level' => 'expert']],
        ])->assertOk();

        $log = ActivityLog::where('description', 'like', 'Updated skills%')->first();
        $this->assertSame('Excel', $log->changes['skills added']['new']);
    }

    public function test_activity_endpoint_filters_and_is_admin_hr_only(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'hr']));
        $this->postJson('/api/departments', ['name' => 'Legal'])->assertCreated();
        $this->postJson('/api/skills', ['name' => 'Excel'])->assertCreated();

        $this->getJson('/api/activity?subject_type=Skill')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Created skill Excel');
        $this->getJson('/api/activity?action=nonsense')->assertStatus(422);

        Sanctum::actingAs($this->makeEmployee()->user);
        $this->getJson('/api/activity')->assertForbidden();
    }

    /* ===== Avatar upload ===== */

    public function test_avatar_upload_replaces_the_old_photo(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($user = User::factory()->create());

        $first = $this->postJson('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 200, 200)])
            ->assertOk()->json('data.avatar_url');
        $firstPath = $user->fresh()->avatar_path;
        $this->assertNotNull($first);
        Storage::disk('public')->assertExists($firstPath);
        $this->assertStringNotContainsString('me.jpg', $firstPath); // random name, not the uploaded one

        $this->postJson('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('new.png', 300, 300)])->assertOk();
        Storage::disk('public')->assertMissing($firstPath);

        $this->deleteJson('/api/profile/avatar')->assertOk()->assertJsonPath('data.avatar_url', null);
        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_unsafe_or_wrong_files_are_refused(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $svg = UploadedFile::fake()->createWithContent('evil.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->postJson('/api/profile/avatar', ['avatar' => $svg])->assertStatus(422)->assertJsonValidationErrors('avatar');

        $fakeImage = UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "not an image"; ?>');
        $this->postJson('/api/profile/avatar', ['avatar' => $fakeImage])->assertStatus(422);

        $huge = UploadedFile::fake()->image('big.jpg', 500, 500)->size(3000); // 3 MB
        $this->postJson('/api/profile/avatar', ['avatar' => $huge])->assertStatus(422)
            ->assertJsonPath('errors.avatar.0', 'The photo can be at most 2 MB.');

        $tiny = UploadedFile::fake()->image('tiny.png', 10, 10);
        $this->postJson('/api/profile/avatar', ['avatar' => $tiny])->assertStatus(422);

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }
}
