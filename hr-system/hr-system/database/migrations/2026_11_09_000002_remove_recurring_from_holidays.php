<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Holidays are now plain dates uploaded each year (dates such as Songkran or
     * lunar holidays change from year to year), so the "same date every year"
     * flag is removed. Existing holidays keep their date.
     */
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropColumn('recurring');
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->boolean('recurring')->default(false)->after('name');
        });
    }
};
