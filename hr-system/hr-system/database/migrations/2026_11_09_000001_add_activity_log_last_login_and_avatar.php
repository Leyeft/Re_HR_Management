<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * - activity_logs: audit trail of who created / updated / deleted / restored what, and sign-ins.
     * - users.last_login_at / last_login_ip: shown on profiles.
     * - users.avatar_path: profile photo (stored on the public disk).
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // Who did it (null = the system, e.g. the seeder). Kept even if the user is removed.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 20);            // created, updated, deleted, restored, login
            $table->string('subject_type', 40)->nullable(); // e.g. Employee, LeaveRequest
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');            // human-readable summary
            $table->json('changes')->nullable();      // { field: { old, new } } for updates
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->string('avatar_path')->nullable()->after('last_login_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'last_login_ip', 'avatar_path']);
        });

        Schema::dropIfExists('activity_logs');
    }
};
