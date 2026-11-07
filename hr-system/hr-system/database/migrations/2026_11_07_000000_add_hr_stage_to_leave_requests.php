<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two-step leave approval: Department Head first, then HR.
     * Adds the new statuses and an audit trail of who signed off and when.
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('head_approved_by')->nullable()->after('rejection_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('head_approved_at')->nullable()->after('head_approved_by');
            $table->foreignId('hr_approved_by')->nullable()->after('head_approved_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('hr_approved_at')->nullable()->after('hr_approved_by');
        });

        // Widen the enum, migrate old 'pending' rows to the new first stage, then narrow it.
        DB::statement("ALTER TABLE leave_requests MODIFY status ENUM('pending','pending_head','pending_hr','approved','rejected') NOT NULL DEFAULT 'pending'");
        DB::statement("UPDATE leave_requests SET status = 'pending_head' WHERE status = 'pending'");
        DB::statement("ALTER TABLE leave_requests MODIFY status ENUM('pending_head','pending_hr','approved','rejected') NOT NULL DEFAULT 'pending_head'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE leave_requests MODIFY status ENUM('pending','pending_head','pending_hr','approved','rejected') NOT NULL DEFAULT 'pending'");
        DB::statement("UPDATE leave_requests SET status = 'pending' WHERE status IN ('pending_head','pending_hr')");
        DB::statement("ALTER TABLE leave_requests MODIFY status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_approved_by');
            $table->dropColumn('head_approved_at');
            $table->dropConstrainedForeignId('hr_approved_by');
            $table->dropColumn('hr_approved_at');
        });
    }
};
