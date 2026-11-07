<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let employees cancel their own requests (with a reason).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE leave_requests MODIFY status ENUM('pending_head','pending_hr','approved','rejected','cancelled') NOT NULL DEFAULT 'pending_head'");

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('cancellation_reason');
        });

        DB::statement("UPDATE leave_requests SET status = 'rejected' WHERE status = 'cancelled'");
        DB::statement("ALTER TABLE leave_requests MODIFY status ENUM('pending_head','pending_hr','approved','rejected') NOT NULL DEFAULT 'pending_head'");
    }
};
