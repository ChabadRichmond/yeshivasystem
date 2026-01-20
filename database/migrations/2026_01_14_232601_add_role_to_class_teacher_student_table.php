<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_teacher_student', function (Blueprint $table) {
            // Add role column with enum type
            $table->enum('role', ['primary_teacher', 'attendance_taker'])
                  ->default('attendance_taker')
                  ->after('student_id');

            // Performance indexes for permission lookups
            $table->index(['teacher_user_id', 'role'], 'teacher_role_idx');
            $table->index(['school_class_id', 'teacher_user_id', 'role'], 'class_teacher_role_idx');
            $table->index(['student_id', 'role'], 'student_role_idx');
        });

        // Data migration: Set all existing records to 'attendance_taker' (safer, more restrictive default)
        \DB::table('class_teacher_student')->update(['role' => 'attendance_taker']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_teacher_student', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('teacher_role_idx');
            $table->dropIndex('class_teacher_role_idx');
            $table->dropIndex('student_role_idx');

            // Drop role column
            $table->dropColumn('role');
        });
    }
};
