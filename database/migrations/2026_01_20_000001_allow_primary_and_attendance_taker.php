<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_teacher_student', function (Blueprint $table) {
            // Drop the existing unique constraint that prevents multiple assignments per student per class
            $table->dropUnique('class_student_teacher_unique');

            // Add new unique constraint: allows one primary_teacher AND one attendance_taker per student per class
            // But prevents multiple primary_teachers or multiple attendance_takers for same student in same class
            $table->unique(['school_class_id', 'student_id', 'role'], 'class_student_role_unique');
        });
    }

    public function down(): void
    {
        Schema::table('class_teacher_student', function (Blueprint $table) {
            // Drop the new constraint
            $table->dropUnique('class_student_role_unique');

            // Restore the original constraint
            $table->unique(['school_class_id', 'student_id'], 'class_student_teacher_unique');
        });
    }
};
