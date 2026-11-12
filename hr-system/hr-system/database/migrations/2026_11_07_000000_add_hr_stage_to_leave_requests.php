<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
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
        // Schema ->change() (not raw ALTER ... MODIFY) so this also runs on SQLite for tests.
        $this->setStatuses(['pending', 'pending_head', 'pending_hr', 'approved', 'rejected'], 'pending');
        DB::table('leave_requests')->where('status', 'pending')->update(['status' => 'pending_head']);
        $this->setStatuses(['pending_head', 'pending_hr', 'approved', 'rejected'], 'pending_head');
    }

    public function down(): void
    {
        $this->setStatuses(['pending', 'pending_head', 'pending_hr', 'approved', 'rejected'], 'pending');
        DB::table('leave_requests')->whereIn('status', ['pending_head', 'pending_hr'])->update(['status' => 'pending']);
        $this->setStatuses(['pending', 'approved', 'rejected'], 'pending');

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_approved_by');
            $table->dropColumn('head_approved_at');
            $table->dropConstrainedForeignId('hr_approved_by');
            $table->dropColumn('hr_approved_at');
        });
    }

    private function setStatuses(array $statuses, string $default): void
    {
        Schema::table('leave_requests', function (Blueprint $table) use ($statuses, $default) {
            $table->enum('status', $statuses)->default($default)->change();
        });
    }
};
