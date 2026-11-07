<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Human-readable employee code: department id * 100000 + a per-department
     * sequence, e.g. dept 1 -> 100001, 100002; dept 3 -> 300001.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('employee_code')->nullable()->unique()->after('id');
        });

        // Backfill existing employees per department, ordered by id.
        foreach (DB::table('employees')->distinct()->pluck('department_id') as $deptId) {
            $seq = 0;
            foreach (DB::table('employees')->where('department_id', $deptId)->orderBy('id')->pluck('id') as $id) {
                $seq++;
                DB::table('employees')->where('id', $id)->update([
                    'employee_code' => $deptId * 100000 + $seq,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('employee_code');
        });
    }
};
