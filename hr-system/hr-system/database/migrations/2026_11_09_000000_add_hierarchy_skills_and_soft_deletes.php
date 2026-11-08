<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - Department hierarchy: departments.parent_id → departments.id (self-referencing).
     * - Skills: skills + employee_skill pivot (many-to-many, with a proficiency level).
     * - Soft deletes on users, employees and departments, so deleted records can be restored
     *   and past leave keeps its history.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // If a parent department is ever hard-deleted, its children simply become top-level.
            $table->foreignId('parent_id')->nullable()->after('id')
                ->constrained('departments')->nullOnDelete();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('employee_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['beginner', 'intermediate', 'expert'])->default('intermediate');
            $table->timestamps();

            $table->unique(['employee_id', 'skill_id']); // a skill is listed once per person
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_skill');
        Schema::dropIfExists('skills');

        Schema::table('employees', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('users', fn (Blueprint $table) => $table->dropSoftDeletes());

        Schema::table('departments', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
