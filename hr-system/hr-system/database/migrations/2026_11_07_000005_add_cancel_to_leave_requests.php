<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Let employees cancel their own requests (with a reason).
     */
    public function up(): void
    {
        $this->setStatuses(['pending_head', 'pending_hr', 'approved', 'rejected', 'cancelled']);

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('cancellation_reason');
        });

        DB::table('leave_requests')->where('status', 'cancelled')->update(['status' => 'rejected']);
        $this->setStatuses(['pending_head', 'pending_hr', 'approved', 'rejected']);
    }

    // Schema ->change() (not raw ALTER ... MODIFY) so this also runs on SQLite for tests.
    private function setStatuses(array $statuses): void
    {
        Schema::table('leave_requests', function (Blueprint $table) use ($statuses) {
            $table->enum('status', $statuses)->default('pending_head')->change();
        });
    }
};
