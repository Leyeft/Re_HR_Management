<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Date Hired drives each employee's annual leave entitlement (years of service).
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('date_hired')->nullable()->after('rank');
        });

        // Backfill existing rows with their record creation date as a reasonable start.
        DB::statement('UPDATE employees SET date_hired = DATE(created_at) WHERE date_hired IS NULL');
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('date_hired');
        });
    }
};
