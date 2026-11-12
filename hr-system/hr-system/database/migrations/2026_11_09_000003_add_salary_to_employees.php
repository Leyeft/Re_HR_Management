<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monthly base salary (THB). Empty until HR fills it in.
 * Who may see or change it is decided in EmployeePolicy (viewSalary / editSalary).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('salary', 10, 2)->nullable()->after('date_hired');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('salary');
        });
    }
};
