<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Leave type: annual (counts against the yearly entitlement), sick (own 30-day
     * cap, auto-recorded), or professional (company benefit, no limit).
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->enum('type', ['annual', 'sick', 'professional'])
                ->default('annual')
                ->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
